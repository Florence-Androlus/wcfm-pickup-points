<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// single-branch-view.php
get_header();

// Inclure et instancier le modèle
use fandWCFMPickupPoints\Classes\Models\BranchModel;

// Récupération des données passées
$fand_vendor_id = get_query_var('current_vendor_id');
$fand_store_url = function_exists('get_wcfm_store_url') ? get_wcfm_store_url($fand_vendor_id) : '#';

$fand_branch_raw_data = get_query_var( 'current_branch_data' );

// 2. Utilisation du Modèle pour récupérer TOUTES les données formatées
// Assurez-vous que l'instanciation est correcte selon où se trouve la classe (probablement besoin d'un autoloader ou d'un require, mais l'usage du namespace ici est supposé fonctionnel)
$fand_branch_model = new BranchModel();
$fand_data = $fand_branch_model->getSingleBranchData($fand_vendor_id, $fand_branch_raw_data);
// Le script JS s'attend à un tableau de marqueurs, nous encapsulons donc le résultat.
$fand_markers = $fand_data ? [$fand_data] : [];
// 3. Extraction des variables pour la vue (similaire à ce que vous faisiez)
// Les noms de variables sont maintenant ceux définis dans le tableau de retour du modèle.
$fand_branch_id         = $fand_data['branch_id'];
$fand_branch_name       = $fand_data['branch_name'];
$fand_address           = $fand_data['display_address'];
$fand_lat               = $fand_data['lat'];
$fand_lng               = $fand_data['lng'];
$fand_email             = $fand_data['vendor_email'];
$fand_phone             = $fand_data['vendor_phone'];
$fand_avatar            = $fand_data['avatar_url'];
$fand_store_url    = $fand_data['store_url'];
$fand_banner            = $fand_data['banner_url']; 
$fand_store_info        = $fand_data['store_info'];
$fand_category_terms    = $fand_data['category_terms']; // Utilisé plus bas pour le filtre/catégories
$fand_store_user        = wcfmmp_get_store( $fand_vendor_id );
$fand_store_info        = $fand_store_user->get_shop_info();

// Récupérer l'ID du post "Branch" actuellement affiché dans la requête principale
$fand_current_post_id = get_queried_object_id();

// Assurez-vous que cette variable est disponible avant ce bloc (elle doit venir de la fonction de routage)
$fand_branch_slug = get_query_var( 'branch_slug' ); 

// 1. Définir le chemin de base stable
// Assurez-vous que 'pickup/emplacement' est la base de vos permaliens.
if ( ! empty( $fand_branch_slug ) ) {
    $fand_base_path = 'pickup/emplacement/' . $fand_branch_slug;
    
    // 2. Reconstruire l'URL de base complète de l'emplacement (ex: .../la-rhum-caffee/)
    // Cette URL DOIT se terminer par un slash, mais SANS SLUG D'ONGLET.
    $fand_base_url_for_tabs = trailingslashit( site_url( $fand_base_path ) );
    
} else {
    // Cas de repli si le branch_slug n'est pas disponible (improbable si le routage fonctionne)
    $fand_base_url_for_tabs = site_url(); 
}
// Reconstruit l'URL de base stable de l'emplacement :
$fand_base_url_for_filter = trailingslashit( site_url( $fand_base_path ) );

// Récupérer le slug d'onglet actif. On utilise maintenant 'tab_slug' qui est défini par le routage
$fand_active_tab = get_query_var( 'tab_slug' ); 

// --- Détermination de l'onglet actif (Logique simplifiée et plus robuste) ---
$fand_valid_tabs = array('about', 'policies', 'reviews', 'followers');

if ( ! empty( $fand_active_tab ) && in_array( $fand_active_tab, $fand_valid_tabs ) ) {
    // Si la variable de requête 'tab_slug' existe et est valide, on l'utilise
    // (Ceci suppose que le routage PHP fonctionne maintenant)
} else {
    // Sinon, on utilise l'onglet par défaut (produits)
    $fand_active_tab = 'products'; 
}

// Récupérer la page actuelle pour la pagination
$fand_paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
// Récupérer le slug de la catégorie sélectionnée (pour le filtre)
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$fand_current_cat_slug = isset( $_GET['product_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['product_cat'] ) ) : '';

// 1. Définition des arguments de la requête des produits
$fand_args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 12, 
    'author'         => $fand_vendor_id, 
    'paged'          => $fand_paged,
);

// 2. Logique de FILTRE par CATÉGORIE
if ( ! empty( $fand_current_cat_slug ) ) {
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
    $fand_args['tax_query'] = array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $fand_current_cat_slug,
        ),
    );
}

// 3. Logique de TRI (OrderBy)
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( isset( $_GET['orderby'] ) ) {
    // On ignore le Nonce car il s'agit d'un tri d'affichage public via URL (GET)
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $fand_orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );

    if ( $fand_orderby == 'price' ) {
        $fand_args['orderby'] = 'meta_value_num';
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        $fand_args['meta_key'] = '_price';
        $fand_args['order'] = 'ASC';
    } elseif ( $fand_orderby == 'price-desc' ) {
        $fand_args['orderby'] = 'meta_value_num';
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        $fand_args['meta_key'] = '_price';
        $fand_args['order'] = 'DESC';
    } elseif ( $fand_orderby == 'date' ) {
        $fand_args['orderby'] = 'date';
        $fand_args['order'] = 'DESC';
    }
}

