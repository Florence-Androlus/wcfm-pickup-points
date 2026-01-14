<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
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
                    $current_orderby = isset($_GET['pickup_orderby']) ? sanitize_text_field(wp_unslash($_GET['pickup_orderby'])) : 'newness_asc';
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $current_day    = isset($_GET['pickup_day']) ? sanitize_text_field(wp_unslash($_GET['pickup_day'])) : '';
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $current_status = isset($_GET['pickup_status']) ? sanitize_text_field(wp_unslash($_GET['pickup_status'])) : '';
                    // phpcs:enable WordPress.Security.NonceVerification.Recommended
                    ?>
                    <select id="wcfmmp_pickup_store_orderby" name="pickup_orderby" class="orderby" onchange="this.form.submit()">
                        <option value="newness_asc" <?php selected($current_orderby, 'newness_asc'); ?>>
                            Trier plus vieux au plus récent
                        </option>
                        <option value="newness_desc" <?php selected($current_orderby, 'newness_desc'); ?>>
                            Trier du plus récent au plus vieux
                        </option>
                        <option value="alphabetical_asc" <?php selected($current_orderby, 'alphabetical_asc'); ?>>
                            Alphabétique : A → Z
                        </option>
                        <option value="alphabetical_desc" <?php selected($current_orderby, 'alphabetical_desc'); ?>>
                            Alphabétique : Z → A
                        </option>
                    </select>

                    <select id="wcfmmp_pickup_store_day" name="pickup_day" class="orderby" onchange="this.form.submit()">
                        <option value="">Tous les jours</option>
                        <option value="0" <?php selected($current_day, '0'); ?>>Lundi</option>
                        <option value="1" <?php selected($current_day, '1'); ?>>Mardi</option>
                        <option value="2" <?php selected($current_day, '2'); ?>>Mercredi</option>
                        <option value="3" <?php selected($current_day, '3'); ?>>Jeudi</option>
                        <option value="4" <?php selected($current_day, '4'); ?>>Vendredi</option>
                        <option value="5" <?php selected($current_day, '5'); ?>>Samedi</option>
                        <option value="6" <?php selected($current_day, '6'); ?>>Dimanche</option>
                    </select>

                    <select id="wcfmmp_pickup_store_status" name="pickup_status" class="orderby" onchange="this.form.submit()">
                        <option value="" <?php selected($current_status, ''); ?>>Tout</option>
                        <option value="open" <?php selected($current_status, 'open'); ?>>Ouvert</option>
                        <option value="closed" <?php selected($current_status, 'closed'); ?>>Fermé</option>
                    </select>

