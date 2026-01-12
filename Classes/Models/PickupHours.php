<?php
namespace fandWCFMPickupPoints\Classes\Models;

class PickupHours {
    public static function save($data) {
        global $wpdb;
        $table_hours = $wpdb->prefix . 'fand_wcfm_pickup_hours';

        $branch_id = intval($data['branch_id'] ?? 0);
        $pickup_hours = $data['wcfm_pickup_hours']['day_times'] ?? [];
        foreach ($pickup_hours as $day_index => $day_slots) {
            if (!is_array($day_slots)) continue; // sécurité
            foreach ($day_slots as $slot) {
                if (!is_array($slot)) continue; // sécurité
                $open  = isset($slot['start']) ? sanitize_text_field($slot['start']) : '';
                $close = isset($slot['end']) ? sanitize_text_field($slot['end']) : '';

                // ignorer les créneaux vides
                if ($open === '' && $close === '') continue;

                $wpdb->insert(
                    $table_hours,
                    [
                        'branch_id' => $branch_id,
                        'day_of_week' => $day_index,
                        'open_time' => $open,
                        'close_time' => $close
                    ],
                    ['%d','%d','%s','%s']
                );
            }
        }
    }
}
