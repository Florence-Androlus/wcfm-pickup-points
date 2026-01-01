<?php
namespace fandWCFMPickupPoints\Classes\Controllers;

use fandWCFMPickupPoints\Classes\Models\PickupModel;

class pickuphoursController {

    public function __construct() {
        add_action('wp_ajax_save_pickup_hours', [$this, 'savePickupHours']);
        add_action('wp_ajax_load_pickup_hours_template', [$this, 'loadPickupHoursTemplate']);
    }

    public function savePickupHours() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'save_pickup_hours_nonce')) {
            wp_send_json_error(['message' => 'Nonce invalide']);
        }

        $branch_id = intval($_POST['branch_id']);
        $hours = $_POST['wcfm_pickup_hours'] ?? [];

        $model = new PickupModel();
        $model->saveHours($branch_id, $hours['day_times'] ?? []);


        wp_send_json_success(['message' => 'Horaires sauvegardés !']);
    }

    public function loadPickupHoursTemplate() {
        $branch_id = intval($_POST['branch_id'] ?? 0);
        if (!$branch_id) {
            wp_send_json_error(['message' => 'Branch ID manquant']);
        }

        $model = new PickupModel();
        $hours = $model->getHours($branch_id);
        $holidays = $model->getHolidays($branch_id);

        ob_start();
        include FAND_PICKUP_PLUGIN_DIR . 'views/pickup-hours/pickup-hours-template.php';
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'branch_id' => $branch_id]);
    }
}
