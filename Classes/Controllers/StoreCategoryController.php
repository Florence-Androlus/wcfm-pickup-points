<?php
namespace fandWCFMPickupPoints\Classes\Controllers;

// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class StoreCategoryController {

    public function __construct() {
        // On attache le hook de sauvegarde au contrôleur
        add_action('wcfm_vendor_settings_update', [$this, 'saveVendorCategories'], 10, 2);
    
        // Ajout des routes AJAX
        add_action('wp_ajax_get_vendor_categories', [$this, 'ajaxGetCategories']);
        add_action('wp_ajax_nopriv_get_vendor_categories', [$this, 'ajaxGetCategories']);
    }

    /**
     * Logique de sauvegarde 
     */
    public function saveVendorCategories($vendor_id, $wcfm_settings_form) {
        if (isset($wcfm_settings_form['wcfm_store_main_category'])) {
            $categories = array_map('sanitize_text_field', (array) $wcfm_settings_form['wcfm_store_main_category']);
            update_user_meta($vendor_id, 'wcfm_store_custom_categories', $categories);
        } else {
            delete_user_meta($vendor_id, 'wcfm_store_custom_categories');
        }
    }

    /**
     * Méthode Helper pour récupérer les catégories d'un vendeur 
     */
    public static function getVendorCategories($vendor_id) {
        $vendor_id = intval($vendor_id);
        return get_user_meta($vendor_id, 'wcfm_store_custom_categories', true) ?: [];
    }

    /**
     * Récupération AJAX des catégories
     */
    public function ajaxGetCategories() {
        // 1. Vérification du Nonce
        check_ajax_referer('get_categories_nonce', 'security');

        // 2. Vérification des permissions
        if ( ! is_user_logged_in() ) {
            wp_send_json_error('Utilisateur non connecté');
        }

        // 3. Nettoyage de l'entrée
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : get_current_user_id();

        if (!$vendor_id) {
            wp_send_json_error('ID vendeur manquant');
        }

        $categories = self::getVendorCategories($vendor_id);
        wp_send_json_success($categories);
    }
}