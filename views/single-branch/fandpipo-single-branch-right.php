<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<div id="tabsWithStyle" class="tab_area">
    <div id="tab_links_area" class="tab_links_area" tabindex="-1" spellcheck="false">
        <ul class="tab_links">
            <li class="<?php echo esc_attr( ( $fandpipo_active_tab == 'products' ) ? 'active' : '' ); ?>">
                <a href="<?php echo esc_url( $fandpipo_base_url_for_tabs ); ?>#tab_links_area">Produits</a>
            </li>
            <li class="<?php echo esc_attr( ( $fandpipo_active_tab == 'reviews' ) ? 'active' : '' ); ?>">
                <a href="<?php echo esc_url( $fandpipo_base_url_for_tabs . 'reviews/' ); ?>#tab_links_area">
                    Avis (<span class="wcfm_reviews_count"><?php echo esc_html( $fandpipo_data['rating_count'] ); ?></span>)
                </a>
            </li>
        </ul>
    </div>
    <div class="wcfm-clearfix"></div>

    <?php if ( $fandpipo_active_tab == 'products' ) : ?>
        <div class="" id="products">
            <div class="product_area">
                <div id="products-wrapper" class="products-wrapper">
                    <?php 
                    if ( $fandpipo_products->have_posts() ) :
                        $fandpipo_count = $fandpipo_products->found_posts;
                        $fandpipo_paged = $fandpipo_products->query_vars['paged']; 
                        ?>
                        
                        <?php do_action( 'wcfmmp_before_store_product', $fandpipo_vendor_id, $fandpipo_store_info ); ?>
                        
                        <?php if ( woocommerce_product_loop() ) { ?>
                            
                            <?php do_action( 'wcfmmp_woocommerce_before_shop_loop_before', $fandpipo_vendor_id, $fandpipo_store_info ); ?>
                            
                            <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>
                            <?php do_action( 'woocommerce_before_shop_loop' ); ?>
                            
                            <?php do_action( 'wcfmmp_woocommerce_before_shop_loop_after', $fandpipo_vendor_id, $fandpipo_store_info ); ?>
                            
                            <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>
                            <?php do_action( 'flatsome_category_title_alt'); ?>
                            
                            <?php do_action( 'wcfmmp_before_store_product_loop', $fandpipo_vendor_id, $fandpipo_store_info ); ?>
                            
                            <?php woocommerce_product_loop_start(); ?>
                            
                                <?php if ( wc_get_loop_prop( 'total' ) ) { ?>
                                    
                                    <?php do_action( 'wcfmmp_after_store_product_loop_start', $fandpipo_vendor_id, $fandpipo_store_info ); ?>
                                    
                                    <?php while ( have_posts() ) { the_post(); ?>
                                        
                                        <?php do_action( 'wcfmmp_store_product_loop_in_before', $fandpipo_vendor_id, $fandpipo_store_info, $fandpipo_counter ); ?>
                                        
                                        <?php wc_get_template_part( 'content', 'product' ); ?>
                                        
                                        <?php do_action( 'wcfmmp_store_product_loop_in_after', $fandpipo_vendor_id, $fandpipo_store_info, $fandpipo_counter ); ?>
                                        
                                        <?php $fandpipo_counter++; ?>
                                    
                                    <?php }  ?>
                                    
                                    <?php do_action( 'wcfmmp_before_store_product_loop_end', $fandpipo_vendor_id, $fandpipo_store_info ); ?>
                                    
                                <?php } ?>
                                
                            <?php if( function_exists( 'listify_php_compat_notice') ) { ?>
                                    </div>
                            <?php } else { ?>
                                    <?php woocommerce_product_loop_end(); ?>
                            <?php } ?>
                            
                            <?php do_action( 'wcfmmp_after_store_product_loop', $fandpipo_vendor_id, $fandpipo_store_info ); ?>
                            <?php do_action( 'wcfmmp_woocommerce_after_shop_loop_before', $fandpipo_vendor_id, $fandpipo_store_info ); ?>
                            
                            <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>
                            <?php do_action( 'woocommerce_after_shop_loop' ); ?>
                            
                            <?php do_action( 'wcfmmp_woocommerce_after_shop_loop_after', $fandpipo_vendor_id, $fandpipo_store_info ); ?>
                        
                        <?php } else { ?>
                            <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>
                            <?php do_action( 'woocommerce_no_products_found' ); ?>
                        <?php } ?>
                        
                        <?php do_action( 'wcfmmp_after_store_product', $fandpipo_vendor_id, $fandpipo_store_info ); ?>
                        
                    <?php else : ?>
                        <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>
                        <?php do_action( 'woocommerce_no_products_found' ); ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php do_action( 'wcfmmp_store_after_products', $fandpipo_vendor_id ); ?>
        </div>

    <?php elseif ( $fandpipo_active_tab === 'about' ): ?>
        <div class="_area" id="wcfmmp_store_about">
            <div class="wcfmmp-store-description">
                <div class="wcfm-store-about">
                    <h2>À Propos de <?php echo esc_html( $fandpipo_branch_name ); ?></h2>
                    <div class="wcfm_store_description">
                        <?php 
                        $WCFMmp->template->get_template( 'store/wcfmmp-view-store-about.php', array( 'store_user' => $fand_store_user, 'store_info' => $fandpipo_store_info ) );
                        ?>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ( $fandpipo_active_tab == 'policies' ) : ?>
        <div class="_area" id="wcfmmp_store_policies">
            <div class="wcfmmp-store-description">
                <h2>Politiques de l'emplacement</h2>
                <?php $WCFMmp->template->get_template( 'store/wcfmmp-view-store-policies.php', array( 'store_user' => $fand_store_user, 'store_info' => $fandpipo_store_info ) );?>
            </div>
        </div>
        
    <?php elseif ( $fandpipo_active_tab == 'reviews' ) : ?>
        <div class="_area" id="reviews">
            <div class="reviews_area">
                <div class="reviews_heading">Avis</div>
                <div class="recent_reviews">
                    <div class="bd_review_section">
                        <?php if ( ! empty( $fandpipo_data['reviews'] ) ) : ?>
                            <?php foreach ( $fandpipo_data['reviews'] as $fandpipo_review ) : ?>
                                <div class="review_section">
                                    <div class="lft user_photo">
                                        <div class="review_photo">
                                            <img src="<?php echo esc_url(site_url()); ?>/wp-content/plugins/wc-frontend-manager/assets/images/avatar.png" alt="Review">
                                        </div>
                                        <div class="rated">
                                            <strong>évalué</strong>
                                            <div class="user_rated"><?php echo number_format($fandpipo_review['rating'], 1); ?></div>
                                        </div>
                                    </div>

                                    <div class="rgt user_review_sec">
                                        <div class="user_review_sec_left" style="display: inline-block; float: left; width: 60%;">
                                            <div class="user_name"><?php echo esc_html( $fandpipo_review['comment_author'] ); ?></div>
                                            <div class="user_review_area">
                                                <span class="user_date"><?php echo esc_html( date_i18n( 'j F Y G\hi', strtotime( $fandpipo_review['comment_date'] ) ) ); ?></span>
                                            </div>
                                            <div class="user_review_text">
                                                <p><?php echo nl2br( esc_html( $fandpipo_review['comment_content'] ) ); ?></p>
                                            </div>
                                        </div>

                                        <div class="bd_rating_area" style="float: right; width: 35%;">
                                            <?php if ( !empty($fandpipo_review['sub_ratings']) ) : ?>
                                                <?php foreach ( $fandpipo_review['sub_ratings'] as $fandpipo_sub ) : 
                                                    $fandpipo_val = floatval($fandpipo_sub['value']);
                                                ?>
                                                    <div class="rating_box">
                                                        <?php for ( $fandpipo_i = 1; $fandpipo_i <= 5; $fandpipo_i++ ) : ?>
                                                            <i class="wcfmfa fa-star <?php echo ( $fandpipo_i <= $fandpipo_val ) ? 'selected' : ''; ?>" aria-hidden="true" style="color: <?php echo ( $fandpipo_i <= $fandpipo_val ) ? '#ffb600' : '#ccc'; ?>;"></i>
                                                        <?php endfor; ?>
                                                        <span><?php echo number_format($fandpipo_val, 1); ?>&nbsp;<?php echo esc_html($fandpipo_sub['key']); ?></span>
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