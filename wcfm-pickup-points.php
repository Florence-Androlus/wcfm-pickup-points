<?php
/**
 * Plugin Name: WCFM Pickup Points - FAND Addon
 * Description: Gestion avancée des points de retrait pour WCFM Marketplace. Développé par Fan-develop.
 * Version: 1.0.0
 * Author: Fan-develop
 * Text Domain: wcfm-pickup-points
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * */
 
    namespace fandWCFMPickupPoints;

    use fandWCFMPickupPoints\Classes\Helpers\PluginActivator;
    
    defined('ABSPATH') || exit;

    // Charger l'autoloader Composer
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    // Fix pour l'erreur WooCommerce Subscriptions
    add_action('plugins_loaded', function() {
        if (class_exists('WC_Subscriptions')) {
            add_filter('woocommerce_subscriptions_object_data_cache_enabled', '__return_false');
        }
    }, 5);

    add_filter('doing_it_wrong_trigger_error', '__return_false');

    define('FAND_PICKUP_VERSION', '1.0.0');
    define('FAND_PICKUP_MAIN_FILE', __FILE__);
    define('FAND_PICKUP_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('FAND_PICKUP_PLUGIN_DIR', plugin_dir_path(__FILE__));

    register_activation_hook(__FILE__, function() {
        PluginActivator::createPage();
        PluginActivator::createTables();
        flush_rewrite_rules();
    });

    // Inclure le fichier principal du plugin
    require_once FAND_PICKUP_PLUGIN_DIR . 'plugin.php';
    new FANDPickupSettings();