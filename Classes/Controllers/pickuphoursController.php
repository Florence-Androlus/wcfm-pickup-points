<?php
namespace fandWCFMPickupPoints\Classes\Controllers;

use fandWCFMPickupPoints\Classes\Models\PickupModel;

class pickuphoursController {

    public function __construct() {
        add_action('wp_ajax_save_pickup_hours', [$this, 'savePickupHours']);
        add_action('wp_ajax_load_pickup_hours_template', [$this, 'loadPickupHoursTemplate']);
    }

    public function savePickupHours() {
        // --- Nonce
        $nonce = isset($_POST['_wpnonce']) ? wp_unslash($_POST['_wpnonce']) : '';
        if (! wp_verify_nonce($nonce, 'save_pickup_hours_nonce')) {
            wp_send_json_error(['message' => 'Nonce invalide']);
        }

        // --- Branch ID
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        if (!$branch_id) {
            wp_send_json_error(['message' => 'Branch ID manquant']);
        }

        // --- Horaires
        $hours = isset($_POST['wcfm_pickup_hours']) ? wp_unslash($_POST['wcfm_pickup_hours']) : [];
        $hours_raw = isset($_POST['wcfm_pickup_hours']) ? wp_unslash($_POST['wcfm_pickup_hours']) : '{}';
        $hours = json_decode($hours_raw, true);

        $day_times = $hours['day_times'] ?? [];

        // --- Sauvegarde
        $model = new PickupModel();
        $model->saveHours($branch_id, $day_times);

        wp_send_json_success(['message' => 'Horaires sauvegardés !']);
    }


    public function loadPickupHoursTemplate() {
        // --- Nonce
        $nonce = isset($_POST['_wpnonce']) ? wp_unslash($_POST['_wpnonce']) : '';
        if (! wp_verify_nonce($nonce, 'load_pickup_hours_nonce')) {
            wp_send_json_error(['message' => 'Nonce invalide']);
        }

        // --- Branch ID
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        if (!$branch_id) {
            wp_send_json_error(['message' => 'Branch ID manquant']);
        }

        // --- Récupération données
        $model = new PickupModel();
        $hours = $model->getHours($branch_id);
        $holidays = $model->getHolidays($branch_id);

        ob_start();
        include FAND_PICKUP_PLUGIN_DIR . 'views/pickup-hours/pickup-hours-template.php';
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'branch_id' => $branch_id]);
    }
}
