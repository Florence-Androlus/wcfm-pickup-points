<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<aside class="widget"><div class="sidebar_heading"><h4 class="widget-title">Chercher</h4></div>
    <form role="search" method="get" class="woocommerce-product-search" action="">
        <input type="hidden" id="pickup-lat">
        <input type="hidden" id="pickup-lng">
        <label class="screen-reader-text" for="woocommerce-product-search-field-0">Recherche pour&nbsp;:</label>
        <input type="search" id="woocommerce-product-search-field-0" class="search-field" placeholder="Recherche de produits…" value="" name="s">
        
        <input type="hidden" name="vendor_id_for_redirect" value="<?php echo esc_attr( $fandpipo_vendor_id ); ?>" />
        <input type="hidden" name="force_redirect_product" value="1" />
        
        <button type="submit" value="Recherche">Recherche</button>
    </form>
</aside>

<aside class="widget">
    <div class="sidebar_heading">
        <h4 class="widget-title">Catégories</h4>
    </div>
    <div class="categories_list">
        <label for="category-filter-select" class="screen-reader-text">Filtrer par catégorie</label>
            
            <select id="category-filter-select" onchange="window.location.href = this.value;">

                <?php 
                // 1. Option "Toutes les catégories" (valeur = URL de base sans filtre)
                $fandpipo_all_cat_selected = empty( $fandpipo_current_cat_slug ) ? 'selected="selected"' : '';
                ?>
                <option value="<?php echo esc_url( $fandpipo_base_url_for_filter ); ?>" <?php echo esc_attr( $fandpipo_all_cat_selected ); ?>>
                    Toutes les catégories
                </option>

                <?php
                // Boucle pour chaque catégorie
                if ( ! is_wp_error( $fandpipo_category_terms ) && ! empty( $fandpipo_category_terms ) ) :
                    foreach ( $fandpipo_category_terms as $fandpipo_category ) :
                        $fandpipo_cat_slug = $fandpipo_category->slug;
                        $fandpipo_cat_name = $fandpipo_category->name;

                        // URL de filtrage
                        $fandpipo_category_filter_url = add_query_arg( 'product_cat', $fandpipo_cat_slug, $fandpipo_base_url_for_filter );

                        // Déterminer si option sélectionnée
                        $fandpipo_selected_attr = ( $fandpipo_current_cat_slug === $fandpipo_cat_slug ) ? 'selected="selected"' : '';
                        ?>
                        <option value="<?php echo esc_url( $fandpipo_category_filter_url ); ?>" <?php echo esc_attr( $fandpipo_selected_attr ); ?>>
                            <?php echo esc_html( $fandpipo_cat_name ); ?>
                        </option>
                    <?php
                    endforeach;
                endif;
                ?>
            </select>
    </div>
</aside>		

<aside class="widget">
    <div class="sidebar_heading"><h4 class="widget-title">Emplacement du Pickup</h4></div>
   <div id="pickup-map" style="height: 383px;">
    </div>
</aside>

