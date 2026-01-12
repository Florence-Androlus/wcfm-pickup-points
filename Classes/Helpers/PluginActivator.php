<?php
namespace fandWCFMPickupPoints\Classes\Helpers;
use function dbDelta;

class PluginActivator {

    public static function createPage() {

        $page_id = get_option('fand_pickup_page_id');

        // Si la page n'existe plus on la recrée
        if (!$page_id || !get_post($page_id)) {

            $page_id = wp_insert_post([
                'post_title'   => 'Emplacements Pickup',
                'post_name'    => 'emplacements-pickup', 
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_content' => '[pickup_points_map]',
                'page_template' => 'fand-pickup-template.php',
                'meta_input'   => [
                    '_wp_page_template' => 'fand-pickup-template.php' 
                ]
            ]);

            if ($page_id && !is_wp_error($page_id)) {
                update_option('fand_pickup_page_id', $page_id);
            }
        }
    }

    public static function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_hours = $wpdb->prefix . 'fand_wcfm_pickup_hours';
        $table_holidays = $wpdb->prefix . 'fand_wcfm_pickup_holidays';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql_hours = "CREATE TABLE IF NOT EXISTS $table_hours (
            ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            branch_id bigint(20) unsigned NOT NULL,
            day_of_week tinyint(1) NOT NULL,
            open_time time DEFAULT NULL,
            close_time time DEFAULT NULL,
            is_closed tinyint(1) DEFAULT 0,
            PRIMARY KEY (ID),
            KEY branch_day (branch_id, day_of_week)
        ) $charset_collate;";

        $sql_holidays = "CREATE TABLE IF NOT EXISTS $table_holidays (
            ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            branch_id bigint(20) unsigned NOT NULL,
            holiday_date date NOT NULL,
            note varchar(255) DEFAULT NULL,
            PRIMARY KEY (ID),
            UNIQUE KEY branch_holiday (branch_id, holiday_date)
        ) $charset_collate;";

        dbDelta($sql_hours);
        dbDelta($sql_holidays);
    }
}
