<?php
namespace fandWCFMPickupPoints\Classes\Admin;

class PageManager {

    public function __construct() {
            add_filter('display_post_states', [$this, 'add_pickup_post_state'], 10, 2);
    }

    public function add_pickup_post_state($post_states, $post) {
        $pickup_page_id = get_option('fand_pickup_page_id');

        if ($pickup_page_id && (int) $pickup_page_id === $post->ID) {
            $post_states['fand_pickup_page'] = __('Page Emplacements Pickup', 'wcfm-pickup-points');
        }

        return $post_states;
    }
}