<?php
    global $wpdb;
 
    // Récupérer tous les horaires pour cette branche
    // 1. Définition des paramètres de cache pour cette branche spécifique
    $fandpipo_cache_key   = 'branch_hours_' . intval( $fandpipo_branch_id );
    $fandpipo_cache_group = 'fand_pickup';

    // 2. Tentative de récupération depuis le cache
    $fandpipo_raw_hours = wp_cache_get( $fandpipo_cache_key, $fandpipo_cache_group );

    if ( false === $fandpipo_raw_hours ) {
        // 3. Si non présent, on exécute la requête SQL préparée
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $fandpipo_raw_hours = $wpdb->get_results( 
            $wpdb->prepare(
                "SELECT day_of_week, open_time, close_time, is_closed FROM $wpdb->prefix.fand_wcfm_pickup_hours WHERE branch_id = %d",
                intval( $fandpipo_branch_id )
            ), 
            ARRAY_A 
        );

        // 4. On stocke en cache pour 12 heures
        wp_cache_set( $fandpipo_cache_key, $fandpipo_raw_hours, $fandpipo_cache_group, 43200 );
    }

    // Organiser les données pour l'affichage (car il peut y avoir plusieurs plages horaires par jour)
    $fandpipo_branch_hours = array();

    foreach ( $fandpipo_raw_hours as $fandpipo_hour ) {
        $fandpipo_day = $fandpipo_hour['day_of_week'];
       
        if ( ! isset( $fandpipo_branch_hours[$fandpipo_day] ) ) {
            $fandpipo_branch_hours[$fandpipo_day] = array( 'closed' => false, 'periods' => array() );
        }
        
        // Si is_closed est à 1, marquer le jour comme fermé (même si des périodes existent, on priorise le fermé)
        if ( $fandpipo_hour['is_closed'] == 1 ) {
            $fandpipo_branch_hours[$fandpipo_day]['closed'] = true;
        } else {
            // Ajouter la plage horaire
            $fandpipo_branch_hours[$fandpipo_day]['periods'][] = array(
                'open'  => $fandpipo_hour['open_time'],
                'close' => $fandpipo_hour['close_time']
            );
        }
    }

    // Utilisation des données préparées $fandpipo_branch_hours
    if ( ! empty( $fandpipo_branch_hours ) ) {
        // Les jours sont indexés de 0 (Monday) à 6 (Sunday) selon votre description
        $fandpipo_days_map = array(
            0 => 'Monday',
            1 => 'Tuesday',
            2 => 'Wednesday',
            3 => 'Thursday',
            4 => 'Friday',
            5 => 'Saturday',
            6 => 'Sunday',
        );
        ?>
        <aside class="widget">
            <div class="sidebar_heading">
                <h4 class="widget-title">Horaires d'Ouverture</h4>
            </div>
            <div class="store_hours_list">
                <ul style="list-style: none; margin: 0; padding: 0;">
                    <?php
                    // Parcourir tous les jours de la semaine (pour garantir l'ordre)
                    foreach ( $fandpipo_days_map as $fandpipo_day_index => $fandpipo_day_name ) :
                       
                        // Vérifier si nous avons des données pour ce jour
                        $fandpipo_day_data = isset( $fandpipo_branch_hours[$fandpipo_day_index] ) ? $fandpipo_branch_hours[$fandpipo_day_index] : null;
                        
                        $fandpipo_hours_display = '';
                        $fandpipo_css_style = '';
                        
                        if ( $fandpipo_day_data && $fandpipo_day_data['closed'] ) {
                            // Jour marqué comme fermé
                            $fandpipo_hours_display = 'Closed';
                            $fandpipo_css_style = 'color: red; font-weight: bold;';
                        } elseif ( $fandpipo_day_data && ! empty( $fandpipo_day_data['periods'] ) ) {
                            // Afficher les plages horaires (gestion des multiples plages)
                            $fandpipo_periods_text = array();
                            foreach ($fandpipo_day_data['periods'] as $fandpipo_period) {
                                // Formatage simple HH:MM - HH:MM
                                $fandpipo_periods_text[] = substr($fandpipo_period['open'], 0, 5) . ' - ' . substr($fandpipo_period['close'], 0, 5);
                            }
                            $fandpipo_hours_display = implode('<br>', $fandpipo_periods_text); // Afficher les plages sur plusieurs lignes
                        } else {
                            // Pas de données spécifiques (peut être considéré comme fermé si non renseigné)
                            $fandpipo_hours_display = 'Non spécifié / Closed';
                            $fandpipo_css_style = 'color: #888;';
                        }
                        ?>
                        <li style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee;">
                            <span style="font-weight: 600;"><?php echo esc_html($fandpipo_day_name); ?> :</span>
                            <span style="<?php echo esc_attr($fandpipo_css_style); ?> text-align: right;"><?php echo wp_kses($fandpipo_hours_display, array('br' => array())); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
        <?php
    }
?>