// 4. Exécuter la requête
$fand_products = new WP_Query( $fand_args );

// 5. Mettre à jour la requête globale pour les fonctions WooCommerce/pagination si des produits existent
if ( $fand_products->have_posts() ) {
    global $wp_query;
    $fand_original_wp_query = $wp_query; // Sauvegarder l'original
    $wp_query = $fand_products;          // Remplacer
    wc_set_loop_prop( 'is_main_query', false ); 
    wc_set_loop_prop( 'total', $fand_products->found_posts );
    wc_set_loop_prop( 'current_page', $fand_paged ); 
}

// Définir la variable de compteur, même si elle n'est pas strictement nécessaire ici
$fand_counter = 0;

?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div id="wcfmmp-store" class="wcfmmp-single-store-holder">
            <div id="wcfmmp-store-content" class="wcfmmp-store-page-wrap woocommerce" role="main">

                <div class="wcfm_banner_area">
                    <section class="banner_area banner_area_desktop">
                          <div class="banner_img" style="background-image: url('<?php echo esc_url($fand_banner); ?>');"></div>
                        <div class="banner_text"><h1><?php echo esc_html( $fand_branch_name ); ?></h1></div>
                    </section>
                </div>
                
                <div id="wcfm_store_header">
                    <div class="header_wrapper">
                        <div class="header_area">
                            <div class="lft header_left">
                                <div class="logo_area lft">
                                    <a href="#">
                                        <img src="<?php echo esc_url( $fand_avatar ); ?>" alt="Logo">
                                    </a>
                                </div>
                                <div class="logo_area_after">
                                    <div class="wcfmmp-store-rating" title="Aucun avis pour le moment !">
                                        <span style="width: 0%">
                                            <strong class="rating">0</strong> sur 5				
                                        </span>
                                    </div>
                                
                                    <div class="wcfmmp_store_mobile_badges">
                                        <div class="wcfm_vendor_badges"></div>
                                        <div class="spacer"></div> 
                                    </div>
                                    <div class="spacer"></div>  
				                </div>
                                <div class="address rgt" >                                            
                                    <p class=" wcfmmp_store_header_address">
                                    <i class="wcfmfa fa-map-marker" aria-hidden="true"></i>
                                    <a href="https://google.com/maps/place/Avenue%20Pierre%20et%20Marie%20Curie%2C%2083240%20CAVALAIRE-SUR-MER%2C%20France/@43.17160958829991,6.5316724776202895&amp;z=16" target="_blank"><span><?php echo esc_html( $fand_address ); ?></span></a>
                                    </p>
                                    <div class="">
                                        <div class="store_info_parallal wcfmmp_store_header_phone" style="margin-right: 10px;">
                                            <i class="wcfmfa fa-phone" aria-hidden="true"></i>
                                            <span>
                                            <a href="tel:<?php echo esc_attr( $fand_phone ); ?>">
                                                <?php echo esc_html( $fand_phone ); ?>
                                            </a>
                                            </span>
                                        </div>
                                        <div class="store_info_parallal wcfmmp_store_header_email">
                                            <i class="wcfmfa fa-envelope" aria-hidden="true"></i>
                                            <span>
                                            <a href="mailto:<?php echo esc_attr( $fand_email ); ?>">
                                                <?php echo esc_html( $fand_email ); ?>
                                            </a>
                                            </span>
                                        </div>  
                                        <div class="spacer"></div>  
                                    </div>
                                </div>
                            </div>
                            <div class="header_right">
                                <div class="bd_icon_area lft">

                                    <div class="lft bd_icon_box"><a class="wcfm_store_enquiry " data-store="5" data-product="0" href="#"><i class="wcfmfa fa-question" aria-hidden="true"></i><span>Question</span></a></div>
                                    <div class="spacer"></div>   
                                </div>
                                <div class="spacer"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="wcfm-clearfix"></div>
                
                <div class="body_area">
                    <div class="rgt right_side">
                        <?php
                        include "single-branch-right.php";
                        ?>
                        </div>
                    </div>
                </div>
                <div class="lft left_sidebar widget-area sidebar"> 
                    <?php
                    include "single-branch-left.php";
                    ?>
                </div>
                
                <div class="spacer"></div>
                </div>
                <div class="wcfm-clearfix"></div>
            </div>
        </div>
    </main>
</div>
<script>
    // Variables PHP rendues pour le JavaScript
    // mapMarkers ne contient maintenant qu'un seul élément (la branche actuelle)
    const mapMarkers = <?php echo wp_json_encode( $fand_markers ); ?>;

    // Ajout des coordonnées pour le centrage de la carte
    const currentLat = <?php echo json_encode($fand_lat); ?>;
    const currentLng = <?php echo json_encode($fand_lng); ?>;
    const fandPickupPluginUrl = '<?php echo esc_url( FAND_PICKUP_PLUGIN_URL ); ?>';
    const isSingleView = true; // Flag pour le script général

</script>
<?php
    wp_enqueue_script('fand-pickup-map-script'); 
?>
<?php wp_footer(); ?>