<?php
// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$fand_default_category = get_option('liste_categories_boutique', 'Alimentation');
$fand_default_category = trim(explode(',', $fand_default_category)[0]);
$fand_default_country = $data['default_country'];
$fand_countries = $data['countries'];
$fand_default_state    = '';
?>

<div id="pickup-map" style="width:100%;height:550px;"></div>

<form role="search" method="get" class="wcfmmp-store-search-form" action="">
    <?php
    // 1. Préparation sécurisée de la valeur de recherche
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $fand_search_value = isset($_GET['pickup_search']) ? sanitize_text_field(wp_unslash($_GET['pickup_search'])) : '';
    ?>

    <input class="search-field wcfmmp-store-search" type="search" id="pickup-search" placeholder="Recherche..." name="pickup_search" value="<?php echo esc_attr($fand_search_value); ?>"/>

    <select name="category" id="pickup-category" class="select2 select2-container select2-container--default">
        <option value="">Toutes catégories</option>
        <?php
            // 1. Récupérer ta liste personnalisée depuis les options (comme dans Scripts.php)
            $fand_liste_brute = get_option('liste_categories_boutique', 'Alimentation, Évènementiel, Foodtruck');
            
            // 2. Transformer la chaîne en tableau propre
            $fand_categories_array = array_map('trim', explode(',', $fand_liste_brute));

            // 3. Boucler sur tes catégories pour créer les options
            if (!empty($fand_categories_array)) {
                foreach ($fand_categories_array as $fand_category_name) {
                    // 1. Préparation sécurisée de la catégorie sélectionnée
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $fand_current_category = isset($_GET['category']) ? sanitize_text_field(wp_unslash($_GET['category'])) : '';
                    ?>

                    <option value="<?php echo esc_attr($fand_category_name); ?>" <?php selected($fand_current_category, $fand_category_name); ?>>
                        <?php echo esc_html($fand_category_name); ?>
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
        $fand_selected_country = isset($_GET['country']) ? sanitize_text_field(wp_unslash($_GET['country'])) : $fand_default_country; 
        error_log( $fand_selected_country);
        error_log( print_r($fand_countries,true));
    ?>

    <select name="country" id="pickup-country"> <option value="">Tous les pays</option>
        <?php foreach ($fand_countries as $fand_code => $fand_name) : ?>
            <option value="<?php echo esc_attr($fand_code); ?>" <?php selected($fand_selected_country, $fand_code); ?>>
                <?php echo esc_html($fand_name); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="state" id="pickup-state" class="select2 select2-container select2-container--default">
        <option value="">Départements / Régions</option> <!-- obligatoire pour allowClear -->
        <?php
        global $wpdb;
        $fand_states = [];//$wpdb->get_col( "SELECT DISTINCT state FROM {$wpdb->prefix}ultimate_pickup_locations WHERE state != '' ORDER BY state ASC" );
        foreach ($fand_states as $fand_state) {
            echo '<option value="' . esc_attr($fand_state) . '">' . esc_html($fand_state) . '</option>';
        }
        ?>
    </select>

</form>

<script>
    // Variables PHP rendues pour le JavaScript
    const mapMarkers     = <?php echo json_encode($markers); ?>;
    const defaultCategory= '<?php echo esc_js($fand_default_category); ?>';
    const defaultCountry = '<?php echo esc_js($fand_default_country); ?>';
    const defaultState   = '<?php echo esc_js($fand_default_state); ?>';
    const fandPickupPluginUrl = '<?php echo esc_url(FAND_PICKUP_PLUGIN_URL); ?>';
</script>