<?php
                        // On ignore le manque de Nonce car c'est un formulaire GET de filtrage public
                        // phpcs:disable WordPress.Security.NonceVerification.Recommended
                        if(!empty($_GET)){
                            foreach($_GET as $key => $value){
                                // On nettoie systématiquement la clé et la valeur
                                $safe_key = sanitize_text_field(wp_unslash($key));
                                $safe_value = sanitize_text_field(wp_unslash($value));

                                if(in_array($safe_key, ['pickup_orderby', 'pickup_day', 'pickup_status', 'country', 'category', 'pickup_search'])) continue;
                                
                                echo '<input type="hidden" name="' . esc_attr($safe_key) . '" value="' . esc_attr($safe_value) . '">';
                            }
                        }
                    ?>
                </form>

                <?php
                    global $wpdb;
                    // 1. Préparer les horaires
                    // Définition des paramètres de cache
                    $cache_key   = 'fand_all_pickup_hours';
                    $cache_group = 'fand_pickup';

                    // 2. Tentative de récupération depuis le cache
                    $all_hours = wp_cache_get( $cache_key, $cache_group );

                    if ( false === $all_hours ) {
                        // 3. Si pas en cache, on exécute la requête SQL
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $all_hours = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}fand_wcfm_pickup_hours", ARRAY_A );
                        
                        // 4. On stocke en cache pour 12 heures (43200 secondes) car les horaires changent peu
                        wp_cache_set( $cache_key, $all_hours, $cache_group, 43200 );
                    }
                    
                    $branch_hours = [];
                    foreach ($all_hours as $h) {
                        $branch_hours[$h['branch_id']][$h['day_of_week']][] = $h;
                    }

                    // 2. Récupérer les filtres de manière sécurisée
                    $selected_day    = isset($_GET['pickup_day']) && $_GET['pickup_day'] !== '' ? intval($_GET['pickup_day']) : null;
                    $selected_status = isset($_GET['pickup_status']) ? sanitize_text_field(wp_unslash($_GET['pickup_status'])) : '';
                    $orderby         = isset($_GET['pickup_orderby']) ? sanitize_text_field(wp_unslash($_GET['pickup_orderby'])) : 'newness_desc';
                    // phpcs:enable WordPress.Security.NonceVerification.Recommended

                    // 3. MISE À PLAT pour le TRI
                    $flat_list = [];
                    foreach ($vendors_data as $v_data) {
                        foreach ($v_data['branches'] as $branch) {
                            $branch['vendor_data_node'] = $v_data['vendor']; 
                            $branch['vendor_email'] = $v_data['vendor_email'];
                            $branch['vendor_phone'] = $v_data['vendor_phone'];
                            $flat_list[] = $branch;
                        }
                    }
                    
                    // 4. LE TRI FONCTIONNEL
                    usort($flat_list, function($a, $b) use ($orderby) {
                        $nameA = $a['branch_name'] ?? $a['name'] ?? '';
                        $nameB = $b['branch_name'] ?? $b['name'] ?? '';
                        if ($orderby === 'alphabetical_asc') return strcmp($nameA, $nameB);
                        if ($orderby === 'alphabetical_desc') return strcmp($nameB, $nameA);
                        if ($orderby === 'newness_asc') return $a['ID'] - $b['ID'];
                        if ($orderby === 'newness_desc') return $b['ID'] - $a['ID'];
                        return 0;
                    });

                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $selected_country = isset($_GET['country']) ? sanitize_text_field(wp_unslash($_GET['country'])) : 'FR';

                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $search_query = isset($_GET['pickup_search']) ? strtolower(sanitize_text_field(wp_unslash($_GET['pickup_search']))) : '';
                ?>

                <div class="spacer"></div>

            </div> <div class="wcfm-clearfix"></div>

            <ul class="wcfmmp-store-wrap">
                <?php
                $displayed_count = 0;

                foreach ($flat_list as $branch):
                    $vendor = $branch['vendor_data_node'];
                    $branch_name = strtolower($branch['branch_name'] ?? $branch['name'] ?? '');
                    $vendor_name = strtolower($vendor->display_name ?? '');

                    // Mise à jour du lien "Visiter le Magasin"
                    // NOUVELLE URL : /pickup/emplacement/mon-emplacement-agreable/
                    $location_url = home_url( '/pickup/emplacement/' . esc_attr(sanitize_title( $branch_name )) . '/' );


                    // --- FILTRAGE PAR RECHERCHE (NOM) ---
                    if (!empty($search_query)) {
                        // On cherche si le texte est dans le nom de la branche OU le nom du vendeur
                        $match_branch = strpos($branch_name, $search_query) !== false;
                        $match_vendor = strpos($vendor_name, $search_query) !== false;
                        if (!$match_branch && !$match_vendor) {
                            continue; // Si aucune correspondance, on passe à la suivante
                        }
                    }

                    // On récupère le pays de la branche
                    $branch_country = $branch['country'] ?? ''; 
                    // FILTRE PAYS : Si un pays est sélectionné, on ne garde que celui-là
                    // Si la branche n'a pas de pays, on tente de récupérer celui du vendeur
                    if (empty($branch_country)) {
                        $vendor_data = get_user_meta($vendor->ID, 'wcfmmp_profile_settings', true);
                        $branch_country = $vendor_data['address']['country'] ?? '';
                    }

                    // Ensuite on applique le filtre seulement si demandé
                    if (!empty($selected_country) && strtoupper($branch_country) !== strtoupper($selected_country)) {
                        continue;
                    }

                    // --- FILTRAGE ---
                    if (!empty($selected_category)) {
                        // On vérifie si la catégorie choisie est dans notre tableau propre
                        if (!in_array($selected_category, $branch['category'])) {
                            continue;
                        }
                    }

                    $branch_id = $branch['ID'];

                    // --- LOGIQUE HORAIRES & OUVERTURE ---
                    $now = current_time('H:i:s'); // Heure locale WordPress
                    $php_day_index = (int) current_time( 'w' );
                    $current_real_day = ($php_day_index == 0) ? 6 : $php_day_index - 1;

                    // A. Logique pour le FILTRE (Ce qui détermine si la boutique apparaît dans la liste)
                    $check_day = ($selected_day !== null) ? $selected_day : $current_real_day;
                    $filter_hours = $branch_hours[$branch_id][$check_day] ?? [];
                    $is_open_filter_day = false; 

                    foreach ($filter_hours as $h) {
                        if (!isset($h['is_closed']) || $h['is_closed'] != '1') {
                            // Optionnel : Si l'utilisateur a filtré sur "Ouvert" ET qu'il regarde le jour J, 
                            // on peut aussi vérifier l'heure pour être ultra précis dans le filtre.
                            $is_open_filter_day = true; 
                            break;
                        }
                    }

                    // B. Logique pour le CERCLE (Le statut visuel temps réel, toujours basé sur MAINTENANT)
                    $is_currently_open = false;
                    $real_time_hours = $branch_hours[$branch_id][$current_real_day] ?? [];

                    foreach ($real_time_hours as $h) {
                        if (!isset($h['is_closed']) || $h['is_closed'] != '1') {
                            // strtotime transforme "08:00" en timestamp pour une comparaison fiable
                            $start = $h['open_time'];
                            $end   = $h['close_time'];
                            
                            if ($now >= $start && $now <= $end) {
                                $is_currently_open = true;
                                break;
                            }
                        }
                    }

                    // --- FILTRAGE DE LA LISTE ---
                    // On utilise $is_open_filter_day car c'est la réponse à "La boutique est-elle ouverte le jour sélectionné ?"
                    if ($selected_status === 'open' && !$is_open_filter_day) continue;
                    if ($selected_status === 'closed' && $is_open_filter_day) continue;

                    $displayed_count++;

                    // Variables standards pour le template

                    $profile_settings = get_user_meta($vendor->ID, 'wcfmmp_profile_settings', true);
                    //Sécuriser l'avatar (on force 0 si c'est null ou vide)
                    $avatar_id = !empty($profile_settings['gravatar']) ? $profile_settings['gravatar'] : 0;
                    $avatar = wp_get_attachment_url($avatar_id);
                    if (!$avatar) { $avatar = 'URL_PAR_DEFAUT'; }
                    $banner_id = $profile_settings['banner'] ?? 0;
                    $banner_image = $banner_id ? wp_get_attachment_url($banner_id) : plugins_url('wc-multivendor-marketplace/assets/images/default_banner.jpg');
                    $country_name = isset($countries[$branch['country']]) ? $countries[$branch['country']] : 'France';
                    //Sécuriser les métadonnées de branche (on force une chaîne vide si null)
                    $address = ($branch['postal_code'] ?? '') . ' ' . ($branch['city'] ?? '');
                    $address = ltrim(trim($address));
                    $email = $branch['vendor_email'];
                    $phone = $branch['vendor_phone'];
                    $branch_name = $branch['branch_name'] ?? $branch['name'] ?? 'Emplacement';
                    // Sécuriser l'ID du vendeur
                    $v_id = isset($vendor->ID) ? intval($vendor->ID) : 0;
                    // Sécuriser l'URL de la boutique (on s'assure que $v_id n'est pas 0)
                    $store_url = ($v_id > 0) ? wcfmmp_get_store_url($v_id) : '#';
                    
                    include FAND_PICKUP_PLUGIN_DIR . 'views/pickup-card.php';

                endforeach; ?>

                <div class="wcfm-clearfix"></div>

            </ul>

            <p class="woocommerce-result-count">
                Montrer <?php echo esc_html( $displayed_count ); ?> résultat<?php echo ($displayed_count > 1 ? 's' : ''); ?>
            </p>

            </div></div></div><div class="spacer"></div>

</div>