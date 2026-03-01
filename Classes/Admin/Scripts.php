<?php

namespace fandWCFMPickupPoints\Classes\Admin;

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
    }

	/**
	 * Enqueue les scripts et styles nécessaires uniquement sur les pages administratives spécifiques
	 *
	 * @param string $hook_suffix Identifiant de la page actuelle
	 */

	public function fandpipo_enqueue_scripts() {
        
        // --- Conditions de chargement ---
        // 1. Est-on sur la page WCFM (Admin Vendeur) ?// On sécurise la récupération du paramètre endpoint
        $endpoint = filter_input(INPUT_GET, 'endpoint', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $is_wcfm_page = ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) || ( $endpoint === 'wcfm-settings' );
        
        // 2. Est-on sur la page de la carte (front) ?
        $is_map_page = is_page('emplacements-pickup');

        // 3. Est-on sur une page "Emplacement" individuelle (votre URL actuelle) ?
        // On teste si c'est le Custom Post Type 'emplacement' ou si le slug est présent dans l'URL
        // On récupère l'URI, on enlève les slashs magiques, on nettoie et on sécurise
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])) : '';

        $is_single_emplacement = is_singular('emplacement') || (strpos($request_uri, '/pickup/emplacement/') !== false);
        $is_frontend_map_page = $is_map_page || $is_single_emplacement;
        
        // WCFM CSS/JS
        $wcmm_assets_url = plugins_url( 'wc-multivendor-marketplace/assets/' );
        $wcfm_assets_url = plugins_url( 'wc-frontend-manager/assets/' );

        // CSS WCFM
        wp_enqueue_style('wcfmmp-style-stores-list', $wcmm_assets_url . 'css/min/store-lists/wcfmmp-style-stores-list.css', [],WCFMmp_VERSION);
        wp_enqueue_style('wcfmmp-style-stores-list-classic', $wcmm_assets_url . 'css/min/store-lists/wcfmmp-style-stores-list-classic.css', [],WCFMmp_VERSION);
        wp_enqueue_style('wcfmmp-style-store', $wcmm_assets_url . 'css/min/store/wcfmmp-style-store.css', [],WCFMmp_VERSION);
        wp_enqueue_style('wcfmmp-style-store-ver', $wcmm_assets_url . 'css/min/store/wcfmmp-style-store.css', [],WCFMmp_VERSION);
        wp_enqueue_style('wcfmmp-style-store-responsive',$wcmm_assets_url . 'css/min/store/wcfmmp-style-store-responsive.css', [],WCFMmp_VERSION);
        wp_enqueue_style('wcfmicon', $wcfm_assets_url . 'fonts/font-awesome/css/wcfmicon.min.css', [],WCFMmp_VERSION);

        // JS spécifique pickup
        wp_enqueue_script('pickup-admin', FANDPIPO_PLUGIN_URL . 'assets/js/pickup-admin.js', ['jquery'], '1.0', true);

        // On ne cherche plus l'ID ici, car il n'est pas fiable au chargement
        $liste_brute = get_option('fandpipo_liste_categories_boutique', 'Alimentation, Évènementiel, Foodtruck');
        $categories_array = array_map('trim', explode(',', $liste_brute));

        $common_data = [
            'ajax_url'           => admin_url('admin-ajax.php'),
            'fandpipoloadPickupNonce'    => wp_create_nonce('fandpipo_load_pickup_hours_nonce'),
            'fandpiposavePickupNonce'    => wp_create_nonce('fandpipo_save_pickup_hours_nonce'),
            'fandpipogetCategoriesNonce' => wp_create_nonce('fandpipo_get_categories_nonce'),
            'categories'         => $categories_array,
        ];

        // CSS spécifique pickup
        wp_enqueue_style('pickup-admin', FANDPIPO_PLUGIN_URL . 'assets/css/style.css', [],FANDPIPO_VERSION);

        // 2. Chargement du script ADMIN (WCFM)
        wp_enqueue_script('pickup-admin', FANDPIPO_PLUGIN_URL . 'assets/js/pickup-admin.js', ['jquery'], '1.0', true);
        wp_localize_script('pickup-admin', 'fandpipo_pickup_data', $common_data);

        // 3. Chargement du script FRONT (La Carte)
        if ( $is_frontend_map_page ) {
            $map_markers = [];
            $lat = 46.6;
            $lng = 2.4;
            $is_single = false;
            
            // On prépare TOUTES les variables nécessaires
            if ( $is_single_emplacement ) {
                // --- CAS PAGE SINGLE ---
                // On récupère les données via le BranchModel (comme dans ton template)
                $vendor_id = get_query_var('current_vendor_id');
                $branch_raw = get_query_var('current_branch_data');
                
                $branch_model = new BranchModel();
                $single_data = $branch_model->getSingleBranchData($vendor_id, $branch_raw);
                $is_single = true;
                if ($single_data) {
                    $map_markers = [$single_data]; // Un seul marqueur dans le tableau
                    $lat = $single_data['lat'];
                    $lng = $single_data['lng'];
                }
            } else {
                // --- CAS PAGE CARTE GLOBALE ---
                $data = PickupModel::fandpipo_getPickupData([]);
                $map_markers = $data['fandpipo_markers'];
                $lat = get_query_var('fandpipo_lat', 46.6);
                $lng = get_query_var('fandpipo_lng', 2.4);
            }

            $map_settings = [
                'markers'         => $map_markers,
                'currentLat'      => $lat,
                'currentLng'      => $lng,
                'isSingleView'    => $is_single,
                'defaultCategory' => trim(explode(',', get_option('fandpipo_liste_categories_boutique'))[0]),
            ];

            // Chargement unique du script
            wp_enqueue_script('fand-pickup-raduis', FANDPIPO_PLUGIN_URL . 'assets/js/pickup-raduis.js', array('jquery'), FANDPIPO_VERSION, true);
          
            wp_enqueue_script('fand-pickup-map', FANDPIPO_PLUGIN_URL . 'assets/js/pickup-map.js', array('jquery', 'leaflet-js'), FANDPIPO_VERSION, true);
            
            // Injection des données sous le nom "fandpipoData"
            wp_localize_script('fand-pickup-map', 'fandpipoData', $map_settings);

            wp_enqueue_script('fand-pickup-map-script', FANDPIPO_PLUGIN_URL . 'assets/js/pickup-map-script.js',array('jquery'), [], FANDPIPO_VERSION,true );
            // On peut utiliser le même objet common_data pour la carte
            wp_localize_script('fand-pickup-map-script', 'fandpipo_pickup_data', $common_data);

            // Enqueue le Select2 CSS depuis le CDN
            wp_enqueue_style('select2-css', FANDPIPO_PLUGIN_URL . 'assets/css/select2.min.css', [], '4.1.0');
            wp_enqueue_style('font-awesome', FANDPIPO_PLUGIN_URL . 'assets/css/all.min.css', [], '5.15.4');
        
            wp_enqueue_script('view-script-branch-list', FANDPIPO_PLUGIN_URL . 'assets/js/view-script-branch-list.js', ['jquery'], '1.0', true);
            wp_localize_script('view-script-branch-list', 'fandpipo_data', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('fandpipo_filter_nonce') // Le jeton de sécurité
            ]);

            //Enqueue le Select2 JS si ce n'est pas fait
            wp_enqueue_script('select2-js', FANDPIPO_PLUGIN_URL . 'assets/js/select2.min.js', ['jquery'], '4.1.0', true);
            
            // Leaflet
            wp_enqueue_style('leaflet-search-css', FANDPIPO_PLUGIN_URL . 'assets/css/leaflet-search.css',[], '2.9.0');
            wp_enqueue_style('leaflet-css', FANDPIPO_PLUGIN_URL . 'assets/css/leaflet.css', [], '1.9.4');
            wp_enqueue_script('leaflet-js', FANDPIPO_PLUGIN_URL . 'assets/js/leaflet.js', [], '1.9.4', true);
            //wp_enqueue_script('leaflet-search-js', FANDPIPO_PLUGIN_URL . 'assets/js/leaflet-search.js', [], '2.9.0', true);

            wp_enqueue_style( 'kadence-shop-styles' );
        
        }
    }
}
