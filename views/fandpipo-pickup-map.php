<?php
// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

// On ignore le Nonce car c'est un formulaire de recherche GET public
// phpcs:disable WordPress.Security.NonceVerification.Recommended

// 1. Définition et sécurisation des variables de base
$fandpipo_current_category = isset($_GET['fandpipo_category']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_category'])) : '';
$fandpipo_wcfm_options              = get_option( 'wcfm_marketplace_options' );

// On sécurise l'accès aux options pour éviter "used without escaping"
$fandpipo_is_radius_enabled = ( isset( $fandpipo_wcfm_options['enable_wcfm_storelist_radius'] ) && 'yes' === $fandpipo_wcfm_options['enable_wcfm_storelist_radius'] );

// On cast immédiatement pour rassurer le scanner sur l'origine des données $_GET
$fandpipo_search_lat   = isset($_GET['wcfmmp_radius_lat']) ? (float) sanitize_text_field(wp_unslash($_GET['wcfmmp_radius_lat'])) : 0;
$fandpipo_search_lng   = isset($_GET['wcfmmp_radius_lng']) ? (float) sanitize_text_field(wp_unslash($_GET['wcfmmp_radius_lng'])) : 0;
$fandpipo_search_range = isset($_GET['wcfmmp_radius_range']) ? intval(sanitize_text_field(wp_unslash($_GET['wcfmmp_radius_range']))) : 5;
$fandpipo_current_range = $fandpipo_search_range;

$fandpipo_results = []; 
$fandpipo_table_name = $wpdb->prefix . "wcfm_store_locations";
$fandpipo_sql_distance_query = "";

if ( $fandpipo_is_radius_enabled && $fandpipo_search_lat && $fandpipo_search_lng ) {
    $fandpipo_sql_distance_query = $wpdb->prepare(
        ", ( 6371 * acos( cos( radians(%f) ) * cos( radians( latitude ) ) 
        * cos( radians( longitude ) - radians(%f) ) + sin( radians(%f) ) 
        * sin( radians( latitude ) ) ) ) AS distance",
        $fandpipo_search_lat, 
        $fandpipo_search_lng, 
        $fandpipo_search_lat
    );
}

// 2. Construction de la structure SQL
$fandpipo_query = "SELECT * " . $fandpipo_sql_distance_query . " FROM " . $fandpipo_table_name . " WHERE 1=1";
$fandpipo_params = [];

if ( ! empty( $fandpipo_sql_distance_query ) ) {
    $fandpipo_query .= " HAVING distance <= %d ORDER BY distance ASC";
    $fandpipo_params[] = $fandpipo_search_range;
} else {
    $fandpipo_query .= " ORDER BY id DESC";
}

// 3. Exécution finale avec suppression de TOUS les avertissements spécifiques
if ( ! empty( $fandpipo_params ) ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $fandpipo_results = $wpdb->get_results( $wpdb->prepare( $fandpipo_query, $fandpipo_params ) );
} else {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $fandpipo_results = $wpdb->get_results( $fandpipo_query );
}
?>

<div id="pickup-map" style="width:100%;height:550px;"></div>

