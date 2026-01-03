<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// single-branch-view.php
get_header();

// Inclure et instancier le modèle
use fandWCFMPickupPoints\Classes\Models\BranchModel;

// Récupération des données passées
$vendor_id = get_query_var('current_vendor_id');
$profile_settings = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
$store_url = function_exists('get_wcfm_store_url') ? get_wcfm_store_url($vendor_id) : '#';
$email = get_user_meta($vendor_id, 'billing_email', true);
$phone = get_user_meta($vendor_id, 'billing_phone', true);

$branch_raw_data = get_query_var( 'current_branch_data' );

// 2. Utilisation du Modèle pour récupérer TOUTES les données formatées
// Assurez-vous que l'instanciation est correcte selon où se trouve la classe (probablement besoin d'un autoloader ou d'un require, mais l'usage du namespace ici est supposé fonctionnel)
$branch_model = new BranchModel();
$data = $branch_model->getSingleBranchData($vendor_id, $branch_raw_data);
// Le script JS s'attend à un tableau de marqueurs, nous encapsulons donc le résultat.
$markers = $data ? [$data] : [];
// 3. Extraction des variables pour la vue (similaire à ce que vous faisiez)
// Les noms de variables sont maintenant ceux définis dans le tableau de retour du modèle.
$branch_id         = $data['branch_id'];
$branch_name       = $data['branch_name'];
$address           = $data['display_address'];
$lat               = $data['lat'];
$lng               = $data['lng'];
$email             = $data['vendor_email'];
$phone             = $data['vendor_phone'];
$avatar            = $data['avatar_url'];
$store_url         = $data['store_url'];
$banner            = $data['banner_url']; 
$store_info        = $data['store_info'];
$category_terms    = $data['category_terms']; // Utilisé plus bas pour le filtre/catégories

$store_user        = wcfmmp_get_store( $vendor_id );
$store_info        = $store_user->get_shop_info();

// Récupérer l'ID du post "Branch" actuellement affiché dans la requête principale
$current_post_id = get_queried_object_id();

// Assurez-vous que cette variable est disponible avant ce bloc (elle doit venir de la fonction de routage)
$branch_slug = get_query_var( 'branch_slug' ); 

// 1. Définir le chemin de base stable
// Assurez-vous que 'pickup/emplacement' est la base de vos permaliens.
if ( ! empty( $branch_slug ) ) {
    $base_path = 'pickup/emplacement/' . $branch_slug;
    
    // 2. Reconstruire l'URL de base complète de l'emplacement (ex: .../la-rhum-caffee/)
    // Cette URL DOIT se terminer par un slash, mais SANS SLUG D'ONGLET.
    $base_url_for_tabs = trailingslashit( site_url( $base_path ) );
    
} else {
    // Cas de repli si le branch_slug n'est pas disponible (improbable si le routage fonctionne)
    $base_url_for_tabs = site_url(); 
}
// Reconstruit l'URL de base stable de l'emplacement :
$base_url_for_filter = trailingslashit( site_url( $base_path ) );

// Récupérer le slug d'onglet actif. On utilise maintenant 'tab_slug' qui est défini par le routage
$active_tab = get_query_var( 'tab_slug' ); 

// --- Détermination de l'onglet actif (Logique simplifiée et plus robuste) ---
$valid_tabs = array('about', 'policies', 'reviews', 'followers');

if ( ! empty( $active_tab ) && in_array( $active_tab, $valid_tabs ) ) {
    // Si la variable de requête 'tab_slug' existe et est valide, on l'utilise
    // (Ceci suppose que le routage PHP fonctionne maintenant)
} else {
    // Sinon, on utilise l'onglet par défaut (produits)
    $active_tab = 'products'; 
}

// Récupérer la page actuelle pour la pagination
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
// Récupérer le slug de la catégorie sélectionnée (pour le filtre)
$current_cat_slug = isset( $_GET['product_cat'] ) ? sanitize_text_field( $_GET['product_cat'] ) : '';

// 1. Définition des arguments de la requête des produits
$args = array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 12, 
    'author'         => $vendor_id, 
    'paged'          => $paged,
);

// 2. Logique de FILTRE par CATÉGORIE
if ( ! empty( $current_cat_slug ) ) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $current_cat_slug,
        ),
    );
}

// 3. Logique de TRI (OrderBy)
if ( isset( $_GET['orderby'] ) ) {
    $orderby = sanitize_text_field( $_GET['orderby'] );
    if ( $orderby == 'price' ) {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = '_price';
        $args['order'] = 'ASC';
    } elseif ( $orderby == 'price-desc' ) {
        $args['orderby'] = 'meta_value_num';
        $args['meta_key'] = '_price';
        $args['order'] = 'DESC';
    } elseif ( $orderby == 'date' ) {
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
    }
}

// 4. Exécuter la requête
$products = new WP_Query( $args );

