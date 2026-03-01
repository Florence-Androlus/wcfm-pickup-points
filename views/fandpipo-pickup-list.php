<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if (defined('DOING_AJAX') && DOING_AJAX) {
    check_ajax_referer('fandpipo_filter_nonce', 'security');
}
$fandpipo_vendors_data = $data['fandpipo_vendors_data'] ?? [];

// 1. On prépare la variable une seule fois de manière sécurisée
$fandpipo_current_orderby = isset($_GET['fandpipo_pickup_orderby']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_orderby'])) : 'newness_asc';
$fandpipo_current_day    = isset($_GET['fandpipo_pickup_day']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_day'])) : '';
$fandpipo_current_status = isset($_GET['fandpipo_pickup_status']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_status'])) : '';
$fandpipo_selected_category   = isset($_GET['fandpipo_category']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_category'])) : '';
$fandpipo_current_lat  = isset($_GET['wcfmmp_radius_lat']) ? sanitize_text_field(wp_unslash($_GET['wcfmmp_radius_lat'])) : '';
$fandpipo_current_lng  = isset($_GET['wcfmmp_radius_lng']) ? sanitize_text_field(wp_unslash($_GET['wcfmmp_radius_lng'])) : '';
$fandpipo_current_addr = isset($_GET['wcfmmp_radius_addr']) ? sanitize_text_field(wp_unslash($_GET['wcfmmp_radius_addr'])) : '';
$fandpipo_current_range = isset($_GET['wcfmmp_radius_range']) ? intval(wp_unslash($_GET['wcfmmp_radius_range'])) : 50;?>

<div id="wcfmmp-stores-wrap-holder" class="rgt right_side right_side_full">
    <div id="wcfmmp-stores-wrap">
        <div class="wcfmmp-stores-content-holder">
            <div class="wcfmmp-stores-content">

            <div class="wcfmmp-store-lists-sorting">
                <form class="wcfm-woocommerce-ordering" action="" method="GET">
                    <input type="hidden" name="wcfmmp_radius_lat" id="pickup-lat" value="<?php echo esc_attr($fandpipo_current_lat); ?>">
                    <input type="hidden" name="wcfmmp_radius_lng" id="pickup-lng" value="<?php echo esc_attr($fandpipo_current_lng); ?>">
                    <input type="hidden" name="wcfmmp_radius_addr" value="<?php echo esc_attr($fandpipo_current_addr); ?>">
                    <input type="hidden" name="wcfmmp_radius_range" value="<?php echo esc_attr($fandpipo_current_range); ?>">
                    <input type="hidden" name="fandpipo_category" value="<?php echo esc_attr($fandpipo_selected_category); ?>">
                    <input type="hidden" name="fandpipo_pickup_search" value="<?php echo isset($_GET['fandpipo_pickup_search']) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['fandpipo_pickup_search'] ) ) ) : ''; ?>">

                    <select id="wcfmmp_pickup_store_orderby" name="fandpipo_pickup_orderby" class="orderby" onchange="this.form.submit()">
                        <option value="newness_asc" <?php selected($fandpipo_current_orderby, 'newness_asc'); ?>>
                            Trier plus vieux au plus récent
                        </option>
                        <option value="newness_desc" <?php selected($fandpipo_current_orderby, 'newness_desc'); ?>>
                            Trier du plus récent au plus vieux
                        </option>
                        <option value="alphabetical_asc" <?php selected($fandpipo_current_orderby, 'alphabetical_asc'); ?>>
                            Alphabétique : A → Z
                        </option>
                        <option value="alphabetical_desc" <?php selected($fandpipo_current_orderby, 'alphabetical_desc'); ?>>
                            Alphabétique : Z → A
                        </option>
                    </select>

                    <select id="wcfmmp_pickup_store_day" name="fandpipo_pickup_day" class="orderby" onchange="this.form.submit()">
                        <option value="">Tous les jours</option>
                        <option value="0" <?php selected($fandpipo_current_day, '0'); ?>>Lundi</option>
                        <option value="1" <?php selected($fandpipo_current_day, '1'); ?>>Mardi</option>
                        <option value="2" <?php selected($fandpipo_current_day, '2'); ?>>Mercredi</option>
                        <option value="3" <?php selected($fandpipo_current_day, '3'); ?>>Jeudi</option>
                        <option value="4" <?php selected($fandpipo_current_day, '4'); ?>>Vendredi</option>
                        <option value="5" <?php selected($fandpipo_current_day, '5'); ?>>Samedi</option>
                        <option value="6" <?php selected($fandpipo_current_day, '6'); ?>>Dimanche</option>
                    </select>

                    <select id="wcfmmp_pickup_store_status" name="fandpipo_pickup_status" class="orderby" onchange="this.form.submit()">
                        <option value="" <?php selected($fandpipo_current_status, ''); ?>>Tout</option>
                        <option value="open" <?php selected($fandpipo_current_status, 'open'); ?>>Ouvert</option>
                        <option value="closed" <?php selected($fandpipo_current_status, 'closed'); ?>>Fermé</option>
                    </select>

                    <?php
                        $fandpipo_required_params = ['page', 'wcfm_screen', 'tab'];

                        foreach ( $fandpipo_required_params as $fandpipo_param ) {
                            if ( ! empty( $_GET[ $fandpipo_param ] ) ) {
                                echo '<input type="hidden" name="' . esc_attr( $fandpipo_param ) . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET[ $fandpipo_param ] ) ) ) . '">';
                            }
                        }
                    ?>
                </form>

                <?php
                    global $wpdb;
                    // 1. Préparer les horaires
                    // Définition des paramètres de cache
                    $fandpipo_cache_key   = 'fand_all_pickup_hours';
                    $fandpipo_cache_group = 'fand_pickup';

                    // 2. Tentative de récupération depuis le cache
                    $fandpipo_all_hours = wp_cache_get( $fandpipo_cache_key, $fandpipo_cache_group );

                    if ( false === $fandpipo_all_hours ) {
                        // 3. Si pas en cache, on exécute la requête SQL
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $fandpipo_all_hours = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}fand_wcfm_pickup_hours", ARRAY_A );
                        
                        // 4. On stocke en cache pour 12 heures (43200 secondes) car les horaires changent peu
                        wp_cache_set( $fandpipo_cache_key, $fandpipo_all_hours, $fandpipo_cache_group, 43200 );
                    }
                    
                    $fandpipo_branch_hours = [];
                    foreach ($fandpipo_all_hours as $fandpipo_h) {
                        $fandpipo_branch_hours[$fandpipo_h['branch_id']][$fandpipo_h['day_of_week']][] = $fandpipo_h;
                    }

                    // 2. Récupérer les filtres de manière sécurisée
                    $fandpipo_selected_day    = isset($_GET['fandpipo_pickup_day']) && $_GET['fandpipo_pickup_day'] !== '' ? intval($_GET['fandpipo_pickup_day']) : null;
                    $fandpipo_selected_status = isset($_GET['fandpipo_pickup_status']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_status'])) : '';
                    $fandpipo_orderby        = isset($_GET['fandpipo_pickup_orderby']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_orderby'])) : 'newness_desc';
                    // phpcs:enable WordPress.Security.NonceVerification.Recommended

                    // 3. MISE À PLAT pour le TRI
                    $fandpipo_flat_list = [];

                    if ( ! empty( $fandpipo_vendors_data ) ) {
                        foreach ( $fandpipo_vendors_data as $fandpipo_v_data ) {
                            if ( ! isset( $fandpipo_v_data['branches'] ) ) continue;

                            foreach ( $fandpipo_v_data['branches'] as $fandpipo_branch ) {
                                // On s'assure que les données vendeur remontent bien dans chaque branche
                                $fandpipo_branch['vendor_data_node'] = $fandpipo_v_data['vendor'] ?? null;
                                $fandpipo_branch['vendor_email']     = $fandpipo_v_data['vendor_email'] ?? '';
                                $fandpipo_branch['vendor_phone']     = $fandpipo_v_data['vendor_phone'] ?? '';
                                $fandpipo_branch['fandpipo_category']= $fandpipo_v_data['group_id'] ?? ''; // On récupère la catégorie
                                $fandpipo_branch['rating_avg']       = $fandpipo_v_data['rating_avg'] ?? 0;
                                $fandpipo_branch['rating_count']     = $fandpipo_v_data['rating_count'] ?? 0;
                                $fandpipo_flat_list[] = $fandpipo_branch;
                            }
                        }
                    }

                    // 4. LE TRI FONCTIONNEL
                    usort($fandpipo_flat_list, function($fand_a,  $fand_b) use ($fandpipo_orderby) {
                        $nameA = $fand_a['branch_name'] ?? $fand_a['name'] ?? '';
                        $nameB =  $fand_b['branch_name'] ??  $fand_b['name'] ?? '';
                        if ($fandpipo_orderby === 'alphabetical_asc') return strcmp($nameA, $nameB);
                        if ($fandpipo_orderby === 'alphabetical_desc') return strcmp($nameB, $nameA);
                        if ($fandpipo_orderby === 'newness_asc') return $fand_a['ID'] -  $fand_b['ID'];
                        if ($fandpipo_orderby === 'newness_desc') return  $fand_b['ID'] - $fand_a['ID'];
                        return 0;
                    });

                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $fandpipo_selected_country = isset($_GET['fandpipo_country']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_country'])) : 'FR';

                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $fandpipo_search_query = isset($_GET['fandpipo_pickup_search']) ? strtolower(sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_search']))) : '';
                ?>

                <div class="spacer"></div>

            </div> <div class="wcfm-clearfix"></div>

            <ul class="wcfmmp-store-wrap">
                <?php
                $fandpipo_displayed_count = 0;

                foreach ($fandpipo_flat_list as  $fandpipo_branch):
                    $fandpipo_vendor =  $fandpipo_branch['vendor_data_node'];
                    $fandpipo_branch_name = strtolower( $fandpipo_branch['branch_name'] ??  $fandpipo_branch['name'] ?? '');
                    $fandpipo_vendor_name = strtolower($fandpipo_vendor->display_name ?? '');
                    // Mise à jour du lien "Visiter le Magasin"
                    // NOUVELLE URL : /pickup/emplacement/mon-emplacement-agreable/
                    $fandpipo_location_url = home_url( '/pickup/emplacement/' . sanitize_title(  $fandpipo_branch_name ) . '/' );

                    // --- FILTRAGE PAR RECHERCHE (NOM) ---
                    if (!empty($fandpipo_search_query)) {
                        // On cherche si le texte est dans le nom de la branche OU le nom du vendeur
                        $fandpipo_match_branch = strpos( $fandpipo_branch_name, $fandpipo_search_query) !== false;
                        $fandpipo_match_vendor = strpos($fandpipo_vendor_name, $fandpipo_search_query) !== false;
                        if (!$fandpipo_match_branch && !$fandpipo_match_vendor) {
                            continue; // Si aucune correspondance, on passe à la suivante
                        }
                    }

                    // On récupère le pays de la branche
                     $fandpipo_branch_country =  $fandpipo_branch['fandpipo_country'] ?? ''; 

                    // FILTRE PAYS : Si un pays est sélectionné, on ne garde que celui-là
                    // Si la branche n'a pas de pays, on tente de récupérer celui du vendeur
                    if (empty( $fandpipo_branch_country)) {
                        $fandpipo_vendor_data = get_user_meta($fandpipo_vendor->ID, 'wcfmmp_profile_settings', true);
                         $fandpipo_branch_country = $fandpipo_vendor_data['address']['country'] ?? '';
                    }

                    // Ensuite on applique le filtre seulement si demandé
                    if (!empty($fandpipo_selected_country) && strtoupper( $fandpipo_branch_country) !== strtoupper($fandpipo_selected_country)) {
                        continue;
                    }

                    // --- FILTRAGE category---
                    if (!empty($fandpipo_selected_category)) {
                        $fandpipo_valeur_boutique = $fandpipo_branch['fandpipo_category'] ?? '';

                        if (is_array($fandpipo_valeur_boutique)) {
                            if (!in_array($fandpipo_selected_category, $fandpipo_valeur_boutique)) {
                                continue;
                            }
                        } else {
                            if (strval($fandpipo_valeur_boutique) !== strval($fandpipo_selected_category)) {
                                continue;
                            }
                        }
                    }

                    $fandpipo_branch_id =  $fandpipo_branch['ID'];

                    // --- LOGIQUE HORAIRES & OUVERTURE ---
                    $fandpipo_now = current_time('H:i:s'); // Heure locale WordPress
                    $fandpipo_php_day_index = (int) current_time( 'w' );
                    $fandpipo_current_real_day = ($fandpipo_php_day_index == 0) ? 6 : $fandpipo_php_day_index - 1;

                    // A. Logique pour le FILTRE (Ce qui détermine si la boutique apparaît dans la liste)
                    $fandpipo_check_day = ($fandpipo_selected_day !== null) ? $fandpipo_selected_day : $fandpipo_current_real_day;
                    $fandpipo_filter_hours = $fandpipo_branch_hours[ $fandpipo_branch_id][$fandpipo_check_day] ?? [];
                    $fandpipo_is_open_filter_day = false; 

                    foreach ($fandpipo_filter_hours as $fandpipo_h) {
                        if (!isset($fandpipo_h['is_closed']) || $fandpipo_h['is_closed'] != '1') {
                            // Optionnel : Si l'utilisateur a filtré sur "Ouvert" ET qu'il regarde le jour J, 
                            // on peut aussi vérifier l'heure pour être ultra précis dans le filtre.
                            $fandpipo_is_open_filter_day = true; 
                            break;
                        }
                    }

                    // B. Logique pour le CERCLE (Le statut visuel temps réel, toujours basé sur MAINTENANT)
                    $fandpipo_is_currently_open = false;
                    $fandpipo_real_time_hours = $fandpipo_branch_hours[ $fandpipo_branch_id][$fandpipo_current_real_day] ?? [];

                    foreach ($fandpipo_real_time_hours as $fandpipo_h) {
                        if (!isset($fandpipo_h['is_closed']) || $fandpipo_h['is_closed'] != '1') {
                            // strtotime transforme "08:00" en timestamp pour une comparaison fiable
                            $fandpipo_start = $fandpipo_h['open_time'];
                            $fandpipo_end   = $fandpipo_h['close_time'];
                            
                            if ($fandpipo_now >= $fandpipo_start && $fandpipo_now <= $fandpipo_end) {
                                $fandpipo_is_currently_open = true;
                                break;
                            }
                        }
                    }

                    // --- FILTRAGE DE LA LISTE ---
                    // On utilise $fandpipo_is_open_filter_day car c'est la réponse à "La boutique est-elle ouverte le jour sélectionné ?"
                    if ($fandpipo_selected_status === 'open' && !$fandpipo_is_open_filter_day) continue;
                    if ($fandpipo_selected_status === 'closed' && $fandpipo_is_open_filter_day) continue;

                    $fandpipo_displayed_count++;

                    // Variables standards pour le template

                    $fandpipo_profile_settings = get_user_meta($fandpipo_vendor->ID, 'wcfmmp_profile_settings', true);
                    //Sécuriser l'avatar (on force 0 si c'est null ou vide)
                    $fandpipo_avatar_id = !empty($fandpipo_profile_settings['gravatar']) ? $fandpipo_profile_settings['gravatar'] : 0;
                    $fandpipo_avatar = wp_get_attachment_url($fandpipo_avatar_id);
                    if (!$fandpipo_avatar) { $fandpipo_avatar = FANDPIPO_AVATAR_DEFAULT; }
                    $fandpipo_banner_id = $fandpipo_profile_settings['banner'] ?? 0;
                    $fandpipo_banner_image = $fandpipo_banner_id ? wp_get_attachment_url($fandpipo_banner_id) : FANDPIPO_PLUGIN_URL . 'assets/images/default_banner.jpg';

                    $fandpipo_country_name = isset($countries[ $fandpipo_branch['country']]) ? $countries[ $fandpipo_branch['country']] : 'France';
                    //Sécuriser les métadonnées de branche (on force une chaîne vide si null)
                    $fandpipo_address = ( $fandpipo_branch['postal_code'] ?? '') . ' ' . ( $fandpipo_branch['city'] ?? '');
                    $fandpipo_address = ltrim(trim($fandpipo_address));
                    $fandpipo_email = isset($fandpipo_branch['vendor_email']) ? $fandpipo_branch['vendor_email'] : '';
                    $fandpipo_phone = isset($fandpipo_branch['vendor_phone']) ? $fandpipo_branch['vendor_phone'] : '';
                    $fandpipo_branch_name =  $fandpipo_branch['branch_name'] ??  $fandpipo_branch['name'] ?? 'Emplacement';
                    // Sécuriser l'ID du vendeur
                    $fandpipo_v_id = isset($fandpipo_vendor->ID) ? intval($fandpipo_vendor->ID) : 0;
                    // Sécuriser l'URL de la boutique (on s'assure que $v_id n'est pas 0)
                    $fandpipo_store_url = ($fandpipo_v_id > 0) ? wcfmmp_get_store_url($fandpipo_v_id) : '#';
                    $fandpipo_rating_avg   = $fandpipo_branch['rating_avg'] ?? 0;
                    $fandpipo_rating_count = $fandpipo_branch['rating_count'] ?? 0;

                    include FANDPIPO_PLUGIN_DIR . 'views/fandpipo-pickup-card.php';

                endforeach; ?>

                <div class="wcfm-clearfix"></div>

            </ul>

            <div class="wcfm-clearfix"></div>

            <?php 
            // Vérification APRES la fin de la boucle foreach
            if ( 0 === $fandpipo_displayed_count ) : 
            ?>
                <div class="wcfm-info" style="display: block; clear: both; margin: 20px 0; padding: 15px; background-color: #e7f7ff; border-left: 4px solid #2196f3;">
                    Aucun point de retrait ne correspond à vos critères de recherche.
                </div>
            <?php endif; ?>

            <p class="woocommerce-result-count">
                Montrer <?php echo esc_html( $fandpipo_displayed_count ); ?> résultat<?php echo ($fandpipo_displayed_count > 1 ? 's' : ''); ?>
            </p>

            </div></div></div><div class="spacer"></div>

</div>