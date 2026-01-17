<?php

namespace fandWCFMPickupPoints\Classes\Admin;

// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Scripts {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_enqueue_scripts', function() {
            wp_deregister_script('wcfmmp_store_lists_script');
        }, 999);
    }

	/**
	 * Enqueue les scripts et styles nécessaires uniquement sur les pages administratives spécifiques
	 *
	 * @param string $hook_suffix Identifiant de la page actuelle
	 */

	public function enqueue_scripts() {
        global $WCFM, $WCFMmp;
        // --- Conditions de chargement ---
        // 1. Est-on sur la page WCFM (Admin Vendeur) ?
        $is_wcfm_page = (function_exists('wcfm_is_store_page') && wcfm_is_store_page()) || (isset($_GET['endpoint']) && $_GET['endpoint'] == 'wcfm-settings');

        // 2. Est-on sur la page de la carte (front) ?
        $is_map_page = is_page('emplacements-pickup');

        // 3. Est-on sur une page "Emplacement" individuelle (votre URL actuelle) ?
        // On teste si c'est le Custom Post Type 'emplacement' ou si le slug est présent dans l'URL
        $is_single_emplacement = is_singular('emplacement') || strpos($_SERVER['REQUEST_URI'], '/pickup/emplacement/') !== false;

        $is_frontend_map_page = $is_map_page || $is_single_emplacement;
        error_log('is_frontend_map_page : '.$is_frontend_map_page);
        // WCFM CSS/JS
        $wcmm_plugin_file = WP_PLUGIN_DIR . '/wc-multivendor-marketplace/wc-multivendor-marketplace.php';
        $wcmm_assets_url = plugin_dir_url($wcmm_plugin_file) . 'assets/';
        $wcfm_plugin_file = WP_PLUGIN_DIR . '/wc-frontend-manager/wc-frontend-manager.php';
        $wcfm_assets_url = plugin_dir_url($wcfm_plugin_file) . 'assets/';

        // CSS WCFM
        wp_enqueue_style('wcfmmp-style-stores-list', $wcmm_assets_url . 'css/min/store-lists/wcfmmp-style-stores-list.css', [],WCFMmp_VERSION);
        wp_enqueue_style('wcfmmp-style-stores-list-classic', $wcmm_assets_url . 'css/min/store-lists/wcfmmp-style-stores-list-classic.css', [],WCFMmp_VERSION);
        wp_enqueue_style('wcfmmp-style-store', $wcmm_assets_url . 'css/min/store/wcfmmp-style-store.css', [],WCFMmp_VERSION);
        wp_enqueue_style('wcfmmp-style-store-ver', $wcmm_assets_url . 'css/min/store/wcfmmp-style-store.css', [],WCFMmp_VERSION);
        wp_enqueue_style('wcfmmp-style-store-responsive',$wcmm_assets_url . 'css/min/store/wcfmmp-style-store-responsive.css', [],WCFMmp_VERSION);
        wp_enqueue_style('wcfmicon', $wcfm_assets_url . 'fonts/font-awesome/css/wcfmicon.min.css', [],WCFMmp_VERSION);

        // JS spécifique pickup
        wp_enqueue_script('pickup-admin', FAND_PICKUP_PLUGIN_URL . 'assets/js/pickup-admin.js', ['jquery'], '1.0', true);

        // On ne cherche plus l'ID ici, car il n'est pas fiable au chargement
        $liste_brute = get_option('liste_categories_boutique', 'Alimentation, Évènementiel, Foodtruck');
        $categories_array = array_map('trim', explode(',', $liste_brute));

        $common_data = [
            'ajax_url'           => admin_url('admin-ajax.php'),
            'loadPickupNonce'    => wp_create_nonce('load_pickup_hours_nonce'),
            'savePickupNonce'    => wp_create_nonce('save_pickup_hours_nonce'),
            'getCategoriesNonce' => wp_create_nonce('get_categories_nonce'),
            'categories'         => $categories_array,
        ];

        // 2. Chargement du script ADMIN (WCFM)
        wp_enqueue_script('pickup-admin', FAND_PICKUP_PLUGIN_URL . 'assets/js/pickup-admin.js', ['jquery'], '1.0', true);
        wp_localize_script('pickup-admin', 'FAND_PICKUP_DATA', $common_data);

        // 3. Chargement du script FRONT (La Carte)
        if ( $is_frontend_map_page ) {
            wp_enqueue_script('fand-pickup-map', FAND_PICKUP_PLUGIN_URL . 'assets/js/pickup-map-script.js', ['jquery'], '1.0', true);
            // On peut utiliser le même objet common_data pour la carte
            wp_localize_script('fand-pickup-map', 'FAND_PICKUP_DATA', $common_data);

            // CSS spécifique pickup
            wp_enqueue_style('pickup-admin', FAND_PICKUP_PLUGIN_URL . 'assets/css/style.css', [],FAND_PICKUP_VERSION);
            wp_enqueue_script('fand-pickup-map-script', FAND_PICKUP_PLUGIN_URL . 'assets/js/pickup-map-script.js',array('jquery'), [], FAND_PICKUP_VERSION,true );
            
            // Enqueue le Select2 CSS depuis le CDN
            //wp_enqueue_style('select2-css','https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',[],'4.1.0');
            wp_enqueue_style('select2-css', FAND_PICKUP_PLUGIN_URL . 'assets/css/select2.min.css', [], '4.1.0');
            //wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4' );
            wp_enqueue_style('font-awesome', FAND_PICKUP_PLUGIN_URL . 'assets/css/all.min.css', [], '5.15.4');
        
            wp_enqueue_script('view-script-branch-list', FAND_PICKUP_PLUGIN_URL . 'assets/js/view-script-branch-list.js', ['jquery'], '1.0', true);
            wp_localize_script('view-script-branch-list', 'ajaxurl', admin_url('admin-ajax.php'));

            //Enqueue le Select2 JS si ce n'est pas fait
            //wp_enqueue_script('select2-js','https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',['jquery'],'4.1.0',true);
            wp_enqueue_script('select2-js', FAND_PICKUP_PLUGIN_URL . 'assets/js/select2.min.js', ['jquery'], '4.1.0', true);
            
            // Leaflet
            //wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet/dist/leaflet.css');
            wp_enqueue_style('leaflet-css', FAND_PICKUP_PLUGIN_URL . 'assets/css/leaflet.css', [], '1.9.4');
            //wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet/dist/leaflet.js', [], null, true);
            wp_enqueue_script('leaflet-js', FAND_PICKUP_PLUGIN_URL . 'assets/js/leaflet.js', [], '1.9.4', true);

            wp_enqueue_style( 'kadence-shop-styles' );
        
        }
    }
}
