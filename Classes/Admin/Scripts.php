<?php

namespace fandWCFMPickupPoints\Classes\Admin;

use fandWCFMPickupPoints\Classes\Database\Database;
use fandWCFMPickupPoints\Classes\Models\BranchModel;
use fandWCFMPickupPoints\Classes\Models\PickupModel;

// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Scripts {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'fandpipo_enqueue_scripts']);
        add_action('wp_enqueue_scripts', function() {
            wp_deregister_script('wcfmmp_store_lists_script');
        }, 999);
        // Ajoute ceci dans ta classe Scripts ou dans ton fichier functions.php
        add_action('wp_ajax_nopriv_get_osm_address', [$this, 'get_osm_address']);
        add_action('wp_ajax_get_osm_address', [$this, 'get_osm_address']);
        // Pour les utilisateurs connectés
        add_action('wp_ajax_get_reverse_address', [$this, 'get_reverse_address']);
        // Pour les visiteurs non connectés
        add_action('wp_ajax_nopriv_get_reverse_address', [$this, 'get_reverse_address']);
    }
    
    function get_reverse_address() {
        // Vérification de sécurité simple
        if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
            wp_send_json_error(['message' => 'Paramètres manquants']);
        }

        $lat = sanitize_text_field($_GET['lat']);
        $lng = sanitize_text_field($_GET['lng']);
        
        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$lat&lon=$lng";
        
        $response = wp_remote_get($url, [
            'headers' => ['User-Agent' => 'PickupPointsApp/1.0 (contact@tonsite.fr)'],
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Erreur API Nominatim']);
        }
        
        wp_send_json(json_decode(wp_remote_retrieve_body($response)));
    }

    public function get_osm_address() {
        $query = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $url = "https://nominatim.openstreetmap.org/search?format=json&limit=5&q=" . urlencode($query);
        
        $response = wp_remote_get($url, [
            'headers' => ['User-Agent' => 'PickupPointsApp/1.0 (contact@tonsite.fr)'],
            'timeout' => 15
        ]);

        // 1. Vérifier si c'est une erreur de connexion WordPress
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Erreur serveur']);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // 2. Vérifier si Nominatim renvoie une erreur 429
        if ($code === 429) {
            wp_send_json_error(['message' => 'Trop de requêtes, réessayez plus tard.']);
        }

        // 3. Envoyer le contenu uniquement si tout va bien
        wp_send_json(json_decode($body));
    }

    /**
     * Enqueue les scripts et styles nécessaires
     */
    public function fandpipo_enqueue_scripts() {
        
        // --- Conditions de chargement ---
        $endpoint = filter_input(INPUT_GET, 'endpoint', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $is_wcfm_page = ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) || ( $endpoint === 'wcfm-settings' );
        $is_map_page = is_page('emplacements-pickup');
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $is_single_emplacement = is_singular('emplacement') || (strpos($request_uri, '/pickup/emplacement/') !== false);
        $is_frontend_map_page = $is_map_page || $is_single_emplacement;
        
        // WCFM Assets
        $wcmm_assets_url = plugins_url( 'wc-multivendor-marketplace/assets/' );
        $wcfm_assets_url = plugins_url( 'wc-frontend-manager/assets/' );

        // CSS WCFM (Chargé systématiquement si besoin)
        wp_enqueue_style('wcfmmp-style-stores-list', $wcmm_assets_url . 'css/min/store-lists/wcfmmp-style-stores-list.css', [], WCFMmp_VERSION);
        wp_enqueue_style('wcfmmp-style-stores-list-classic', $wcmm_assets_url . 'css/min/store-lists/wcfmmp-style-stores-list-classic.css', [], WCFMmp_VERSION);
        wp_enqueue_style('wcfmmp-style-store', $wcmm_assets_url . 'css/min/store/wcfmmp-style-store.css', [], WCFMmp_VERSION);
        wp_enqueue_style('wcfmmp-style-store-responsive', $wcmm_assets_url . 'css/min/store/wcfmmp-style-store-responsive.css', [], WCFMmp_VERSION);
        wp_enqueue_style('wcfmicon', $wcfm_assets_url . 'fonts/font-awesome/css/wcfmicon.min.css', [], WCFMmp_VERSION);

        // --- DONNÉES COMMUNES ---
        $categories_objets = Database::get_all_categories();
        $common_data = [
            'ajax_url'                     => admin_url('admin-ajax.php'),
            'fandpipoloadPickupNonce'      => wp_create_nonce('fandpipo_load_pickup_hours_nonce'),
            'fandpiposavePickupNonce'      => wp_create_nonce('fandpipo_save_pickup_hours_nonce'),
            'fandpipogetCategoriesNonce'   => wp_create_nonce('fandpipo_get_categories_nonce'),
            'categories'                   => $categories_objets,
        ];

        // Script Admin
        wp_enqueue_style('pickup-admin', FANDPIPO_PLUGIN_URL . 'assets/css/style.css', [], FANDPIPO_VERSION);
        wp_enqueue_script('pickup-admin', FANDPIPO_PLUGIN_URL . 'assets/js/pickup-admin.js', ['jquery'], '1.0', true);
        wp_localize_script('pickup-admin', 'fandpipo_pickup_data', $common_data);

        // --- CHARGEMENT FRONT (CARTE) ---
        if ( $is_frontend_map_page ) {
            $map_markers = [];
            $lat = 46.6;
            $lng = 2.4;
            $is_single = false;
            
            if ( $is_single_emplacement ) {
                $vendor_id = get_query_var('current_vendor_id');
                $branch_raw = get_query_var('current_branch_data');
                $branch_model = new BranchModel();
                $single_data = $branch_model->getSingleBranchData($vendor_id, $branch_raw);
                $is_single = true;
                if ($single_data) {
                    $map_markers = [$single_data];
                    $lat = $single_data['lat'];
                    $lng = $single_data['lng'];
                }
            } else {
                $data = PickupModel::fandpipo_getPickupData([]);
                $map_markers = $data['fandpipo_markers'];
                $lat = filter_input(INPUT_GET, 'fandpipo_lat', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 43.1785; 
                $lng = filter_input(INPUT_GET, 'fandpipo_lng', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 6.5208;
            }

            $map_settings = [
                'markers'         => $map_markers,
                'currentLat'      => $lat,
                'currentLng'      => $lng,
                'isSingleView'    => $is_single,
                'defaultCategory' => trim(explode(',', get_option('fandpipo_liste_categories_boutique'))[0]),
            ];

            // Leaflet
            wp_enqueue_style('leaflet-search-css', FANDPIPO_PLUGIN_URL . 'assets/css/leaflet-search.css', [], '2.9.0');
            wp_enqueue_style('leaflet-css', FANDPIPO_PLUGIN_URL . 'assets/css/leaflet.css', [], '1.9.4');
            wp_enqueue_script('leaflet-js', FANDPIPO_PLUGIN_URL . 'assets/js/leaflet.js', [], '1.9.4', true);

            // 2. Ensuite, les scripts utilitaires (sans dépendances complexes)
            wp_enqueue_script('pickup-map-time-utils', FANDPIPO_PLUGIN_URL . 'assets/js/pickup-map-time-utils.js', ['jquery'], FANDPIPO_VERSION, true);

            // 3. Enfin, les scripts de logique (qui dépendent de Leaflet et jQuery)
            wp_enqueue_script('pickup-map-filter-system', FANDPIPO_PLUGIN_URL . 'assets/js/pickup-map-filter-system.js', ['jquery', 'pickup-map-time-utils'], FANDPIPO_VERSION, true);
            //wp_enqueue_script('pickup-map-geoloc', FANDPIPO_PLUGIN_URL . 'assets/js/pickup-map-geoloc.js', ['jquery', 'leaflet-js'], FANDPIPO_VERSION, true);
            wp_enqueue_script('pickup-map-core', FANDPIPO_PLUGIN_URL . 'assets/js/pickup-map-core.js', ['jquery', 'leaflet-js', 'pickup-map-filter-system'], FANDPIPO_VERSION, true);
            wp_localize_script('pickup-map-time-utils', 'fandpipoData', $map_settings);
            // Localisation (indispensable pour passer les données PHP vers JS)
            wp_localize_script('pickup-map-core', 'fandpipoData', $map_settings);

            wp_enqueue_script('pickup-radius', FANDPIPO_PLUGIN_URL . 'assets/js/pickup-radius.js', ['jquery'], FANDPIPO_VERSION, true);

            // Autres Assets
            wp_enqueue_style('select2-css', FANDPIPO_PLUGIN_URL . 'assets/css/select2.min.css', [], '4.1.0');
            wp_enqueue_style('font-awesome', FANDPIPO_PLUGIN_URL . 'assets/css/all.min.css', [], '5.15.4');
            wp_enqueue_script('view-script-branch-list', FANDPIPO_PLUGIN_URL . 'assets/js/view-script-branch-list.js', ['jquery'], '1.0', true);
            wp_localize_script('view-script-branch-list', 'fandpipo_data', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('fandpipo_filter_nonce')
            ]);

            wp_enqueue_script('select2-js', FANDPIPO_PLUGIN_URL . 'assets/js/select2.min.js', ['jquery'], '4.1.0', true);
            


            // Styles Kadence (si présent)
            wp_enqueue_style( 'kadence-shop-styles' );
        }
    }
}