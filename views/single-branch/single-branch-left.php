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
        
        <input type="hidden" name="vendor_id_for_redirect" value="<?php echo esc_attr( $fand_vendor_id ); ?>" />
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
                $fand_all_cat_selected = empty( $fand_current_cat_slug ) ? 'selected="selected"' : '';
                ?>
                <option value="<?php echo esc_url( $fand_base_url_for_filter ); ?>" <?php echo esc_attr( $fand_all_cat_selected ); ?>>
                    Toutes les catégories
                </option>

                <?php
                // Boucle pour chaque catégorie
                if ( ! is_wp_error( $fand_category_terms ) && ! empty( $fand_category_terms ) ) :
                    foreach ( $fand_category_terms as $fand_category ) :
                        $fand_cat_slug = $fand_category->slug;
                        $fand_cat_name = $fand_category->name;

                        // URL de filtrage
                        $fand_category_filter_url = add_query_arg( 'product_cat', $fand_cat_slug, $fand_base_url_for_filter );

                        // Déterminer si option sélectionnée
                        $fand_selected_attr = ( $fand_current_cat_slug === $fand_cat_slug ) ? 'selected="selected"' : '';
                        ?>
                        <option value="<?php echo esc_url( $fand_category_filter_url ); ?>" <?php echo esc_attr( $fand_selected_attr ); ?>>
                            <?php echo esc_html( $fand_cat_name ); ?>
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
    $fand_cache_key   = 'branch_hours_' . intval( $fand_branch_id );
    $fand_cache_group = 'fand_pickup';

    // 2. Tentative de récupération depuis le cache
    $fand_raw_hours = wp_cache_get( $fand_cache_key, $fand_cache_group );

    if ( false === $fand_raw_hours ) {
        // 3. Si non présent, on exécute la requête SQL préparée
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $fand_raw_hours = $wpdb->get_results( 
            $wpdb->prepare(
                "SELECT day_of_week, open_time, close_time, is_closed FROM $wpdb->prefix.fand_wcfm_pickup_hours WHERE branch_id = %d",
                intval( $fand_branch_id )
            ), 
            ARRAY_A 
        );

        // 4. On stocke en cache pour 12 heures
        wp_cache_set( $fand_cache_key, $fand_raw_hours, $fand_cache_group, 43200 );
    }

    // Organiser les données pour l'affichage (car il peut y avoir plusieurs plages horaires par jour)
    $fand_branch_hours = array();

    foreach ( $fand_raw_hours as $fand_hour ) {
        $fand_day = $fand_hour['day_of_week'];
       
        if ( ! isset( $fand_branch_hours[$fand_day] ) ) {
            $fand_branch_hours[$fand_day] = array( 'closed' => false, 'periods' => array() );
        }
        
        // Si is_closed est à 1, marquer le jour comme fermé (même si des périodes existent, on priorise le fermé)
        if ( $fand_hour['is_closed'] == 1 ) {
            $fand_branch_hours[$fand_day]['closed'] = true;
        } else {
            // Ajouter la plage horaire
            $fand_branch_hours[$fand_day]['periods'][] = array(
                'open'  => $fand_hour['open_time'],
                'close' => $fand_hour['close_time']
            );
        }
    }

    // Utilisation des données préparées $fand_branch_hours
    if ( ! empty( $fand_branch_hours ) ) {
        // Les jours sont indexés de 0 (Lundi) à 6 (Dimanche) selon votre description
        $fand_days_map = array(
            0 => 'Lundi',
            1 => 'Mardi',
            2 => 'Mercredi',
            3 => 'Jeudi',
            4 => 'Vendredi',
            5 => 'Samedi',
            6 => 'Dimanche',
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
                    foreach ( $fand_days_map as $fand_day_index => $fand_day_name ) :
                       
                        // Vérifier si nous avons des données pour ce jour
                        $fand_day_data = isset( $fand_branch_hours[$fand_day_index] ) ? $fand_branch_hours[$fand_day_index] : null;
                        
                        $fand_hours_display = '';
                        $fand_css_style = '';
                        
                        if ( $fand_day_data && $fand_day_data['closed'] ) {
                            // Jour marqué comme fermé
                            $fand_hours_display = 'Fermé';
                            $fand_css_style = 'color: red; font-weight: bold;';
                        } elseif ( $fand_day_data && ! empty( $fand_day_data['periods'] ) ) {
                            // Afficher les plages horaires (gestion des multiples plages)
                            $fand_periods_text = array();
                            foreach ($fand_day_data['periods'] as $fand_period) {
                                // Formatage simple HH:MM - HH:MM
                                $fand_periods_text[] = substr($fand_period['open'], 0, 5) . ' - ' . substr($fand_period['close'], 0, 5);
                            }
                            $fand_hours_display = implode('<br>', $fand_periods_text); // Afficher les plages sur plusieurs lignes
                        } else {
                            // Pas de données spécifiques (peut être considéré comme fermé si non renseigné)
                            $fand_hours_display = 'Non spécifié / Fermé';
                            $fand_css_style = 'color: #888;';
                        }
                        ?>
                        <li style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee;">
                            <span style="font-weight: 600;"><?php echo esc_html($fand_day_name); ?> :</span>
                            <span style="<?php echo esc_attr($fand_css_style); ?> text-align: right;"><?php echo wp_kses($fand_hours_display, array('br' => array())); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
        <?php
    }
?>



