<?php
namespace fandWCFMPickupPoints\Classes\Controllers;

use fandWCFMPickupPoints\Classes\Models\PickupModel;

class ShortcodesController {
    public static function renderPickupMapShortcode() {
        // 1. Entrées sécurisées
        $filters = [
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'day'     => isset($_GET['pickup_day']) ? sanitize_text_field(wp_unslash($_GET['pickup_day'])) : null,
            
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'status'  => isset($_GET['pickup_status']) ? sanitize_text_field(wp_unslash($_GET['pickup_status'])) : '',
            
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'sort'    => isset($_GET['pickup_orderby']) ? sanitize_text_field(wp_unslash($_GET['pickup_orderby'])) : 'newness_asc',
            
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'country' => isset($_GET['country']) ? sanitize_text_field(wp_unslash($_GET['country'])) : 'FR',
            
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'search'  => isset($_GET['pickup_search']) ? sanitize_text_field(wp_unslash($_GET['pickup_search'])) : ''
        ];

        // 2. Modèle
        $model = new PickupModel();
        $data  = $model->getPickupData($filters);

        // 3. Logique d'affichage
        $php_day = current_time('w'); 
        $data['current_day_idx'] = ($php_day == 0) ? 6 : $php_day - 1;
        $data['selected_day']    = $filters['day'];
        $data['selected_status'] = $filters['status'];

        // 4. Rendu
        if ( is_array( $data ) ) {
            extract( $data );
        }
        
        ob_start();
        $map_view  = FAND_PICKUP_PLUGIN_DIR . 'views/pickup-map.php';
        $list_view = FAND_PICKUP_PLUGIN_DIR . 'views/pickup-list.php';

        if ( file_exists( $map_view ) ) {
            include $map_view;
        }
        if ( file_exists( $list_view ) ) {
            include $list_view;
        }

        return ob_get_clean();
    }
}