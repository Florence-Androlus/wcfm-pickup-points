<?php
namespace fandWCFMPickupPoints\Classes\Models;

class PickupModel {

    private $table_hours;
    private $table_holidays;

    public function __construct() {
        global $wpdb;
        $this->table_hours = $wpdb->prefix . 'fand_wcfm_pickup_hours';
        $this->table_holidays = $wpdb->prefix . 'fand_wcfm_pickup_holidays';
    }

    /**
     * Sauvegarde les horaires d'une branche
     *
     * @param int   $branch_id
     * @param array $day_times Structure : [0 => [[start,end,id], ...], 1 => [...], ...]
     */
    public function saveHours($branch_id, $day_times) {
        global $wpdb;

        if (!$branch_id || !is_array($day_times)) return;

        // 1️⃣ Supprimer uniquement les jours existants pour cette branche
        foreach ($day_times as $day_index => $slots) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->delete(
                $this->table_hours,
                [
                    'branch_id' => $branch_id,
                    'day_of_week' => intval($day_index)
                ],
                ['%d', '%d']
            );
        }

        // 2️⃣ Insérer les créneaux pour chaque jour
        foreach ($day_times as $day_index => $slots) {
            if (!empty($slots) && is_array($slots)) {
                PickupHours::save([
                    'branch_id' => $branch_id,
                    'wcfm_pickup_hours' => [
                        'day_times' => [
                            $day_index => $slots
                        ]
                    ]
                ]);
            }
        }
    }

    public function saveHolidays($branch_id, $data) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $this->table_holidays,
            [
                'vacation_mode'      => isset($data['vacation_mode']) ? 1 : 0,
                'disable_purchase'   => isset($data['disable_purchase']) ? 1 : 0,
                'type'               => sanitize_text_field($data['type'] ?? ''),
                'start_date'         => sanitize_text_field($data['start_date'] ?? ''),
                'end_date'           => sanitize_text_field($data['end_date'] ?? ''),
                'message'            => sanitize_textarea_field($data['message'] ?? ''),
            ],
            ['branch_id' => $branch_id],
            ['%d','%d','%s','%s','%s','%s'],
            ['%d']
        );
    }

    public function getHours($branch_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fand_wcfm_pickup_hours';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, day_of_week, open_time, close_time 
                FROM %i 
                WHERE branch_id = %d
                ORDER BY day_of_week, open_time ASC",
                $table_name,
                $branch_id
            ),
            ARRAY_A
        );

        // Regrouper par jour
        $hours_by_day = [];
        foreach ($results as $row) {
            $day = intval($row['day_of_week']);

            // Ignorer les horaires "vides"
            if ($row['open_time'] === '00:00:00' && $row['close_time'] === '00:00:00') continue;

            $hours_by_day[$day][] = [
                'id'    => intval($row['ID']), // Ajout de l'ID
                'start' => $row['open_time'],
                'end'   => $row['close_time']
            ];
        }

        return $hours_by_day;
    }

    public function getHolidays($branch_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fand_wcfm_pickup_holidays';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM %i WHERE branch_id=%d",$table_name, $branch_id),
            ARRAY_A
        );
    }

    public static function getPickupData($filters) { 
        global $wpdb;
        error_log('getPickupData');
        // --- 1. Initialisation des variables ---
        $markers = [];
        $vendors_data = [];
        $day = $filters['day'] ?? '';
        $status = $filters['status'] ?? '';
        $orderby = $filters['orderby'] ?? '';

        // Récupération de la catégorie sélectionnée dans l'URL (le filtre)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $selected_category = isset($_GET['category']) ? sanitize_text_field(wp_unslash($_GET['category'])) : '';

        // Récupération de la liste globale des catégories pour le retour
        $liste_brute = get_option('liste_categories_boutique', 'Alimentation, Évènementiel, Foodtruck');
        $all_categories_list = array_map('trim', explode(',', $liste_brute));

        // Récupération de la localisation par défaut (WCFM)
        $options = get_option('wcfm_marketplace_options');
        $default_location = isset($options['default_geolocation']['location']) ? $options['default_geolocation']['location'] : '';
        
        // Récupération des pays pour WooCommerce
        $countries_obj = new \WC_Countries(); 
        $countries = $countries_obj->get_countries();
        $default_country = 'FR'; // Valeur par défaut

        if ($default_location && $countries) {
            $country_codes = array_flip($countries); 
            $default_country = $country_codes[$default_location] ?? 'FR'; 
        }
        error_log($default_country);

        // --- 2. Récupération des vendeurs ---
        $vendors = get_users(['role__in' => ['wcfm_vendor']]);

        foreach ($vendors as $vendor) {
            $vendor_id = intval($vendor->ID);
            //On récupère les réglages WCFM 
            $store_settings = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);

            // On définit l'email et le téléphone
            // On cherche d'abord dans WCFM, sinon on prend le meta WordPress standard
            $vendor_email = !empty($store_settings['store_email']) ? $store_settings['store_email'] : $vendor->user_email;
            $vendor_phone = !empty($store_settings['phone']) ? $store_settings['phone'] : get_user_meta($vendor_id, 'billing_phone', true);
            
            // --- LOGIQUE DES CATÉGORIES PERSONNALISÉES ---
            // On récupère le tableau des catégories choisies par le vendeur
            $vendor_categories = get_user_meta($vendor_id, 'wcfm_store_custom_categories', true);
            $vendor_categories = is_array($vendor_categories) ? $vendor_categories : [];

            // Filtrage : si une catégorie est sélectionnée dans le filtre, on vérifie la correspondance
            if (!empty($selected_category)) {
                if (!in_array($selected_category, $vendor_categories)) {
                    continue; // On passe au vendeur suivant si la catégorie ne correspond pas
                }
            }

            // Définition de la catégorie assignée pour l'affichage (priorité au filtre ou à la première trouvée)
            $assigned_category = !empty($selected_category) ? $selected_category : (!empty($vendor_categories) ? $vendor_categories[0] : '');

            // --- RÉCUPÉRATION DES BRANCHES (LOCATIONS) ---
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $branches = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wcfm_store_locations WHERE store_id = %d", $vendor_id),
                ARRAY_A
            );

            if (empty($branches)) continue;

            $branch_ids = wp_list_pluck($branches, 'ID');
            // On génère les placeholders (%d, %d, %d...)
            $placeholders = implode( ',', array_fill( 0, count( $branch_ids ), '%d' ) );

            // 1. Préparation de la requête brute avec les placeholders déjà injectés
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $sql_meta = "SELECT branch_id, meta_value 
                        FROM {$wpdb->prefix}wcfm_store_locations_meta 
                        WHERE branch_id IN ($placeholders) 
                        AND meta_key = 'offers_pickup'";

            // 2. On utilise prepare() sur la chaîne construite
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            // Créer une clé unique basée sur les IDs des branches pour le cache
            $meta_cache_key = 'branches_meta_' . md5( implode( ',', $branch_ids ) );
            $meta_query = wp_cache_get( $meta_cache_key, 'fand_pickup' );

            if ( false === $meta_query ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
                $meta_query = $wpdb->get_results( $wpdb->prepare( $sql_meta, ...$branch_ids ), ARRAY_A );
                
                // On met en cache pour 1 heure
                wp_cache_set( $meta_cache_key, $meta_query, 'fand_pickup', 3600 );
            }
            
            $offers_pickup_map = [];
            foreach ($meta_query as $m) { 
                $offers_pickup_map[$m['branch_id']] = $m['meta_value']; 
            }

            // Récupération des horaires d'ouverture personnalisés
            // 1. Préparation de la requête SQL dans une variable distincte
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $sql_hours = "SELECT branch_id, day_of_week, open_time, close_time, is_closed 
                          FROM {$wpdb->prefix}fand_wcfm_pickup_hours 
                          WHERE branch_id IN ($placeholders)";

            // 2. On place le commentaire d'ignorance juste avant l'exécution
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            // Création d'une clé de cache unique basée sur la liste des IDs demandés
            $hours_cache_key = 'batch_hours_' . md5( implode( ',', $branch_ids ) );

            // Tentative de récupération depuis le cache
            $hours = wp_cache_get( $hours_cache_key, 'fand_pickup' );

            if ( false === $hours ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
                $hours = $wpdb->get_results( $wpdb->prepare( $sql_hours, ...$branch_ids ), ARRAY_A );
                
                // Mise en cache pour 1 heure (3600 secondes)
                wp_cache_set( $hours_cache_key, $hours, 'fand_pickup', 3600 );
            }
            
            $hours_by_branch = [];
            foreach ($hours as $h) { 
                $hours_by_branch[$h['branch_id']][$h['day_of_week']][] = $h; 
            }

            // --- FILTRAGE ET CONSTRUCTION DES MARKERS ---
            $pickup_only_branches = [];
            foreach ($branches as $branch) {
                // On ne garde que les branches qui acceptent le pickup
                if (empty($offers_pickup_map[$branch['ID']]) || $offers_pickup_map[$branch['ID']] != '1') continue;

                $pickup_only_branches[] = $branch;
                $lat = $branch['latitude'] ?? '';
                $lng = $branch['longitude'] ?? '';

                if (!empty($lat) && !empty($lng)) {
                    $branch_name = $branch['name'] ?? 'Pickup';
                    $branch_slug = sanitize_title($branch_name); 
                    $location_url = home_url('/pickup/emplacement/' . esc_attr($branch_slug) . '/');

                    $markers[] = [
                        'branch_id'     => $branch['ID'],
                        'category'      => $assigned_category,
                        'vendor_name'   => $vendor->display_name,
                        'branch_name'   => $branch_name,
                        'address'       => $branch['map_address'] ?? '',
                        'country'       => $branch['country'] ?? '',
                        'lat'           => floatval($lat),
                        'lng'           => floatval($lng),
                        'store_url'     => $location_url,
                        'opening_hours' => $hours_by_branch[$branch['ID']] ?? []
                    ];
                }
            }
      
            // Stockage des données vendeurs pour la liste latérale
            if (!empty($pickup_only_branches)) {
                $vendors_data[] = [
                    'vendor'   => $vendor, 
                    'vendor_email' =>$vendor_email,
                    'vendor_phone' =>$vendor_phone,
                    'branches' => $pickup_only_branches,
                    'group_id' => $assigned_category,
                ];
            }
        }

        // --- 3. Retour des données ---
        return [
            'countries'        => $countries,
            'categories'       => $all_categories_list, // Ta liste propre venant du backoffice
            'states'           => [], // À remplir si besoin
            'default_location' => $default_location,
            'default_country'  => $default_country,
            'default_state'    => '',
            'markers'          => $markers, 
            'vendors_data'     => $vendors_data,
        ];
    }
}
