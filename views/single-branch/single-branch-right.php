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
                <a href="<?php echo esc_url( $fand_base_url_for_tabs . 'reviews/' ); ?>#tab_links_area">
                    Avis (<span class="wcfm_reviews_count"><?php echo $fand_data['rating_count']; ?></span>)
                </a>
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
<div class="_area" id="reviews">
    <div class="reviews_area">
        <div class="reviews_heading">Avis</div>
        
        <div class="recent_reviews">
            <div class="bd_review_section">
                <?php if ( ! empty( $fand_data['reviews'] ) ) : ?>
                    <?php foreach ( $fand_data['reviews'] as $review ) : ?>
                        <div class="review_section">
                            <div class="lft user_photo">
                                <div class="review_photo">
                                    <img src="<?php echo esc_url(site_url()); ?>/wp-content/plugins/wc-frontend-manager/assets/images/avatar.png" alt="Review">
                                </div>
                                <div class="rated">
                                    <strong>évalué</strong>
                                    <div class="user_rated"><?php echo number_format($review['rating'], 1); ?></div>
                                </div>
                            </div>

                            <div class="rgt user_review_sec">
                                <div class="user_review_sec_left" style="display: inline-block; float: left; width: 60%;">
                                    <div class="user_name"><?php echo esc_html( $review['comment_author'] ); ?></div>
                                    <div class="user_review_area">
                                        <span class="user_date"><?php echo date_i18n( 'j F Y G\hi', strtotime( $review['comment_date'] ) ); ?></span>
                                    </div>
                                    <div class="user_review_text">
                                        <p><?php echo nl2br( esc_html( $review['comment_content'] ) ); ?></p>
                                    </div>
                                </div>

                                <div class="bd_rating_area" style="float: right; width: 35%;">
                                    <?php if ( !empty($review['sub_ratings']) ) : ?>
                                        <?php foreach ( $review['sub_ratings'] as $sub ) : 
                                            $val = floatval($sub['value']);
                                        ?>
                                            <div class="rating_box">
                                                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                                    <i class="wcfmfa fa-star <?php echo ( $i <= $val ) ? 'selected' : ''; ?>" aria-hidden="true" style="color: <?php echo ( $i <= $val ) ? '#ffb600' : '#ccc'; ?>;"></i>
                                                <?php endfor; ?>
                                                <span><?php echo number_format($val, 1); ?>&nbsp;<?php echo esc_html($sub['key']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="spacer"></div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>Aucun avis pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
</div>