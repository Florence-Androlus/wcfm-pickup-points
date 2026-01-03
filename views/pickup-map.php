<?
$default_category = get_option('liste_categories_boutique', 'Alimentation');
$default_category = trim(explode(',', $default_category)[0]);
$default_country  = 'FR';
$default_state    = '';
?>

<div id="pickup-map" style="width:100%;height:550px;"></div>

<form role="search" method="get" class="wcfmmp-store-search-form" action="">
    <input class="search-field wcfmmp-store-search" type="search" id="pickup-search" placeholder="Recherche..." name="pickup_search" value="<?php echo esc_attr($_GET['pickup_search'] ?? ''); ?>" />

    <select name="category" id="pickup-category" class="select2 select2-container select2-container--default">
        <option value="">Toutes catégories</option>
        <?php
            // 1. Récupérer ta liste personnalisée depuis les options (comme dans Scripts.php)
            $liste_brute = get_option('liste_categories_boutique', 'Alimentation, Évènementiel, Foodtruck');
            
            // 2. Transformer la chaîne en tableau propre
            $categories_array = array_map('trim', explode(',', $liste_brute));

            // 3. Boucler sur tes catégories pour créer les options
            if (!empty($categories_array)) {
                foreach ($categories_array as $category_name) {
                    $selected_attr = selected($_GET['category'] ?? '', $category_name, false);
                    echo '<option value="' . esc_attr($category_name) . '" ' . $selected_attr . '>' . esc_html($category_name) . '</option>';
                }
            }
        ?>
    </select>

    <!-- Champ texte pour filtrer le select pays -->
    <?php
    // On récupère le pays de l'URL, sinon on prend la France par défaut
    $selected_country = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : 'FR'; 
    ?>

    <select name="country" id="pickup-country"> <option value="">Tous les pays</option>
        <?php foreach ($countries as $code => $name) : ?>
            <option value="<?php echo esc_attr($code); ?>" <?php selected($selected_country, $code); ?>>
                <?php echo esc_html($name); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="state" id="pickup-state" class="select2 select2-container select2-container--default">
        <option value="">Départements / Régions</option> <!-- obligatoire pour allowClear -->
        <?php
        global $wpdb;
        $states = [];//$wpdb->get_col( "SELECT DISTINCT state FROM {$wpdb->prefix}ultimate_pickup_locations WHERE state != '' ORDER BY state ASC" );
        foreach ($states as $state) {
            echo '<option value="' . esc_attr($state) . '">' . esc_html($state) . '</option>';
        }
        ?>
    </select>

</form>

<script>
    // Variables PHP rendues pour le JavaScript
    const mapMarkers     = <?php echo json_encode($markers); ?>;
    const defaultCategory= '<?php echo esc_js($default_category); ?>';
    const defaultCountry = '<?php echo esc_js($default_country); ?>';
    const defaultState   = '<?php echo esc_js($default_state); ?>';
    const fandPickupPluginUrl = '<?php echo esc_url(FAND_PICKUP_PLUGIN_URL); ?>';
</script>