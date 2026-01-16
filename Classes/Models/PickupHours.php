<?php
namespace fandWCFMPickupPoints\Classes\Models;

// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PickupHours {
    /**
     * Sauvegarde les horaires d'ouverture.
     * * @param array $data Les données postées.
     */
    public static function save($data) {
        global $wpdb;
        
        // On construit le nom de la table directement
        $table_name = $wpdb->prefix . 'fand_wcfm_pickup_hours';

        $branch_id = isset($data['branch_id']) ? intval($data['branch_id']) : 0;
        
        // On récupère les créneaux
        $pickup_hours = isset($data['wcfm_pickup_hours']['day_times']) ? $data['wcfm_pickup_hours']['day_times'] : [];

        if ( empty($branch_id) || empty($pickup_hours) ) {
            return;
        }

        // Optionnel : Supprimer les anciens horaires pour cette branche avant d'insérer les nouveaux
        // 1. Suppression en base de données
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $table_name, ['branch_id' => $branch_id], ['%d'] );

        // 2. Invalidation du cache pour cette branche spécifique
        // On utilise la même clé et le même groupe que dans BranchModel.php
        wp_cache_delete( 'branch_hours_' . intval( $branch_id ), 'fand_pickup' );

        foreach ($pickup_hours as $day_index => $day_slots) {
            if ( ! is_array($day_slots) ) {
                continue;
            }

            foreach ($day_slots as $slot) {
                if ( ! is_array($slot) ) {
                    continue;
                }

                $open  = isset($slot['start']) ? sanitize_text_field($slot['start']) : '';
                $close = isset($slot['end']) ? sanitize_text_field($slot['end']) : '';

                // Ignorer les créneaux vides
                if ( '' === $open && '' === $close ) {
                    continue;
                }

                // 1. Insertion en base de données
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->insert(
                    $table_name,
                    [
                        'branch_id'   => intval( $branch_id ),
                        'day_of_week' => intval( $day_index ),
                        'open_time'   => $open,
                        'close_time'  => $close
                    ],
                    [ '%d', '%d', '%s', '%s' ]
                );

                // 2. Invalidation du cache pour forcer la mise à jour à l'affichage
                wp_cache_delete( 'branch_hours_' . intval( $branch_id ), 'fand_pickup' );
            }
        }
    }
}