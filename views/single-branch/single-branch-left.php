 
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
        
        <input type="hidden" name="vendor_id_for_redirect" value="<?php echo esc_attr( $vendor_id ); ?>" />
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
                $all_cat_selected = empty( $current_cat_slug ) ? 'selected="selected"' : '';
                ?>
                <option value="<?php echo esc_url( $base_url_for_filter ); ?>" <?php echo esc_attr( $all_cat_selected ); ?>>
                    Toutes les catégories
                </option>

                <?php
                // Boucle pour chaque catégorie
                if ( ! is_wp_error( $category_terms ) && ! empty( $category_terms ) ) :
                    foreach ( $category_terms as $category ) :
                        $cat_slug = $category->slug;
                        $cat_name = $category->name;

                        // URL de filtrage
                        $category_filter_url = add_query_arg( 'product_cat', $cat_slug, $base_url_for_filter );

                        // Déterminer si option sélectionnée
                        $selected_attr = ( $current_cat_slug === $cat_slug ) ? 'selected="selected"' : '';
                        ?>
                        <option value="<?php echo esc_url( $category_filter_url ); ?>" <?php echo esc_attr( $selected_attr ); ?>>
                            <?php echo esc_html( $cat_name ); ?>
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

    // Assurez-vous que cette variable est disponible, elle est cruciale
    // Pour cet exemple, je suppose que vous avez l'ID de la branche
    // Si vous n'avez que le slug, le code pour trouver l'ID devra être ajouté.
    

    $hours_table = $wpdb->prefix . 'fand_wcfm_pickup_hours';

    // Récupérer tous les horaires pour cette branche
    $raw_hours = $wpdb->get_results( $wpdb->prepare(
        "SELECT day_of_week, open_time, close_time, is_closed FROM $hours_table WHERE branch_id = %d",
        $branch_id
    ), ARRAY_A );

    // Organiser les données pour l'affichage (car il peut y avoir plusieurs plages horaires par jour)
    $branch_hours = array();

    foreach ( $raw_hours as $hour ) {
        $day = $hour['day_of_week'];
        
        if ( ! isset( $branch_hours[$day] ) ) {
            $branch_hours[$day] = array( 'closed' => false, 'periods' => array() );
        }
        
        // Si is_closed est à 1, marquer le jour comme fermé (même si des périodes existent, on priorise le fermé)
        if ( $hour['is_closed'] == 1 ) {
            $branch_hours[$day]['closed'] = true;
        } else {
            // Ajouter la plage horaire
            $branch_hours[$day]['periods'][] = array(
                'open'  => $hour['open_time'],
                'close' => $hour['close_time']
            );
        }
    }

    // Utilisation des données préparées $branch_hours
    if ( ! empty( $branch_hours ) ) {
        // Les jours sont indexés de 0 (Lundi) à 6 (Dimanche) selon votre description
        $days_map = array(
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
                    foreach ( $days_map as $day_index => $day_name ) :
                        
                        // Vérifier si nous avons des données pour ce jour
                        $day_data = isset( $branch_hours[$day_index] ) ? $branch_hours[$day_index] : null;
                        
                        $hours_display = '';
                        $css_style = '';
                        
                        if ( $day_data && $day_data['closed'] ) {
                            // Jour marqué comme fermé
                            $hours_display = 'Fermé';
                            $css_style = 'color: red; font-weight: bold;';
                        } elseif ( $day_data && ! empty( $day_data['periods'] ) ) {
                            // Afficher les plages horaires (gestion des multiples plages)
                            $periods_texts = array();
                            foreach ($day_data['periods'] as $period) {
                                // Formatage simple HH:MM - HH:MM
                                $periods_texts[] = substr($period['open'], 0, 5) . ' - ' . substr($period['close'], 0, 5);
                            }
                            $hours_display = implode('<br>', $periods_texts); // Afficher les plages sur plusieurs lignes
                        } else {
                            // Pas de données spécifiques (peut être considéré comme fermé si non renseigné)
                            $hours_display = 'Non spécifié / Fermé';
                            $css_style = 'color: #888;';
                        }
                        ?>
                        <li style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee;">
                            <span style="font-weight: 600;"><?php echo esc_html($day_name); ?> :</span>
                            <span style="<?php echo esc_attr($css_style); ?> text-align: right;"><?php echo esc_html($hours_display); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
        <?php
    }
?>

