<?php
namespace fandWCFMPickupPoints\Classes\Controllers;

use fandWCFMPickupPoints\Classes\Models\PickupModel;

class ShortcodesController {
    public static function fandpipo_renderPickupMapShortcode() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            check_ajax_referer('fandpipo_filter_nonce', 'security');
        }
        // 1. Entrées sécurisées
        $filters = [
            'fandpipo_day'     => isset($_GET['fandpipo_pickup_day']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_day'])) : null,
            'fandpipo_status'  => isset($_GET['fandpipo_pickup_status']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_status'])) : '',
            'fandpipo_sort'    => isset($_GET['fandpipo_pickup_orderby']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_orderby'])) : 'newness_asc',
            'fandpipo_country' => isset($_GET['fandpipo_country']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_country'])) : 'FR',
            'fandpipo_search'  => isset($_GET['fandpipo_pickup_search']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_search'])) : ''
        ];

        // 2. Modèle
        $model = new PickupModel();
        $data  = $model->fandpipo_getPickupData($filters);

        // 3. Logique d'affichage
        $php_day = current_time('w'); 
        $data['current_day_idx'] = ($php_day == 0) ? 6 : $php_day - 1;
        $data['selected_day']    = $filters['fandpipo_day'];
        $data['selected_status'] = $filters['fandpipo_status'];

        // 4. Rendu
        if ( is_array( $data ) ) {
            extract( $data );
        }
        
        ob_start();
        $map_view  = FANDPIPO_PLUGIN_DIR . 'views/fandpipo-pickup-map.php';
        $list_view = FANDPIPO_PLUGIN_DIR . 'views/fandpipo-pickup-list.php';

        if ( file_exists( $map_view ) ) {
            include $map_view;
        }
        if ( file_exists( $list_view ) ) {
            include $list_view;
        }

        return ob_get_clean();
    }
}