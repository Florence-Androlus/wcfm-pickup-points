<?php
namespace fandWCFMPickupPoints\Classes\Models;

class PickupHours {
    public static function save($data) {
        global $wpdb;
        $table_hours = $wpdb->prefix . 'fand_wcfm_pickup_hours';

        $branch_id = intval($data['branch_id'] ?? 0);
        $pickup_hours = $data['wcfm_pickup_hours']['day_times'] ?? [];

        foreach ($pickup_hours as $day_index => $day_slots) {
            foreach ($day_slots as $slot) {
                $wpdb->insert(
                    $table_hours,
                    [
                        'branch_id' => $branch_id,
                        'day_of_week' => $day_index,
                        'open_time' => sanitize_text_field($slot['start']),
                        'close_time' => sanitize_text_field($slot['end'])
                    ],
                    ['%d','%d','%s','%s']
                );
            }
        }
    }
}
