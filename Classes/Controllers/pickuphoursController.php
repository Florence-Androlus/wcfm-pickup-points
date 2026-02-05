<?php

namespace fandWCFMPickupPoints\Classes\Controllers;

// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use fandWCFMPickupPoints\Classes\Models\PickupModel;

class pickuphoursController {

    public function __construct() {
        add_action('wp_ajax_fandpipo_save_pickup_hours', [$this, 'fandpipo_savePickupHours']);
        add_action('wp_ajax_nopriv_fandpipo_save_pickup_hours', [$this, 'fandpipo_savePickupHours']);
        add_action('wp_ajax_load_pickup_hours_template', [$this, 'fandpipo_loadPickupHoursTemplate']);
        add_action('wp_ajax_nopriv_load_pickup_hours_template', [$this, 'fandpipo_loadPickupHoursTemplate']);
    }

    public function fandpipo_savePickupHours() {
        // --- Nonce (Sanitized)
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if (! wp_verify_nonce($nonce, 'fandpipo_save_pickup_hours_nonce')) {
            wp_send_json_error(['message' => 'Nonce invalide']);
        }

        // --- Branch ID
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;

        if (!$branch_id) {
            wp_send_json_error(['message' => 'Branch ID manquant']);
        }

        // --- Horaires (Sanitized avant json_decode)
        $hours_raw = isset($_POST['wcfm_pickup_hours']) ? sanitize_text_field(wp_unslash($_POST['wcfm_pickup_hours'])) : '';
        $hours = json_decode($hours_raw, true) ?: [];
        $day_times = $hours['day_times'] ?? [];

        // --- Sauvegarde
        $model = new PickupModel();
        $model->fandpipo_saveHours($branch_id, $day_times);

        wp_send_json_success(['message' => 'Horaires sauvegardés !']);
    }

    public function fandpipo_loadPickupHoursTemplate() {
        
        // --- Nonce (Sanitized)
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if (! wp_verify_nonce($nonce, 'fandpipo_load_pickup_hours_nonce')) {
            wp_send_json_error(['message' => 'Nonce invalide']);
        }

        if (!current_user_can('manage_woocommerce') && !wcfm_is_vendor()) {
            wp_send_json_error(['message' => 'Accès refusé']);
        }

        // --- Branch ID
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        if (!$branch_id) {
            wp_send_json_error(['message' => 'Branch ID manquant']);
        }

        // --- Récupération données
        $model = new PickupModel();
        $fand_hours = $model->fandpipo_getHours($branch_id);
        $fand_holidays = $model->fandpipo_getHolidays($branch_id);

        ob_start();

        $template_path = trailingslashit(FANDPIPO_PLUGIN_DIR) . 'views/pickup-hours/fandpipo-pickup-hours-template.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } 
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'branch_id' => $branch_id]);
    }
}
