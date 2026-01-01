<?php
namespace fandWCFMPickupPoints\Classes\Controllers;

class StoreCategoryController {

    public function __construct() {
        // On attache le hook de sauvegarde au contrôleur
        add_action('wcfm_vendor_settings_update', [$this, 'saveVendorCategories'], 10, 2);
    
        // Ajout des routes AJAX
        add_action('wp_ajax_get_vendor_categories', [$this, 'ajaxGetCategories']);
    }

    /**
     * Logique de sauvegarde 
     */
    public function saveVendorCategories($vendor_id, $wcfm_settings_form) {
        error_log("saveVendorCategories");
        if (isset($wcfm_settings_form['wcfm_store_main_category'])) {
            $categories = array_map('sanitize_text_field', $wcfm_settings_form['wcfm_store_main_category']);
            update_user_meta($vendor_id, 'wcfm_store_custom_categories', $categories);
             error_log("update_user_meta");
        } else {
            delete_user_meta($vendor_id, 'wcfm_store_custom_categories');
            error_log("delete_user_meta");
        }
    }

    /**
     * Méthode Helper pour récupérer les catégories d'un vendeur 
     */
    public static function getVendorCategories($vendor_id) {
        return get_user_meta($vendor_id, 'wcfm_store_custom_categories', true) ?: [];
    }

    public function ajaxGetCategories() {
        $vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;

        if (!$vendor_id) {
            // Si pas d'ID envoyé, on tente de récupérer l'ID de l'utilisateur connecté
            $vendor_id = get_current_user_id();
        }
        $categories = self::getVendorCategories($vendor_id);
        wp_send_json_success($categories);
    }
}