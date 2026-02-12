<?php
// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$fandpipo_default_category = get_option('fandpipo_liste_categories_boutique', 'Alimentation');
$fandpipo_default_category = trim(explode(',', $fandpipo_default_category)[0]);
$fandpipo_default_country = $data['default_country'];
$fandpipo_countries = $data['countries'];
$fandpipo_default_state = isset($fandpipo_default_state) ? $fandpipo_default_state : '';
?>

<div id="pickup-map" style="width:100%;height:550px;"></div>

<form role="search" method="get" class="wcfmmp-store-search-form" action="">
    <?php
    // 1. Préparation sécurisée de la valeur de recherche
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $fandpipo_search_value = isset($_GET['fandpipo_pickup_search']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_pickup_search'])) : '';
    ?>

    <input class="search-field wcfmmp-store-search" type="search" id="pickup-search" placeholder="Recherche..." name="fandpipo_pickup_search" value="<?php echo esc_attr($fandpipo_search_value); ?>"/>

    <select name="fandpipo_category" id="pickup-category" class="select2 select2-container select2-container--default">
        <option value="">Toutes catégories</option>
        <?php
            // 1. Récupérer ta liste personnalisée depuis les options (comme dans Scripts.php)
            $fandpipo_liste_brute = get_option('fandpipo_liste_categories_boutique', 'Alimentation, Évènementiel, Foodtruck');
            
            // 2. Transformer la chaîne en tableau propre
            $fandpipo_categories_array = array_map('trim', explode(',', $fandpipo_liste_brute));

            // 3. Boucler sur tes catégories pour créer les options
            if (!empty($fandpipo_categories_array)) {
                foreach ($fandpipo_categories_array as $fandpipo_category_name) {
                    // 1. Préparation sécurisée de la catégorie sélectionnée
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $fandpipo_current_category = isset($_GET['fandpipo_category']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_category'])) : '';
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
        $fandpipo_selected_country = isset($_GET['fandpipo_country']) ? sanitize_text_field(wp_unslash($_GET['fandpipo_country'])) : $fandpipo_default_country; 
    ?>

    <select name="fandpipo_country" id="pickup-country"> <option value="">Tous les pays</option>
        <?php foreach ($fandpipo_countries as $fandpipo_code => $fandpipo_name) : ?>
            <option value="<?php echo esc_attr($fandpipo_code); ?>" <?php selected($fandpipo_selected_country, $fandpipo_code); ?>>
                <?php echo esc_html($fandpipo_name); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="state" id="pickup-state" class="select2 select2-container select2-container--default">
        <option value="">Départements / Régions</option> <!-- obligatoire pour allowClear -->
        <?php
        global $wpdb;
        $fandpipo_states = [];//$wpdb->get_col( "SELECT DISTINCT state FROM {$wpdb->prefix}ultimate_pickup_locations WHERE state != '' ORDER BY state ASC" );
        foreach ($fandpipo_states as $fandpipo_state) {
            echo '<option value="' . esc_attr($fandpipo_state) . '">' . esc_html($fandpipo_state) . '</option>';
        }
        ?>
    </select>

</form>

