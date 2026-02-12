<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// single-branch-view.php
get_header();

// Inclure et instancier le modèle
use fandWCFMPickupPoints\Classes\Models\BranchModel;

// Récupération des données passées
$fandpipo_vendor_id = get_query_var('current_vendor_id');
$fandpipo_store_url = function_exists('get_wcfm_store_url') ? get_wcfm_store_url($fandpipo_vendor_id) : '#';

$fandpipo_branch_raw_data = get_query_var( 'current_branch_data' );

// 2. Utilisation du Modèle pour récupérer TOUTES les données formatées
// Assurez-vous que l'instanciation est correcte selon où se trouve la classe (probablement besoin d'un autoloader ou d'un require, mais l'usage du namespace ici est supposé fonctionnel)
$fandpipo_branch_model = new BranchModel();
$fandpipo_data = $fandpipo_branch_model->getSingleBranchData($fandpipo_vendor_id, $fandpipo_branch_raw_data);
// Le script JS s'attend à un tableau de marqueurs, nous encapsulons donc le résultat.
$fandpipo_markers = $fandpipo_data ? [$fandpipo_data] : [];
// 3. Extraction des variables pour la vue (similaire à ce que vous faisiez)
// Les noms de variables sont maintenant ceux définis dans le tableau de retour du modèle.
$fandpipo_branch_id         = $fandpipo_data['branch_id'];
$fandpipo_branch_name       = $fandpipo_data['branch_name'];
$fandpipo_address           = $fandpipo_data['display_address'];
$fandpipo_lat               = $fandpipo_data['lat'];
$fandpipo_lng               = $fandpipo_data['lng'];
$fandpipo_email             = $fandpipo_data['vendor_email'];
$fandpipo_phone             = $fandpipo_data['vendor_phone'];
$fandpipo_avatar            = $fandpipo_data['avatar_url'];
$fandpipo_store_url         = $fandpipo_data['store_url'];
$fandpipo_banner            = $fandpipo_data['banner_url']; 
$fandpipo_store_info        = $fandpipo_data['store_info'];
$fandpipo_category_terms    = $fandpipo_data['category_terms']; // Utilisé plus bas pour le filtre/catégories
$fandpipo_store_user        = wcfmmp_get_store( $fandpipo_vendor_id );
$fandpipo_store_info        = $fandpipo_store_user->get_shop_info();

// Récupérer l'ID du post "Branch" actuellement affiché dans la requête principale
$fandpipo_current_post_id = get_queried_object_id();

// Assurez-vous que cette variable est disponible avant ce bloc (elle doit venir de la fonction de routage)
$fandpipo_branch_slug = get_query_var( 'branch_slug' ); 

// 1. Définir le chemin de base stable
// Assurez-vous que 'pickup/emplacement' est la base de vos permaliens.
if ( ! empty( $fandpipo_branch_slug ) ) {
    $fandpipo_base_path = 'pickup/emplacement/' . $fandpipo_branch_slug;
    
    // 2. Reconstruire l'URL de base complète de l'emplacement (ex: .../la-rhum-caffee/)
    // Cette URL DOIT se terminer par un slash, mais SANS SLUG D'ONGLET.
    $fandpipo_base_url_for_tabs = trailingslashit( site_url( $fandpipo_base_path ) );
    
} else {
    // Cas de repli si le branch_slug n'est pas disponible (improbable si le routage fonctionne)
    $fandpipo_base_url_for_tabs = site_url(); 
}
// Reconstruit l'URL de base stable de l'emplacement :
$fandpipo_base_url_for_filter = trailingslashit( site_url( $fandpipo_base_path ) );

// Récupérer le slug d'onglet actif. On utilise maintenant 'tab_slug' qui est défini par le routage
$fandpipo_active_tab = get_query_var( 'tab_slug' ); 

// --- Détermination de l'onglet actif (Logique simplifiée et plus robuste) ---
$fandpipo_valid_tabs = array('about', 'policies', 'reviews', 'followers');

if ( ! empty( $fandpipo_active_tab ) && in_array( $fandpipo_active_tab, $fandpipo_valid_tabs ) ) {
    // Si la variable de requête 'tab_slug' existe et est valide, on l'utilise
    // (Ceci suppose que le routage PHP fonctionne maintenant)
} else {
    // Sinon, on utilise l'onglet par défaut (produits)
    $fandpipo_active_tab = 'products'; 
}

// Récupérer la page actuelle pour la pagination
$fandpipo_paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
// Récupérer le slug de la catégorie sélectionnée (pour le filtre)
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$fandpipo_current_cat_slug = isset( $_GET['product_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['product_cat'] ) ) : '';

// 1. Définition des arguments de la requête des produits
$fandpipo_args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 12, 
    'author'         => $fandpipo_vendor_id, 
    'paged'          => $fandpipo_paged,
);

// 2. Logique de FILTRE par CATÉGORIE
if ( ! empty( $fandpipo_current_cat_slug ) ) {
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
    $fandpipo_args['tax_query'] = array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $fandpipo_current_cat_slug,
        ),
    );
}

