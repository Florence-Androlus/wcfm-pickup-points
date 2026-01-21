<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<li id="store-<?php echo esc_attr($fand_branch_id); ?>" class="wcfmmp-single-store item woocommerce coloum-3">
    <div class="store-wrapper">
        <div class="store-content">
            <div class="store-info" style="background-image: url('<?php echo esc_url($fand_banner_image); ?>');"></div>
        </div>
        <div class="store-footer">
            <div id="avatar-branch-<?php echo esc_attr($fand_branch_id); ?>" 
                class="store-avatar lft <?php 
                    // On n'affiche les classes de statut QUE si la version PRO est active
                    if ( defined('FAND_PICKUP_POINTS_ULTIMATE_PRO_PLUGIN_ACTIVE') && FAND_PICKUP_POINTS_ULTIMATE_PRO_PLUGIN_ACTIVE ) {
                        echo $fand_is_currently_open ? 'is-open' : 'is-closed'; 
                    }
                ?>">
                <img src="<?php echo esc_url($fand_avatar); ?>" alt="Logo">
            </div>

            <div class="store-data-container rgt">
                <div class="store-data">
                    <h2 class="branch-title"><?php echo esc_html($fand_branch_name); ?></h2>
                    <h3 class="branch-title">
                        <a href="<?php echo esc_url($fand_store_url); ?>"><?php echo esc_html($fand_vendor->display_name); ?></a>
                        <div class="wcfm_vendor_badges"></div>
                    </h3>

                    <div class="bd_rating">
                        <div class="wcfmmp-store-rating" title="Aucun avis pour le moment !">
                            <span style="width:0%"><strong class="rating">0</strong> sur 5</span>
                        </div>
                        <div class="spacer"></div>
                    </div>

                    <div class="store-contact-details">
                        <p class="store-address"><?php echo esc_html($fand_address); ?></p>
                        <?php if ($fand_email) : ?><p class="store-phone"><i class="wcfmfa fa-envelope"></i> <?php echo esc_html($fand_email); ?></p><?php endif; ?>
                        <?php if ($fand_phone) : ?><p class="store-phone"><i class="wcfmfa fa-phone"></i> <?php echo esc_html($fand_phone); ?></p><?php endif; ?>
                    </div>
                    <p class="store-enquiry">
                        <a class="wcfm_catalog_enquiry" data-store="<?php echo esc_attr($fand_vendor->ID); ?>" data-PICKUPduct="0" href="#">
                            <span class="wcfmfa fa-question-circle"></span>&nbsp;<span class="add_enquiry_label">Question</span>
                        </a>
                    </p>
                    <div class="wcfm-clearfix"></div>
                </div>
            </div>
            <div class="spacer"></div>

            <a href="<?php echo esc_url($fand_location_url); ?>" class="wcfmmp-visit-store">Visiter <span>le Magasin</span></a>
        </div>
    </div>
</li>