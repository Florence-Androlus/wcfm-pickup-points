<?php
namespace fandWCFMPickupPoints\Classes\Controllers;

use fandWCFMPickupPoints\Classes\Models\PickupModel;

class ShortcodesController {
    public static function renderPickupMapShortcode() {
        // 1. Entrées
        $filters = [
            'day'    => isset($_GET['pickup_day']) ? sanitize_text_field($_GET['pickup_day']) : null,
            'status' => $_GET['pickup_status'] ?? '',
            'sort'   => $_GET['pickup_orderby'] ?? 'newness_asc',
            'country' => $_GET['country'] ?? 'FR',
            'search' => isset($_GET['pickup_search']) ? sanitize_text_field($_GET['pickup_search']) : ''
        ];

        // 2. Modèle
        $model = new PickupModel();
        //$data  = $model->getGlobalPickupData($filters);
        $data  = $model->getPickupData($filters);
        // 3. Logique d'affichage (calcul du jour actuel pour les filtres de vue)
        $php_day = current_time('w'); 
        $data['current_day_idx'] = ($php_day == 0) ? 6 : $php_day - 1;
        $data['selected_day']    = $filters['day'];
        $data['selected_status'] = $filters['status'];

        // 4. Rendu
        extract($data);
        ob_start();
        include FAND_PICKUP_PLUGIN_DIR . 'views/pickup-map.php'; 
        include FAND_PICKUP_PLUGIN_DIR . 'views/pickup-list.php';
        return ob_get_clean();
    }
}
