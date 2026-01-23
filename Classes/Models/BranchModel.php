<?php
/**
 * Pickup Model
 * * phpcs:disable WordPress.DB.DirectDatabaseQuery
 */

namespace fandWCFMPickupPoints\Classes\Models;

use Automattic\WooCommerce\Internal\Admin\ProductReviews\Reviews;

class BranchModel {

    /**
     * Récupère toutes les données nécessaires pour l'affichage de la page de la succursale.
     * @param int $vendor_id L'ID de l'utilisateur/vendeur WCFM.
     * @param array $branch_data Les données de la succursale (latitude, longitude, adresse, etc.)
     * @return array
     */
    public function getSingleBranchData($vendor_id, $branch_data) {
        global $wpdb;

        // 1. Infos du Vendeur (WCFM/WP User Meta)
        $profile_settings = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);

        $vendor_user = get_userdata($vendor_id); // Récupère l'objet utilisateur WP

        $vendor_email = !empty($profile_settings['store_email']) ? $profile_settings['store_email'] : ($vendor_user ? $vendor_user->user_email : get_user_meta($vendor_id, 'billing_email', true));
        $vendor_phone = !empty($profile_settings['phone']) ? $profile_settings['phone'] : get_user_meta($vendor_id, 'billing_phone', true);
        
        $store_user = function_exists('wcfmmp_get_store') ? wcfmmp_get_store($vendor_id) : null;
        $store_info = $store_user ? $store_user->get_shop_info() : [];

        // 2. Infos de la Succursale
        $branch_id = $branch_data['branch_id'] ?? 0;
        $city = $branch_data['city'] ?? '';
        $postal = $branch_data['postal_code'] ?? '';
        $lat = $branch_data['lat'] ?? 0; // Utiliser latitude et longitude comme stocké
        $lng = $branch_data['lng'] ?? 0;

        // 3. Formatage pour la Vue
        $display_address = esc_html( $branch_data['address'] ?? 'Adresse non spécifiée' ) . ' ' . strtoupper($postal) . ' ' . $city . ', France';
        $avatar_id = isset($profile_settings['gravatar']) ? $profile_settings['gravatar'] : 0;
        $avatar_url = wp_get_attachment_url($avatar_id);
        $store_url = function_exists('get_wcfm_store_url') ? get_wcfm_store_url($vendor_id) : '#';

        // AJOUT : Récupération de la bannière
        $banner_id = isset($profile_settings['banner']) ? $profile_settings['banner'] : 0;
        $banner_url = $banner_id ? wp_get_attachment_url($banner_id) : plugins_url('wc-multivendor-marketplace/assets/images/default_banner.jpg');
        
        // 4. Catégories de Produits Spécifiques au Vendeur
        $product_ids_by_vendor = $this->getVendorProductIds($vendor_id);
        $category_terms_to_show = $this->getVendorTopLevelCategories($product_ids_by_vendor);

        // Récupération de tous les horaires pour ces branches
        // 1. Définir une clé de cache spécifique à cette branche
        $cache_key   = 'branch_hours_' . intval( $branch_id );
        $cache_group = 'fand_pickup';

        // 2. Tenter de récupérer les horaires depuis le cache
        $hours = wp_cache_get( $cache_key, $cache_group );

        if ( false === $hours ) {
            // 3. Si non présent en cache, on exécute la requête
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $hours = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT branch_id, day_of_week, open_time, close_time, is_closed
                        FROM {$wpdb->prefix}fand_wcfm_pickup_hours
                        WHERE branch_id = %d",
                    intval( $branch_id )
                ),
                ARRAY_A
            );

            // 4. On stocke le résultat en cache (ex: pour 1 heure)
            wp_cache_set( $cache_key, $hours, $cache_group, 3600 );
        }

        // Organise les horaires par branch_id et day_of_week
        $hours_by_branch = [];
        foreach ($hours as $h) {
            $day = $h['day_of_week'];
            $hours_by_branch[$h['branch_id']][$day][] = [
                'open_time'  => $h['open_time'],
                'close_time' => $h['close_time'],
                'is_closed' => $h['is_closed']
            ];
        }

        // 1. Définir le tableau des horaires pour la branche unique (si trouvé)
        $single_branch_hours = $hours_by_branch[$branch_id] ?? [];
        
        // 2. S'assurer que c'est bien un tableau (sécurité)
        if (!is_array($single_branch_hours)) {
            $single_branch_hours = [];
        }

        // On récupère le pack "Reviews" complet (moyenne + liste)
        $reviews_pack = $this->getVendorReviews($vendor_id);

        return [
            'vendor_id' => $vendor_id,
            'branch_id' => $branch_id,
            'branch_name' => esc_html( $branch_data['branch_name'] ?? 'Point de Retrait' ),
            'display_address' => $display_address,
            'lat' => floatval($lat),
            'lng' => floatval($lng),
            'vendor_email' => $vendor_email,
            'vendor_phone' => $vendor_phone,
            'avatar_url' => $avatar_url,
            'banner_url'       => $banner_url,
            'store_url' => $store_url,
            'opening_hours' => $single_branch_hours,
            'store_info' => $store_info, // Peut être utilisé pour le nom du magasin
            'category_terms' => $category_terms_to_show,
            'reviews'      => $reviews_pack['list'],
            'rating_avg'   => $reviews_pack['avg'],
            'rating_count' => $reviews_pack['count']
        ];
    }

    /**
     * Récupère les IDs de tous les produits publiés par un vendeur.
     */
    private function getVendorProductIds($vendor_id) {
        $query = new \WP_Query( array(
            'fields'         => 'ids',
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'author'         => $vendor_id,
            'posts_per_page' => -1,
        ) );
        return $query->posts;
    }

    /**
     * Récupère les catégories de produits de niveau supérieur utilisées par un ensemble de produits.
     */
    private function getVendorTopLevelCategories($product_ids) {
        if ( empty( $product_ids ) ) {
            return [];
        }

        return wp_get_object_terms( $product_ids, 'product_cat', array(
            'fields'     => 'all',
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => true,
            'parent'     => 0, // Top-Level seulement
        ) );
    }

    /**
     * Récupère les avis ET calcule les statistiques en une seule fois
     */
    public function getVendorReviews($vendor_id) {
        global $wpdb;
        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared

        $table_reviews = $wpdb->prefix . 'wcfm_marketplace_reviews';
        $table_meta    = $wpdb->prefix . 'wcfm_marketplace_review_rating_meta';

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT ID as comment_ID, author_name as comment_author, review_description as comment_content, created as comment_date, review_rating as rating
            FROM %i WHERE vendor_id = %d AND approved = 1 ORDER BY created DESC",
            $table_reviews,
            intval($vendor_id)
        ), ARRAY_A );

        if ( ! empty($results) ) {
            foreach ( $results as &$review ) {
                $review['sub_ratings'] = $wpdb->get_results( $wpdb->prepare(
                    "SELECT `key`, `value` FROM %i WHERE review_id = %d AND type = 'rating_category'",
                    $table_meta,
                    intval($review['comment_ID'])
                ), ARRAY_A );
            }
        }

        $total_rating = 0;
        $count = count($results);
        if ($count > 0) {
            foreach ($results as $r) { 
                $total_rating += floatval($r['rating']); 
            }
        }

        // phpcs:enable

        return [
            'list'  => $results ? $results : [],
            'count' => $count,
            'avg'   => $count > 0 ? round($total_rating / $count, 1) : 0
        ];
    }
}
// phpcs:enable