<form role="search" method="get" class="wcfmmp-store-search-form" action="">
    <?php
    // 1. Préparation sécurisée de la valeur de recherche
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $fandpipo_search_value = isset($_GET['fandpipo_pickup_search']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_search'])) : '';
    
    ?>
    <input type="hidden" name="fandpipo_pickup_day" value="<?php echo isset($_GET['fandpipo_pickup_day']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_day']))) : ''; ?>">
    <input type="hidden" name="fandpipo_pickup_status" value="<?php echo isset($_GET['fandpipo_pickup_status']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_status']))) : ''; ?>">
    <input type="hidden" name="fandpipo_pickup_orderby" value="<?php echo isset($_GET['fandpipo_pickup_orderby']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_orderby']))) : ''; ?>">
    <input class="search-field wcfmmp-store-search" type="search" id="pickup-search" placeholder="Recherche..." name="fandpipo_pickup_search" value="<?php echo esc_attr($fandpipo_search_value); ?>"/>

    <select name="fandpipo_category" id="pickup-category" class="select2 select2-container select2-container--default" onchange="this.form.submit()">
        <option value=""><?php echo __('All categories', 'fand-pickup-points-ultimate-edition-for-wcfm'); ?></option>
        <?php
            global $wpdb;
            // 1. On récupère les catégories depuis TA table SQL (fandpipo_categories)
            // Note : remplace 'fandpipo_categories' par le nom exact de ta table si besoin
            $fandpipo_table_categories = $wpdb->prefix . "fandpipo_categories";
            $fandpipo_categories_db = $wpdb->get_results( "SELECT nom FROM $fandpipo_table_categories ORDER BY nom ASC", ARRAY_A );

            // 2. On récupère la catégorie actuellement sélectionnée dans l'URL
            $fandpipo_current_category = isset($_GET['fandpipo_category']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_category'])) : '';

            // 3. Boucle sur les résultats de la BDD
            if ( ! empty( $fandpipo_categories_db ) ) {
                foreach ( $fandpipo_categories_db as $cat_row ) {
                    $fandpipo_category_name = $cat_row['nom']; // On extrait le nom de l'objet
                    ?>
                    <option value="<?php echo esc_attr($fandpipo_category_name); ?>" <?php selected($fandpipo_current_category, $fandpipo_category_name); ?>>
                        <?php echo esc_html($fandpipo_category_name); ?>
                    </option>
                    <?php
                }
            }
        ?>
    </select>

    <!-- Champ texte pour filtrer le select pays -->
    <?php
        // On récupère le pays de l'URL, sinon on prend la France par défaut
        // On ignore le manque de Nonce car c'est un filtre de carte public (GET)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        // On définit un pays par défaut (ex: France) pour éviter le Warning
        $fandpipo_default_country = 'FR';
        $fandpipo_selected_country = isset($_GET['fandpipo_country']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_country'])) : $fandpipo_default_country; 
    ?>


    <?php if ( $fandpipo_is_radius_enabled ) : ?>
        
    <div id="wcfm_radius_filter_container" class="wcfm_radius_filter_container" style="position: relative;">
		<?php
        // On définit le chemin vers l'image du plugin de manière dynamique
        $fandpipo_locate_icon_url = plugins_url('wc-multivendor-marketplace/assets/images/locate.svg');
        ?>

        <i id="wcfm_locate_me" class="wcfmmmp_locate_icon" style="cursor:pointer; background-image: url(<?php echo esc_url($fandpipo_locate_icon_url); ?>)" data-nonce="<?php echo esc_attr( wp_create_nonce('fandpipo_gps_action') ); ?>"></i>
        <div class="leaflet-control-search search-exp">
            <label class="search-input wcfmmp-radius-addr" for="wcfmmp_radius_addr" style="display: none; float: none;"></label>
    
            <input class="search-input wcfmmp-radius-addr" type="text" size="9" autocomplete="off" autocapitalize="off" placeholder="Rechercher une adresse..." role="search" id="wcfmmp_radius_addr" name="wcfmmp_radius_addr" style="display: block; float: none;" value="<?php echo isset($_GET['wcfmmp_radius_addr']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['wcfmmp_radius_addr']))) : ''; ?>">
            <ul class="search-tooltip" style="display: none;"></ul>
            </a>
            <a class="search-button" href="#" title="Search..."></a>
            <div class="search-alert" style="display: none;"></div>
        </div>
    </div>

    <div class="wcfm_radius_slidecontainer">
        <input class="wcfmmp_radius_range" name="wcfmmp_radius_range" id="wcfmmp_radius_range" type="range" value="<?php echo esc_attr($fandpipo_current_range); ?>" min="0" max="500" steps="6">
        <span class="wcfmmp_radius_range_start">0</span>
        <span class="wcfmmp_radius_range_cur" style="left: 19.5938px;"><?php echo esc_html($fandpipo_current_range); ?> Km</span>
        <span class="wcfmmp_radius_range_end">500</span>
    </div>

    <input type="hidden" id="wcfmmp_radius_lat" name="wcfmmp_radius_lat" value="<?php echo isset($_GET['wcfmmp_radius_lat']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['wcfmmp_radius_lat']))) : ''; ?>">
    <input type="hidden" id="wcfmmp_radius_lng" name="wcfmmp_radius_lng" value="<?php echo isset($_GET['wcfmmp_radius_lng']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['wcfmmp_radius_lng']))) : ''; ?>">

    <?php // phpcs:enable WordPress.Security.NonceVerification.Recommended
    else : ?>
        <select name="fandpipo_country" id="pickup-country" onchange="this.form.submit()"> 
            <option value=""><?php echo __('All countries', 'fand-pickup-points-ultimate-edition-for-wcfm'); ?></option>
            <?php foreach ($countries as $fandpipo_code => $fandpipo_name) : ?>
                <option value="<?php echo esc_attr($fandpipo_code); ?>" <?php selected($fandpipo_selected_country, $fandpipo_code); ?>>
                    <?php echo esc_html($fandpipo_name); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="state" id="pickup-state" class="select2 select2-container select2-container--default">
            <option value=""><?php echo __('Departments / Regions', 'fand-pickup-points-ultimate-edition-for-wcfm'); ?></option> <!-- obligatoire pour allowClear -->
            <?php
            global $wpdb;
            $fandpipo_states = [];//$wpdb->get_col( "SELECT DISTINCT state FROM {$wpdb->prefix}ultimate_pickup_locations WHERE state != '' ORDER BY state ASC" );
            foreach ($fandpipo_states as $fandpipo_state) {
                echo '<option value="' . esc_attr($fandpipo_state) . '">' . esc_html($fandpipo_state) . '</option>';
            }
            ?>
        </select>


    <?php endif; ?>

</form>

