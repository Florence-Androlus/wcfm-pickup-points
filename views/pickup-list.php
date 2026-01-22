<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$fand_vendors_data = $data['vendors_data'] ?? [];

?>

<div id="wcfmmp-stores-wrap-holder" class="rgt right_side right_side_full">
    <div id="wcfmmp-stores-wrap">
        <div class="wcfmmp-stores-content-holder">
            <div class="wcfmmp-stores-content">

            <div class="wcfmmp-store-lists-sorting">
                <form class="wcfm-woocommerce-ordering" action="" method="GET">
                    <input type="hidden" id="pickup-lat">
                    <input type="hidden" id="pickup-lng">

                    <?php
                    // 1. On prépare la variable une seule fois de manière sécurisée
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $fand_current_orderby = isset($_GET['pickup_orderby']) ? sanitize_text_field(wp_unslash($_GET['pickup_orderby'])) : 'newness_asc';
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $fand_current_day    = isset($_GET['pickup_day']) ? sanitize_text_field(wp_unslash($_GET['pickup_day'])) : '';
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $fand_current_status = isset($_GET['pickup_status']) ? sanitize_text_field(wp_unslash($_GET['pickup_status'])) : '';
                    // phpcs:enable WordPress.Security.NonceVerification.Recommended
                    ?>
                    <select id="wcfmmp_pickup_store_orderby" name="pickup_orderby" class="orderby" onchange="this.form.submit()">
                        <option value="newness_asc" <?php selected($fand_current_orderby, 'newness_asc'); ?>>
                            Trier plus vieux au plus récent
                        </option>
                        <option value="newness_desc" <?php selected($fand_current_orderby, 'newness_desc'); ?>>
                            Trier du plus récent au plus vieux
                        </option>
                        <option value="alphabetical_asc" <?php selected($fand_current_orderby, 'alphabetical_asc'); ?>>
                            Alphabétique : A → Z
                        </option>
                        <option value="alphabetical_desc" <?php selected($fand_current_orderby, 'alphabetical_desc'); ?>>
                            Alphabétique : Z → A
                        </option>
                    </select>

                    <select id="wcfmmp_pickup_store_day" name="pickup_day" class="orderby" onchange="this.form.submit()">
                        <option value="">Tous les jours</option>
                        <option value="0" <?php selected($fand_current_day, '0'); ?>>Lundi</option>
                        <option value="1" <?php selected($fand_current_day, '1'); ?>>Mardi</option>
                        <option value="2" <?php selected($fand_current_day, '2'); ?>>Mercredi</option>
                        <option value="3" <?php selected($fand_current_day, '3'); ?>>Jeudi</option>
                        <option value="4" <?php selected($fand_current_day, '4'); ?>>Vendredi</option>
                        <option value="5" <?php selected($fand_current_day, '5'); ?>>Samedi</option>
                        <option value="6" <?php selected($fand_current_day, '6'); ?>>Dimanche</option>
                    </select>

                    <select id="wcfmmp_pickup_store_status" name="pickup_status" class="orderby" onchange="this.form.submit()">
                        <option value="" <?php selected($fand_current_status, ''); ?>>Tout</option>
                        <option value="open" <?php selected($fand_current_status, 'open'); ?>>Ouvert</option>
                        <option value="closed" <?php selected($fand_current_status, 'closed'); ?>>Fermé</option>
                    </select>

                    <?php
                        // On ignore le manque de Nonce car c'est un formulaire GET de filtrage public
                        // phpcs:disable WordPress.Security.NonceVerification.Recommended
                        if(!empty($_GET)){
                            foreach($_GET as $fand_key => $fand_value){
                                // On nettoie systématiquement la clé et la valeur
                                $fand_safe_key = sanitize_text_field(wp_unslash($fand_key));
                                $fand_safe_value = sanitize_text_field(wp_unslash($fand_value));

                                if(in_array($fand_safe_key, ['pickup_orderby', 'pickup_day', 'pickup_status', 'country', 'category', 'pickup_search'])) continue;
                                
                                echo '<input type="hidden" name="' . esc_attr($fand_safe_key) . '" value="' . esc_attr($fand_safe_value) . '">';
                            }
                        }
                    ?>
                </form>

                <?php
                    global $wpdb;
                    // 1. Préparer les horaires
                    // Définition des paramètres de cache
                    $fand_cache_key   = 'fand_all_pickup_hours';
                    $fand_cache_group = 'fand_pickup';

                    // 2. Tentative de récupération depuis le cache
                    $fand_all_hours = wp_cache_get( $fand_cache_key, $fand_cache_group );

                    if ( false === $fand_all_hours ) {
                        // 3. Si pas en cache, on exécute la requête SQL
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $fand_all_hours = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}fand_wcfm_pickup_hours", ARRAY_A );
                        
                        // 4. On stocke en cache pour 12 heures (43200 secondes) car les horaires changent peu
                        wp_cache_set( $fand_cache_key, $fand_all_hours, $fand_cache_group, 43200 );
                    }
                    
                    $fand_branch_hours = [];
                    foreach ($fand_all_hours as $fand_h) {
                        $fand_branch_hours[$fand_h['branch_id']][$fand_h['day_of_week']][] = $fand_h;
                    }

                    // 2. Récupérer les filtres de manière sécurisée
                    $fand_selected_day    = isset($_GET['pickup_day']) && $_GET['pickup_day'] !== '' ? intval($_GET['pickup_day']) : null;
                    $fand_selected_status = isset($_GET['pickup_status']) ? sanitize_text_field(wp_unslash($_GET['pickup_status'])) : '';
                    $fand_orderby        = isset($_GET['pickup_orderby']) ? sanitize_text_field(wp_unslash($_GET['pickup_orderby'])) : 'newness_desc';
                    // phpcs:enable WordPress.Security.NonceVerification.Recommended

                    // 3. MISE À PLAT pour le TRI
                    $fand_flat_list = [];
                    if ( ! empty( $fand_vendors_data ) ) {
                        foreach ( $fand_vendors_data as $fand_v_data ) {
                            if ( ! isset( $fand_v_data['branches'] ) ) continue;

                            foreach ( $fand_v_data['branches'] as $fand_branch ) {
                                // On s'assure que les données vendeur remontent bien dans chaque branche
                                $fand_branch['vendor_data_node'] = $fand_v_data['vendor'] ?? null;
                                $fand_branch['vendor_email']     = $fand_v_data['vendor_email'] ?? '';
                                $fand_branch['vendor_phone']     = $fand_v_data['vendor_phone'] ?? '';
                                $fand_branch['category']         = $fand_v_data['group_id'] ?? ''; // On récupère la catégorie
                                $fand_branch['rating_avg']       = $fand_v_data['rating_avg'] ?? 0;
                                $fand_branch['rating_count']     = $fand_v_data['rating_count'] ?? 0;
                                $fand_flat_list[] = $fand_branch;
                            }
                        }
                    }
                    
                    // 4. LE TRI FONCTIONNEL
                    usort($fand_flat_list, function($fand_a,  $fand_b) use ($fand_orderby) {
                        $nameA = $fand_a['branch_name'] ?? $fand_a['name'] ?? '';
                        $nameB =  $fand_b['branch_name'] ??  $fand_b['name'] ?? '';
                        if ($fand_orderby === 'alphabetical_asc') return strcmp($nameA, $nameB);
                        if ($fand_orderby === 'alphabetical_desc') return strcmp($nameB, $nameA);
                        if ($fand_orderby === 'newness_asc') return $fand_a['ID'] -  $fand_b['ID'];
                        if ($fand_orderby === 'newness_desc') return  $fand_b['ID'] - $fand_a['ID'];
                        return 0;
                    });

                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $fand_selected_country = isset($_GET['country']) ? sanitize_text_field(wp_unslash($_GET['country'])) : 'FR';

                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $fand_search_query = isset($_GET['pickup_search']) ? strtolower(sanitize_text_field(wp_unslash($_GET['pickup_search']))) : '';
                ?>

                <div class="spacer"></div>

            </div> <div class="wcfm-clearfix"></div>

            <ul class="wcfmmp-store-wrap">
                <?php
                $fand_displayed_count = 0;

                foreach ($fand_flat_list as  $fand_branch):
                    $fand_vendor =  $fand_branch['vendor_data_node'];
                    $fand_branch_name = strtolower( $fand_branch['branch_name'] ??  $fand_branch['name'] ?? '');
                    $fand_vendor_name = strtolower($fand_vendor->display_name ?? '');
                    // Mise à jour du lien "Visiter le Magasin"
                    // NOUVELLE URL : /pickup/emplacement/mon-emplacement-agreable/
                    $fand_location_url = home_url( '/pickup/emplacement/' . esc_attr(sanitize_title(  $fand_branch_name )) . '/' );


                    // --- FILTRAGE PAR RECHERCHE (NOM) ---
                    if (!empty($fand_search_query)) {
                        // On cherche si le texte est dans le nom de la branche OU le nom du vendeur
                        $fand_match_branch = strpos( $fand_branch_name, $fand_search_query) !== false;
                        $fand_match_vendor = strpos($fand_vendor_name, $fand_search_query) !== false;
                        if (!$fand_match_branch && !$fand_match_vendor) {
                            continue; // Si aucune correspondance, on passe à la suivante
                        }
                    }

                    // On récupère le pays de la branche
                     $fand_branch_country =  $fand_branch['country'] ?? ''; 
                    // FILTRE PAYS : Si un pays est sélectionné, on ne garde que celui-là
                    // Si la branche n'a pas de pays, on tente de récupérer celui du vendeur
                    if (empty( $fand_branch_country)) {
                        $fand_vendor_data = get_user_meta($fand_vendor->ID, 'wcfmmp_profile_settings', true);
                         $fand_branch_country = $fand_vendor_data['address']['country'] ?? '';
                    }

                    // Ensuite on applique le filtre seulement si demandé
                    if (!empty($fand_selected_country) && strtoupper( $fand_branch_country) !== strtoupper($fand_selected_country)) {
                        continue;
                    }

                    // --- FILTRAGE ---
                    if (!empty($fand_selected_category)) {
                        // On vérifie si la catégorie choisie est dans notre tableau propre
                        if (!in_array($fand_selected_category,  $fand_branch['category'])) {
                            continue;
                        }
                    }

                     $fand_branch_id =  $fand_branch['ID'];

                    // --- LOGIQUE HORAIRES & OUVERTURE ---
                    $fand_now = current_time('H:i:s'); // Heure locale WordPress
                    $fand_php_day_index = (int) current_time( 'w' );
                    $fand_current_real_day = ($fand_php_day_index == 0) ? 6 : $fand_php_day_index - 1;

                    // A. Logique pour le FILTRE (Ce qui détermine si la boutique apparaît dans la liste)
                    $fand_check_day = ($fand_selected_day !== null) ? $fand_selected_day : $fand_current_real_day;
                    $fand_filter_hours = $fand_branch_hours[ $fand_branch_id][$fand_check_day] ?? [];
                    $fand_is_open_filter_day = false; 

                    foreach ($fand_filter_hours as $fand_h) {
                        if (!isset($fand_h['is_closed']) || $fand_h['is_closed'] != '1') {
                            // Optionnel : Si l'utilisateur a filtré sur "Ouvert" ET qu'il regarde le jour J, 
                            // on peut aussi vérifier l'heure pour être ultra précis dans le filtre.
                            $fand_is_open_filter_day = true; 
                            break;
                        }
                    }

                    // B. Logique pour le CERCLE (Le statut visuel temps réel, toujours basé sur MAINTENANT)
                    $fand_is_currently_open = false;
                    $fand_real_time_hours = $fand_branch_hours[ $fand_branch_id][$fand_current_real_day] ?? [];

                    foreach ($fand_real_time_hours as $fand_h) {
                        if (!isset($fand_h['is_closed']) || $fand_h['is_closed'] != '1') {
                            // strtotime transforme "08:00" en timestamp pour une comparaison fiable
                            $fand_start = $fand_h['open_time'];
                            $fand_end   = $fand_h['close_time'];
                            
                            if ($fand_now >= $fand_start && $fand_now <= $fand_end) {
                                $fand_is_currently_open = true;
                                break;
                            }
                        }
                    }

                    // --- FILTRAGE DE LA LISTE ---
                    // On utilise $fand_is_open_filter_day car c'est la réponse à "La boutique est-elle ouverte le jour sélectionné ?"
                    if ($fand_selected_status === 'open' && !$fand_is_open_filter_day) continue;
                    if ($fand_selected_status === 'closed' && $fand_is_open_filter_day) continue;

                    $fand_displayed_count++;

                    // Variables standards pour le template

                    $fand_profile_settings = get_user_meta($fand_vendor->ID, 'wcfmmp_profile_settings', true);
                    //Sécuriser l'avatar (on force 0 si c'est null ou vide)
                    $fand_avatar_id = !empty($fand_profile_settings['gravatar']) ? $fand_profile_settings['gravatar'] : 0;
                    $fand_avatar = wp_get_attachment_url($fand_avatar_id);
                    if (!$fand_avatar) { $fand_avatar = 'URL_PAR_DEFAUT'; }
                    $fand_banner_id = $fand_profile_settings['banner'] ?? 0;
                    $fand_banner_image =  $fand_banner_id ? wp_get_attachment_url( $fand_banner_id) : plugins_url('wc-multivendor-marketplace/assets/images/default_banner.jpg');
                    $fand_country_name = isset($countries[ $fand_branch['country']]) ? $countries[ $fand_branch['country']] : 'France';
                    //Sécuriser les métadonnées de branche (on force une chaîne vide si null)
                    $fand_address = ( $fand_branch['postal_code'] ?? '') . ' ' . ( $fand_branch['city'] ?? '');
                    $fand_address = ltrim(trim($fand_address));
                    $fand_email = isset($fand_branch['vendor_email']) ? $fand_branch['vendor_email'] : '';
                    $fand_phone = isset($fand_branch['vendor_phone']) ? $fand_branch['vendor_phone'] : '';
                    $fand_branch_name =  $fand_branch['branch_name'] ??  $fand_branch['name'] ?? 'Emplacement';
                    // Sécuriser l'ID du vendeur
                    $fand_v_id = isset($fand_vendor->ID) ? intval($fand_vendor->ID) : 0;
                    // Sécuriser l'URL de la boutique (on s'assure que $v_id n'est pas 0)
                    $fand_store_url = ($fand_v_id > 0) ? wcfmmp_get_store_url($fand_v_id) : '#';
                    error_log('$fand_branch : '.print_r($fand_branch,true));
                    $fand_rating_avg   = $fand_branch['rating_avg'] ?? 0;
                    $fand_rating_count = $fand_branch['rating_count'] ?? 0;
                    
                    include FAND_PICKUP_POINTS_ULTIMATE_PLUGIN_DIR . 'views/pickup-card.php';

                endforeach; ?>

                <div class="wcfm-clearfix"></div>

            </ul>

            <p class="woocommerce-result-count">
                Montrer <?php echo esc_html( $fand_displayed_count ); ?> résultat<?php echo ($fand_displayed_count > 1 ? 's' : ''); ?>
            </p>

            </div></div></div><div class="spacer"></div>

</div>