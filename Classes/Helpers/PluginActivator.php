<?php
namespace fandWCFMPickupPoints\Classes\Helpers;

use fandWCFMPickupPoints\Classes\Database\Database;

// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PluginActivator {

    public static function fandpipo_createPage() {
        $page_id = get_option('fandpipo_pickup_page_id');
        // Si la page n'existe plus on la recrée
        if (!$page_id || !get_post($page_id)) {
            $page_id = wp_insert_post([
                'post_title'    => 'Emplacements Pickup',
                'post_name'     => 'emplacements-pickup', 
                'post_type'     => 'page',
                'post_status'   => 'publish',
                'post_content'  => '[fandpipo_map]',
                'page_template' => 'fand-pickup-template.php',
                'meta_input'    => [
                    '_wp_page_template' => 'fand-pickup-template.php' 
                ]
            ]);

            if ($page_id && !is_wp_error($page_id)) {
               update_option('fandpipo_pickup_page_id', (int) $page_id);
            }
        }
    }

    public static function fandpipo_createTables() {
        Database::fandpipo_createTables();
    }
}