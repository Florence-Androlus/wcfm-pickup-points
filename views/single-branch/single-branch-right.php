<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<div id="tabsWithStyle" class="tab_area">
    <div id="tab_links_area" class="tab_links_area" tabindex="-1" spellcheck="false">
        <ul class="tab_links">
            <li class="<?php echo esc_attr( ( $fand_active_tab == 'products' ) ? 'active' : '' ); ?>">
                <a href="<?php echo esc_url( $fand_base_url_for_tabs ); ?>#tab_links_area">Produits</a>
            </li>
            <li class="<?php echo esc_attr( ( $fand_active_tab == 'about' ) ? 'active' : '' ); ?>">
                <a href="<?php echo esc_url( $fand_base_url_for_tabs . 'about/' ); ?>#tab_links_area">à propos</a>
            </li>
            <li class="<?php echo esc_attr( ( $fand_active_tab == 'policies' ) ? 'active' : '' ); ?>">
                <a href="<?php echo esc_url( $fand_base_url_for_tabs . 'policies/' ); ?>#tab_links_area">Politiques</a>
            </li>
            <li class="<?php echo esc_attr( ( $fand_active_tab == 'reviews' ) ? 'active' : '' ); ?>">
                <a href="<?php echo esc_url( $fand_base_url_for_tabs . 'reviews/' ); ?>#tab_links_area">Avis (<span class="wcfm_reviews_count">0</span>)</a>
            </li>
        </ul>
    </div>
    <div class="wcfm-clearfix"></div>

    <?php if ( $fand_active_tab == 'products' ) : ?>
        <div class="" id="products">
            <div class="product_area">

                <div id="products-wrapper" class="products-wrapper">
                    
                    <?php 
                    // Nous n'avons plus besoin de définir $args et $fand_products_query, 
                    // car la variable $fand_products est passée du fichier parent.
                    
                    // L'objet $fand_products est déjà une WP_Query exécutée et injectée dans $wp_query dans le parent.
                    // On peut simplement faire la boucle :
                    if ( $fand_products->have_posts() ) :
                                                // $fand_count et $fand_paged sont disponibles ou peuvent être récupérés de $fand_products
                        $fand_count = $fand_products->found_posts;
                        $fand_paged = $fand_products->query_vars['paged']; // Utiliser paged de la query
                        ?>
                        
                        <?php do_action( 'wcfmmp_before_store_product', $fand_vendor_id, $fand_store_info ); ?>
                        
                        <?php if ( woocommerce_product_loop() ) { ?>
                            
                            <?php do_action( 'wcfmmp_woocommerce_before_shop_loop_before', $fand_vendor_id, $fand_store_info ); ?>
                            <?php 
                            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                            do_action( 'woocommerce_before_shop_loop' ); 
                            ?>
                            <?php do_action( 'wcfmmp_woocommerce_before_shop_loop_after', $fand_vendor_id, $fand_store_info ); ?>
                            
                            <?php 
                            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                            do_action( 'flatsome_category_title_alt'); 
                            ?>
                            <?php do_action( 'wcfmmp_before_store_product_loop', $fand_vendor_id, $fand_store_info ); ?>
                            
                            <?php woocommerce_product_loop_start(); ?>
                            
                                <?php if ( wc_get_loop_prop( 'total' ) ) { ?>
                                    
                                    <?php do_action( 'wcfmmp_after_store_product_loop_start', $fand_vendor_id, $fand_store_info ); ?>
                                    
                                    <?php while ( have_posts() ) { the_post(); ?>
                                        
                                        <?php do_action( 'wcfmmp_store_product_loop_in_before', $fand_vendor_id, $fand_store_info, $fand_counter ); ?>
                                        
                                        <?php wc_get_template_part( 'content', 'product' ); ?>
                                        
                                        <?php do_action( 'wcfmmp_store_product_loop_in_after', $fand_vendor_id, $fand_store_info, $fand_counter ); ?>
                                        
                                        <?php $fand_counter++; ?>
                                    
                                    <?php }  ?>
                                    
                                    <?php do_action( 'wcfmmp_before_store_product_loop_end', $fand_vendor_id, $fand_store_info ); ?>
                                    
                                <?php } ?>
                                
                            <?php if( function_exists( 'listify_php_compat_notice') ) { ?>
                                    </div>
                            <?php } else { ?>
                                    <?php woocommerce_product_loop_end(); ?>
                            <?php } ?>
                            
                            <?php do_action( 'wcfmmp_after_store_product_loop', $fand_vendor_id, $fand_store_info ); ?>
                            
                            <?php do_action( 'wcfmmp_woocommerce_after_shop_loop_before', $fand_vendor_id, $fand_store_info ); ?>
                            <?php 
                            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                            do_action( 'woocommerce_after_shop_loop' ); 
                            ?>
                            <?php do_action( 'wcfmmp_woocommerce_after_shop_loop_after', $fand_vendor_id, $fand_store_info ); ?>
                            
                            <?php //wcfmmp_content_nav( 'nav-below' ); ?>
                        
                        <?php } else { ?>
                            <?php 
                            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                            do_action( 'woocommerce_no_products_found' ); 
                            ?>
                        <?php } ?>
                        
                        <?php do_action( 'wcfmmp_after_store_product', $fand_vendor_id, $fand_store_info ); ?>
                        
                    
                    <?php else : ?>
                        <?php 
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
                        do_action( 'woocommerce_no_products_found' ); 
                        ?>
                    <?php endif; ?>
                    
                </div></div><?php do_action( 'wcfmmp_store_after_products', $fand_vendor_id ); ?>
            <?php elseif ( $fand_active_tab === 'about' ): ?>
        <div class="_area" id="wcfmmp_store_about">
            <div class="wcfmmp-store-description">
                <div class="wcfm-store-about">
                    <h2>À Propos de <?php echo esc_html( $fand_branch_name ); ?></h2>
                    <div class="wcfm_store_description">
                        <?php 
                        // Ceci est un appel de fonction (méthode de classe), cela reste ici
                        $WCFMmp->template->get_template( 'store/wcfmmp-view-store-about.php', array( 'store_user' => $fand_store_user, 'store_info' => $fand_store_info ) );
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php elseif ( $fand_active_tab == 'policies' ) : ?>
        <div class="_area" id="wcfmmp_store_policies">
            <div class="wcfmmp-store-description">
                <h2>Politiques de l'emplacement</h2>
                <?php $WCFMmp->template->get_template( 'store/wcfmmp-view-store-policies.php', array( 'store_user' => $fand_store_user, 'store_info' => $fand_store_info ) );?>
            </div>
        </div>
        
        <?php elseif ( $fand_active_tab == 'reviews' ) : ?>
            <div class="_area" id="wcfmmp_store_<?php echo esc_attr( $fand_active_tab ); ?>">
                <h2>Contenu <?php echo esc_html( ucfirst( $fand_active_tab ) ); ?></h2>
            </div>
            <?php $WCFMmp->template->get_template( 'store/wcfmmp-view-store-reviews.php', array( 'store_user' => $fand_store_user, 'store_info' => $fand_store_info ) );?>
        </div>
    <?php endif; ?>
</div>