<?php
namespace fandWCFMPickupPoints\Classes\Admin;

use fandWCFMPickupPoints\Classes\Controllers\StoreCategoryController;

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
        /*$is_frontend_map_page = is_page( 'emplacements-pickup' ) || is_page_template( 'template-store-list.php' );
        
        // Condition pour le Store Manager WCFM
        $is_wcfm_vendor_management_page = false;
        if ( function_exists( 'is_wcfm_endpoint_page' ) ) {
            // Vérifie si nous sommes sur la page 'vendors-manage' du Store Manager
            $is_wcfm_vendor_management_page = is_wcfm_endpoint_page( 'vendors-manage' );
        }*/

        //if ( $is_frontend_map_page || $is_wcfm_vendor_management_page ) {

            // CSS spécifique pickup
            wp_enqueue_style('pickup-admin', FAND_PICKUP_PLUGIN_URL . 'assets/css/style.css');
            // Enqueue le Select2 CSS depuis le CDN
            wp_enqueue_style('select2-css','https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',[],'4.1.0');
            wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4' );

            // WCFM CSS/JS
            $wcmm_plugin_file = WP_PLUGIN_DIR . '/wc-multivendor-marketplace/wc-multivendor-marketplace.php';
            $wcmm_assets_url = plugin_dir_url($wcmm_plugin_file) . 'assets/';
            $wcfm_plugin_file = WP_PLUGIN_DIR . '/wc-frontend-manager/wc-frontend-manager.php';
            $wcfm_assets_url = plugin_dir_url($wcfm_plugin_file) . 'assets/';

            // CSS WCFM
            wp_enqueue_style('wcfmmp-style-stores-list', $wcmm_assets_url . 'css/min/store-lists/wcfmmp-style-stores-list.css');
            wp_enqueue_style('wcfmmp-style-stores-list-classic', $wcmm_assets_url . 'css/min/store-lists/wcfmmp-style-stores-list-classic.css');
            wp_enqueue_style('wcfmmp-style-store', $wcmm_assets_url . 'css/min/store/wcfmmp-style-store.css');
            wp_enqueue_style('wcfmmp-style-store-ver', $wcmm_assets_url . 'css/min/store/wcfmmp-style-store.css?ver=3.6.16');
            wp_enqueue_style('wcfmicon', $wcfm_assets_url . 'fonts/font-awesome/css/wcfmicon.min.css?ver=6.7.22');

            // JS spécifique pickup
            wp_enqueue_script('pickup-admin', FAND_PICKUP_PLUGIN_URL . 'assets/js/pickup-admin.js', ['jquery'], '1.0', true);
            wp_localize_script('pickup-admin', 'ajaxurl', admin_url('admin-ajax.php'));
            wp_enqueue_script('view-script-branch-list', FAND_PICKUP_PLUGIN_URL . 'assets/js/view-script-branch-list.js', ['jquery'], '1.0', true);
            wp_localize_script('view-script-branch-list', 'ajaxurl', admin_url('admin-ajax.php'));
            //Enqueue le Select2 JS si ce n'est pas fait
            wp_enqueue_script('select2-js','https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',['jquery'],'4.1.0',true);

            // Leaflet
            wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet/dist/leaflet.css');
            wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet/dist/leaflet.js', [], null, true);
       
            wp_enqueue_style( 'kadence-shop-styles' );

            // On ne cherche plus l'ID ici, car il n'est pas fiable au chargement
            $liste_brute = get_option('liste_categories_boutique', 'Alimentation, Évènementiel, Foodtruck');
            $categories_array = array_map('trim', explode(',', $liste_brute));

            wp_localize_script('pickup-admin', 'MonPluginData', array(
                'categories' => $categories_array,
                'ajax_url'   => admin_url('admin-ajax.php')
            ));

    }
}
