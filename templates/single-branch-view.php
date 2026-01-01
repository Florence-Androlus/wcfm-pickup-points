<?php 
// single-branch-view.php
get_header();
// Récupération des données passées
$vendor_id = get_query_var('current_vendor_id');

$profile_settings = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
$store_url = function_exists('get_wcfm_store_url') ? get_wcfm_store_url($vendor_id) : '#';
$email = get_user_meta($vendor_id, 'billing_email', true);
$phone = get_user_meta($vendor_id, 'billing_phone', true);

$branch_data = get_query_var( 'current_branch_data' );

// Récupérer l'image
$gravatar_id = isset($profile_settings['gravatar']) ? $profile_settings['gravatar'] : 0;
$avatar = wp_get_attachment_url($gravatar_id);
$branch_id = $branch_data['branch_id'];
$branch_name = esc_html( $branch_data['branch_name'] );
$city = $branch_data['city'] ?? '';
$postal = $branch_data['postal_code'] ?? '';
$address =  esc_html( $branch_data['address'] ?? 'Adresse non spécifiée' ) . ' ' .strtoupper($postal) . ' ' . $city . ', France';

$lat = $branch_data['lat'] ?? 0; 
$lng = $branch_data['lng'] ?? 0;

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
// Récupère toutes les catégories de produits utilisées par ce vendeur spécifique.
// On utilise get_terms pour une recherche plus spécifique.
$vendor_products_query = new WP_Query( array(
    'fields'         => 'ids', // On ne veut que les ID
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'author'         => $vendor_id, // Filtrage crucial par Vendeur
    'posts_per_page' => -1,
) );

$product_ids_by_vendor = $vendor_products_query->posts;

// Initialiser la liste des catégories à afficher
$category_terms_to_show = array();

if ( ! empty( $product_ids_by_vendor ) ) {
    // --- 2. Récupérer les termes (catégories) associés à ces ID de produits ---
    $category_terms_to_show = wp_get_object_terms( $product_ids_by_vendor, 'product_cat', array(
        'fields'     => 'all',
        'orderby'    => 'name',
        'order'      => 'ASC',
        'hide_empty' => true,
        'parent'     => 0, // Pour n'afficher que les catégories principales (Top-Level)
    ) );
}

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

$counter = 0;