// 3. Logique de TRI (OrderBy)
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( isset( $_GET['fandpipo_orderby'] ) ) {
    // On ignore le Nonce car il s'agit d'un tri d'affichage public via URL (GET)
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $fandpipo_orderby = sanitize_text_field( wp_unslash( $_GET['fandpipo_orderby'] ) );

    if ( $fandpipo_orderby == 'price' ) {
        $fandpipo_args['fandpipo_orderby'] = 'meta_value_num';
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        $fandpipo_args['meta_key'] = '_price';
        $fandpipo_args['order'] = 'ASC';
    } elseif ( $fandpipo_orderby == 'price-desc' ) {
        $fandpipo_args['fandpipo_orderby'] = 'meta_value_num';
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        $fandpipo_args['meta_key'] = '_price';
        $fandpipo_args['order'] = 'DESC';
    } elseif ( $fandpipo_orderby == 'date' ) {
        $fandpipo_args['fandpipo_orderby'] = 'date';
        $fandpipo_args['order'] = 'DESC';
    }
}

// 4. Exécuter la requête
$fandpipo_products = new WP_Query( $fandpipo_args );

// 5. Mettre à jour la requête globale pour les fonctions WooCommerce/pagination si des produits existent
if ( $fandpipo_products->have_posts() ) {
    global $wp_query;
    $fandpipo_original_wp_query = $wp_query; // Sauvegarder l'original
    $wp_query = $fandpipo_products;          // Remplacer
    wc_set_loop_prop( 'is_main_query', false ); 
    wc_set_loop_prop( 'total', $fandpipo_products->found_posts );
    wc_set_loop_prop( 'current_page', $fandpipo_paged ); 
}

// Définir la variable de compteur, même si elle n'est pas strictement nécessaire ici
$fandpipo_counter = 0;

?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div id="wcfmmp-store" class="wcfmmp-single-store-holder">
            <div id="wcfmmp-store-content" class="wcfmmp-store-page-wrap woocommerce" role="main">

                <div class="wcfm_banner_area">
                    <section class="banner_area banner_area_desktop">
                          <div class="banner_img" style="background-image: url('<?php echo esc_url($fandpipo_banner); ?>');"></div>
                        <div class="banner_text"><h1><?php echo esc_html( $fandpipo_branch_name ); ?></h1></div>
                    </section>
                </div>
                
                <div id="wcfm_store_header">
                    <div class="header_wrapper">
                        <div class="header_area">
                            <div class="lft header_left">
                                <div class="logo_area lft">
                                    <a href="#">
                                       <?php if (!$fandpipo_avatar) { $fandpipo_avatar = FANDPIPO_AVATAR_DEFAULT; }?>
                                        <img src="<?php echo esc_url( $fandpipo_avatar ); ?>" alt="Logo">
                                    </a>
                                </div>
                                <div class="logo_area_after">
                                    <div class="wcfmmp-store-rating" title="<?php echo esc_attr( $fandpipo_data['rating_count'] ); ?> avis">
                                        <span style="width: <?php echo esc_attr( ($fandpipo_data['rating_avg'] / 5) * 100 ); ?>%">
                                            <strong class="rating"><?php echo esc_html( $fandpipo_data['rating_avg'] ); ?></strong> sur 5             
                                        </span>
                                    </div>
                                    
                                    <div class="review-count-caption" style="font-size: 11px; color: #666;">
                                        (<?php echo esc_html( $fandpipo_data['rating_count'] ); ?> avis clients)
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
                                    <?php 
                                        // Nettoyage et encodage de l'adresse pour l'URL
                                        $fandpipo_google_maps_url = "https://www.google.com/maps/search/?api=1&query=" . urlencode( $fandpipo_address );
                                    ?>
                                    <a href="<?php echo esc_url( $fandpipo_google_maps_url ); ?>" target="_blank">
                                        <span><?php echo esc_html( $fandpipo_address ); ?></span>
                                    </a>
                                    </p>
                                    <div class="">
                                        <div class="store_info_parallal wcfmmp_store_header_phone" style="margin-right: 10px;">
                                            <i class="wcfmfa fa-phone" aria-hidden="true"></i>
                                            <span>
                                            <a href="tel:<?php echo esc_attr( $fandpipo_phone ); ?>">
                                                <?php echo esc_html( $fandpipo_phone ); ?>
                                            </a>
                                            </span>
                                        </div>
                                        <div class="store_info_parallal wcfmmp_store_header_email">
                                            <i class="wcfmfa fa-envelope" aria-hidden="true"></i>
                                            <span>
                                            <a href="mailto:<?php echo esc_attr( $fandpipo_email ); ?>">
                                                <?php echo esc_html( $fandpipo_email ); ?>
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
                        include "fandpipo-single-branch-right.php";
                        ?>
                        </div>
                    </div>
 
                    <div class="lft left_sidebar widget-area sidebar"> 
                        <?php
                        include "fandpipo-single-branch-left.php";
                        ?>
                    </div>
                
                    <div class="spacer"></div>
                    </div>
                    <div class="wcfm-clearfix"></div>
            </div>
        </div>
    </main>
</div>

<?php
    wp_enqueue_script('fand-pickup-map-script'); 
?>
<?php wp_footer(); ?>