// 5. Mettre à jour la requête globale pour les fonctions WooCommerce/pagination si des produits existent
if ( $products->have_posts() ) {
    global $wp_query;
    $original_wp_query = $wp_query; // Sauvegarder l'original
    $wp_query = $products;          // Remplacer
    wc_set_loop_prop( 'is_main_query', false ); 
    wc_set_loop_prop( 'total', $products->found_posts );
    wc_set_loop_prop( 'current_page', $paged ); 
}

// Définir la variable de compteur, même si elle n'est pas strictement nécessaire ici
$counter = 0;

?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div id="wcfmmp-store" class="wcfmmp-single-store-holder">
            <div id="wcfmmp-store-content" class="wcfmmp-store-page-wrap woocommerce" role="main">

                <div class="wcfm_banner_area">
                    <section class="banner_area banner_area_desktop">
                          <div class="banner_img" style="background-image: url('<?php echo esc_url($banner); ?>');"></div>
                        <div class="banner_text"><h1><?php echo esc_html( $branch_name ); ?></h1></div>
                    </section>
                </div>
                
                <div id="wcfm_store_header">
                    <div class="header_wrapper">
                        <div class="header_area">
                            <div class="lft header_left">
                                <div class="logo_area lft">
                                    <a href="#">
                                        <img src="<?php echo esc_url( $avatar ); ?>" alt="Logo">
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
                                <div class="address rgt" style="width: 1251.82px;">                                            
                                    <p class=" wcfmmp_store_header_address">
                                    <i class="wcfmfa fa-map-marker" aria-hidden="true"></i>
                                    <a href="https://google.com/maps/place/Avenue%20Pierre%20et%20Marie%20Curie%2C%2083240%20CAVALAIRE-SUR-MER%2C%20France/@43.17160958829991,6.5316724776202895&amp;z=16" target="_blank"><span><?php echo esc_html( $address ); ?></span></a>
                                    </p>
                                    <div class="">
                                        <div class="store_info_parallal wcfmmp_store_header_phone" style="margin-right: 10px;">
                                            <i class="wcfmfa fa-phone" aria-hidden="true"></i>
                                            <span>
                                            <a href="tel:<?php echo esc_attr( $phone ); ?>">
                                                <?php echo esc_html( $phone ); ?>
                                            </a>
                                            </span>
                                        </div>
                                        <div class="store_info_parallal wcfmmp_store_header_email">
                                            <i class="wcfmfa fa-envelope" aria-hidden="true"></i>
                                            <span>
                                            <a href="mailto:<?php echo esc_attr( $email ); ?>">
                                                <?php echo esc_html( $email ); ?>
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
    const mapMarkers = <?php echo wp_json_encode( $markers ); ?>;

    // Ajout des coordonnées pour le centrage de la carte
    const currentLat = <?php echo json_encode($lat); ?>;
    const currentLng = <?php echo json_encode($lng); ?>;
    const fandPickupPluginUrl = '<?php echo esc_url( FAND_PICKUP_PLUGIN_URL ); ?>';
    const isSingleView = true; // Flag pour le script général, si besoin

    // 1. Solution de sécurité pour jQuery (alias $)
    (function($) {
        $(document).ready(function() {
            // Sélecteur 1 : Le conteneur '#products-wrapper'
            const $productWrapper = $('#products-wrapper'); 

            // Sélecteur 2 : Le conteneur principal du magasin (si #products-wrapper est trop petit)
            // Essayons un conteneur plus général si #products-wrapper ne fonctionne pas.
            const $storeContent = $productWrapper.closest('.product_area'); // Remonte au parent .product_area

            $('.kadence-toggle-shop-layout').on('click', function(e) {
                e.preventDefault();
                const toggleType = $(this).data('archive-toggle'); 

                // 1. Gérer les classes actives des boutons
                $('.kadence-toggle-shop-layout').removeClass('toggle-active');
                $(this).addClass('toggle-active');

                // 2. Appliquer les classes de vue
                // On retire les anciennes classes 'list'/'grid' et on ajoute la nouvelle.
                $productWrapper.removeClass('list grid').addClass(toggleType); 
                $storeContent.removeClass('list grid').addClass(toggleType); 
                $productWrapper.find('ul.products').removeClass('list grid').addClass(toggleType); // Cible la liste des produits WooCommerce
            });

            // Initialisation au chargement
            const $activeButton = $('.kadence-toggle-shop-layout.toggle-active');
            if ($activeButton.length) {
                const defaultToggle = $activeButton.data('archive-toggle');
                $productWrapper.addClass(defaultToggle);
                $storeContent.addClass(defaultToggle);
                $productWrapper.find('ul.products').addClass(defaultToggle);
            }
        });
    })(jQuery);
    

</script>
<script src="<?php echo esc_url( FAND_PICKUP_PLUGIN_URL . 'assets/js/pickup-map-script.js' ); ?>"></script>
<?php wp_footer(); ?>