?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div id="wcfmmp-store" class="wcfmmp-single-store-holder">
            <div id="wcfmmp-store-content" class="wcfmmp-store-page-wrap woocommerce" role="main">

                <div class="wcfm_banner_area">
                    <section class="banner_area banner_area_desktop">
                        <div class="banner_img" style="background-image: url('<?php echo esc_url(plugins_url('wc-multivendor-marketplace/assets/images/default_banner.jpg')); ?>');"></div>
                        <div class="banner_text"><h1><?php echo $branch_name; ?></h1></div>
                    </section>
                </div>
                
                <div id="wcfm_store_header">
                    <div class="header_wrapper">
                        <div class="header_area">
                            <div class="lft header_left">
                                <div class="logo_area lft">
                                    <a href="#">
                                        <img decoding="async" src="<?php echo esc_url($avatar); ?>" alt="Logo">
                                    </a>
                                </div>
                                <div class="logo_area_after">
										
                                    <div style="" class="wcfmmp-store-rating" title="Aucun avis pour le moment !">
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
                                    <a href="https://google.com/maps/place/Avenue%20Pierre%20et%20Marie%20Curie%2C%2083240%20CAVALAIRE-SUR-MER%2C%20France/@43.17160958829991,6.5316724776202895&amp;z=16" target="_blank"><span><?php echo $address;?></span></a>
                                    </p>
                                    <div class="">
                                        <div class="store_info_parallal wcfmmp_store_header_phone" style="margin-right: 10px;">
                                            <i class="wcfmfa fa-phone" aria-hidden="true"></i>
                                            <span>
                                            <a href="tel:+33624662544"><?php echo $phone;?></a>
                                            </span>
                                        </div>
                                        <div class="store_info_parallal wcfmmp_store_header_email">
                                            <i class="wcfmfa fa-envelope" aria-hidden="true"></i>
                                            <span>
                                            <a href="mailto:contact@fan-services.fr"><?php echo $email;?></a>
                                            </span>
                                        </div>  
                                        <div class="spacer"></div>  
                                    </div>
                                </div>
                            </div>
                            <div class="header_right">
                                <div class="bd_icon_area lft">

                                    <div class="lft bd_icon_box"><a class="wcfm_store_enquiry " data-store="5" data-product="0" href="#"><i class="wcfmfa fa-question" aria-hidden="true"></i><span>Question</span></a></div>


                                    <div class="lft bd_icon_box"><a id="wcfm_follow_now" title="Click to Follow" data-count="0" data-vendor_id="5" data-user_id="1" href="#" class="follow"><i class="wcfmfa fa-user-plus"></i>&nbsp;<span>Follow</span></a></div>
                                    <script>
                                    jQuery(document).ready(function($) {
                                    $('#wcfm_follow_now').one('click', function(event) {
                                    event.preventDefault();

                                    $user_id   = $(this).data('user_id');
                                    $vendor_id = $(this).data('vendor_id');
                                    $count     = $(this).data('count');

                                    $('#wcfm_store_header').block({
                                    message: null,
                                    overlayCSS: {
                                    background: '#fff',
                                    opacity: 0.6
                                    }
                                    });
                                    var data = {
                                    action    : 'wcfmu_vendors_followers_update',
                                    user_id   : $user_id,
                                    vendor_id : $vendor_id,
                                    count     : $count,
                                    wcfm_ajax_nonce : wcfm_params.wcfm_ajax_nonce,
                                    }    
                                    $.post(wcfm_params.ajax_url, data, function(response) {
                                    if(response) {
                                    $count = $count + 1;
                                    $('.wcfm_followers_count').text( $count );
                                    $('#wcfm_follow_now').hide();
                                    $('#wcfm_store_header').unblock();
                                    window.location.hash = 'tab_links_area';
                                    window.location.reload();
                                    }
                                    });

                                    return false;
                                    });
                                    });
                                    </script>
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
                        <div id="tabsWithStyle" class="tab_area">
                                <div id="tab_links_area" class="tab_links_area" tabindex="-1" spellcheck="false">
                                    <ul class="tab_links">

                                        <li class="<?php echo ( $active_tab == 'products' ) ? 'active' : ''; ?>">
                                            <a href="<?php echo esc_url( $base_url_for_tabs ); ?>#tab_links_area">Produits</a>
                                        </li>
                                        
                                        <li class="<?php echo ( $active_tab == 'about' ) ? 'active' : ''; ?>">
                                            <a href="<?php echo esc_url( $base_url_for_tabs . 'about/' ); ?>#tab_links_area">à propos</a>
                                        </li>
                                        
                                        <li class="<?php echo ( $active_tab == 'policies' ) ? 'active' : ''; ?>">
                                            <a href="<?php echo esc_url( $base_url_for_tabs . 'policies/' ); ?>#tab_links_area">Politiques</a>
                                        </li>
                                        
                                        <li class="<?php echo ( $active_tab == 'reviews' ) ? 'active' : ''; ?>">
                                            <a href="<?php echo esc_url( $base_url_for_tabs . 'reviews/' ); ?>#tab_links_area">Avis (<span class="wcfm_reviews_count">0</span>)</a>
                                        </li>
                                        <li class="<?php echo ( $active_tab == 'followers' ) ? 'active' : ''; ?>">
                                            <a href="<?php echo esc_url( $base_url_for_tabs . 'followers/' ); ?>#tab_links_area">Suiveurs "Followers" (<span class="wcfm_followers_count">1</span>)</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="wcfm-clearfix"></div>
                                <?php if ( $active_tab == 'products' ) : ?>
                                <div class="" id="products">
                                    <div class="product_area">

                                        <div id="products-wrapper" class="products-wrapper">
                                            
                                            <?php 
                                            global $wp_query; 

                                            // ... (Définition de $args et $products_query inchangée) ...
                                            $args = array(
                                                'post_type'      => 'product',
                                                'post_status'    => 'publish',
                                                'posts_per_page' => 12, 
                                                'author'         => $vendor_id, 
                                                'paged'          => ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1,
                                            );
                                            // ==========================================================
                                            // === LOGIQUE POUR LE FILTRE PAR CATÉGORIE (AJOUTEZ CECI) ===
                                            // ==========================================================
                                            if ( isset( $_GET['product_cat'] ) && !empty( $_GET['product_cat'] ) ) {
                                                $current_cat_slug = sanitize_text_field( $_GET['product_cat'] );
                                                
                                                // Ajoute le paramètre de taxonomie à la requête pour filtrer les produits
                                                $args['tax_query'] = array(
                                                    array(
                                                        'taxonomy' => 'product_cat', // Nom de la taxonomie (catégories WooCommerce)
                                                        'field'    => 'slug',        // On filtre par le slug (celui dans l'URL)
                                                        'terms'    => $current_cat_slug, // Le slug de la catégorie sélectionnée
                                                    ),
                                                );
                                            }
                                            // ==========================================================
                                            // === FIN DE L'AJOUT ===
                                            // ==========================================================
                                            
                                            // Logique de tri pour prix/date (laissez-la ici)
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

                                            $products = new WP_Query( $args );

                                            if ( $products->have_posts() ) :
                                                // 1. Sauvegarder l'état actuel de la requête globale
                                                $original_wp_query = $wp_query; // C'est essentiel !

                                                // 2. Remplacer la requête globale par la nôtre pour le tri et le comptage
                                                $wp_query = $products; // C'est ici que vous "injectez" les produits
                                                // Le code ci-dessus définit normalement les propriétés, mais si ça ne suffit pas :
                                                wc_set_loop_prop( 'is_main_query', false ); // Pour s'assurer qu'il ne s'agit pas de la requête principale
                                                wc_set_loop_prop( 'total', $products->found_posts ); // Définir manuellement le nombre total
                                                wc_set_loop_prop( 'current_page', $paged ); // Définir la page actuelle
                                                // *** FIN DU BLOC À AJOUTER ***
                                                $count = $products->found_posts;
                                                $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
                                                ?>
                                                    <?php do_action( 'wcfmmp_before_store_product', $vendor_id, $store_info ); ?>
                                                    
                                                    <?php if ( woocommerce_product_loop() ) { ?>
                                                        
                                                        <?php do_action( 'wcfmmp_woocommerce_before_shop_loop_before', $vendor_id, $store_info ); ?>
                                                        <?php do_action( 'woocommerce_before_shop_loop' ); ?>
                                                        <?php do_action( 'wcfmmp_woocommerce_before_shop_loop_after', $vendor_id, $store_info ); ?>
                                                        
                                                        <?php do_action( 'flatsome_category_title_alt'); // Flatsome Catalog support ?>
                                                        <?php do_action( 'wcfmmp_before_store_product_loop', $vendor_id, $store_info ); 
                                                        ?>
                                                        
                                                        <?php woocommerce_product_loop_start(); ?>
                                                        
                                                            <?php if ( wc_get_loop_prop( 'total' ) ) { ?>
                                                                
                                                                <?php do_action( 'wcfmmp_after_store_product_loop_start', $vendor_id, $store_info ); ?>
                                                                
                                                                <?php while ( have_posts() ) { the_post(); ?>
                                                                    
                                                                    <?php do_action( 'wcfmmp_store_product_loop_in_before', $vendor_id, $store_info, $counter ); ?>
                                                                    
                                                                    <?php wc_get_template_part( 'content', 'product' ); ?>
                                                                    
                                                                    <?php do_action( 'wcfmmp_store_product_loop_in_after', $vendor_id, $store_info, $counter ); ?>
                                                                    
                                                                    <?php $counter++; ?>
                                                    
                                                                <?php }  ?>
                                                                
                                                                <?php do_action( 'wcfmmp_before_store_product_loop_end', $vendor_id, $store_info ); ?>
                                                                
                                                            <?php } ?>
                                                            
                                                        <?php if( function_exists( 'listify_php_compat_notice') ) { ?>
                                                            </div>
                                                        <?php } else { ?>
                                                            <?php woocommerce_product_loop_end(); ?>
                                                        <?php } ?>
                                                        
                                                        <?php do_action( 'wcfmmp_after_store_product_loop', $vendor_id, $store_info ); ?>
                                                        
                                                        <?php do_action( 'wcfmmp_woocommerce_after_shop_loop_before', $vendor_id, $store_info ); ?>
                                                        <?php do_action( 'woocommerce_after_shop_loop' ); ?>
                                                        <?php do_action( 'wcfmmp_woocommerce_after_shop_loop_after', $vendor_id, $store_info ); ?>
                                                        
                                                        <?php //wcfmmp_content_nav( 'nav-below' ); ?>
                                                
                                                    <?php } else { ?>
                                                        <?php do_action( 'woocommerce_no_products_found' ); ?>
                                                    <?php } ?>
                                                    
                                                    <?php do_action( 'wcfmmp_after_store_product', $vendor_id, $store_info ); ?>
                                                    
                                                
                                                </div><!-- #products -->
                                                </div><!-- .product_area -->

                                                <?php do_action( 'wcfmmp_store_after_products', $vendor_id ); ?>
                                            <?php endif; ?>
                            <?php elseif ( $active_tab == 'about' ) : ?>
                            <div class="_area" id="wcfmmp_store_about">
                                <div class="wcfmmp-store-description">
                                    <div class="wcfm-store-about">
                                        <h2>À Propos de <?php echo $branch_name; ?></h2>
                                        <div class="wcfm_store_description">
                                            <?php $WCFMmp->template->get_template( 'store/wcfmmp-view-store-about.php', array( 'store_user' => $store_user, 'store_info' => $store_info ) );?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php elseif ( $active_tab == 'policies' ) : ?>
                                <div class="_area" id="wcfmmp_store_policies">
                                    <div class="wcfmmp-store-description">
                                        <h2>Politiques de l'emplacement</h2>
                                        <?php $WCFMmp->template->get_template( 'store/wcfmmp-view-store-policies.php', array( 'store_user' => $store_user, 'store_info' => $store_info ) );?>
                                    </div>
                                </div>
                                
                            <?php elseif ( $active_tab == 'reviews' ) : ?>
                                <div class="_area" id="wcfmmp_store_<?php echo $active_tab; ?>">
                                    <h2>Contenu <?php echo ucfirst($active_tab); ?></h2>
                                    <?php $WCFMmp->template->get_template( 'store/wcfmmp-view-store-reviews.php', array( 'store_user' => $store_user, 'store_info' => $store_info ) );?>
                                </div>
                            <?php elseif ( $active_tab == 'followers' ) : ?>
                                <div class="_area" id="wcfmmp_store_<?php echo $active_tab; ?>">
                                    <h2>Contenu <?php echo ucfirst($active_tab); ?></h2>
                                    <?php $WCFMmp->template->get_template( 'store/wcfmmp-view-store-followers.php', array( 'store_user' => $store_user, 'store_info' => $store_info ) );?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="lft left_sidebar widget-area sidebar"> 
                    <aside class="widget"><div class="sidebar_heading"><h4 class="widget-title">Chercher</h4></div>
                        <form role="search" method="get" class="woocommerce-product-search" action="">
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
                            <ul>
                                <?php 
                                // 1. Lien "Toutes les catégories" pour réinitialiser le filtre
                                $all_cat_class = empty( $current_cat_slug ) ? 'active' : '';
                                ?>
                                <li class="parent_cat <?php echo $all_cat_class; ?>">
                                    <a class="" href="<?php echo esc_url( $base_url_for_filter ); ?>">
                                        Toutes les catégories
                                    </a>
                                </li>

                                <?php
                                    // 2. Boucle pour afficher chaque catégorie du vendeur
                                    if ( ! is_wp_error( $category_terms_to_show ) && ! empty( $category_terms_to_show ) ) :
                                                    foreach ( $category_terms_to_show as $category ) :
                                            $cat_slug = $category->slug;
                                            $cat_name = $category->name;
                                            
                                            // Construit le lien de filtrage
                                            $category_filter_url = add_query_arg( 'product_cat', $cat_slug, $base_url_for_filter );
                                            
                                            // Détermine la classe active
                                            $active_class = ( $current_cat_slug === $cat_slug ) ? 'active' : '';
                                            ?>
                                            <li class="parent_cat <?php echo $active_class; ?>">
                                                <a class="" href="<?php echo esc_url( $category_filter_url ); ?>">
                                                    <?php echo esc_html( $cat_name ); ?>
                                                </a>
                                            </li>
                                        <?php
                                        endforeach;
                                    endif;
                                ?>
                            </ul>
                        </div>
                    </aside>		
                        
                    <aside class="widget">
                        <div class="sidebar_heading"><h4 class="widget-title">Emplacement du Pickup</h4></div>
                        <div id="branch-map" style="height: 383px;">
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
                    ?>
                    <?php
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
                                                <span style="font-weight: 600;"><?php echo $day_name; ?> :</span>
                                                <span style="<?php echo $css_style; ?> text-align: right;"><?php echo $hours_display; ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </aside>
                            <?php
                        }
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
    
    // 2. Logique de la Carte (Leaflet) - Reste inchangée si elle fonctionne
    document.addEventListener('DOMContentLoaded', function() {
        const lat = JSON.parse(<?php echo json_encode($lat); ?>);
        const lng = JSON.parse(<?php echo json_encode($lng); ?>);
        const branchName = <?php echo json_encode($branch_name); ?>;
        
        // Le check 'L' s'assure que la librairie Leaflet est chargée
        if (lat && lng && typeof L !== 'undefined') { 
            const map = L.map('branch-map').setView([lat, lng], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            L.marker([lat, lng]).addTo(map)
                .bindPopup(branchName)
                .openPopup();
        }
    });
</script>
<?php wp_footer(); ?>