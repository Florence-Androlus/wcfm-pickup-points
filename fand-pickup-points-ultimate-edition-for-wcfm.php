<?php
/**
 * Plugin Name: Fand Pickup Points : Ultimate Edition for WCFM
 * Description: Gestion avancée des points de retrait pour WCFM Marketplace. Développé par Fan-develop.
 * Version:            1.0.1
 * Requires at least:  6.9
 * Requires PHP:       8.2
 * Requires Plugins:   woocommerce,wc-frontend-manager
 * Author: Fan-develop
 * Text Domain: fand-pickup-points-ultimate-edition-for-wcfm
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * */
 
    namespace fandWCFMPickupPoints;

    use fandWCFMPickupPoints\Classes\Helpers\PluginActivator;
    
    defined('ABSPATH') || exit;

    // ON DÉCLARE LA FONCTION ICI (Avant le namespace pour qu'elle soit globale)
    function fandpipo_is_advanced_active() {
        return apply_filters( 'fandpipo_feature_status', false );
    }

    // Charger l'autoloader Composer
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    // Fix pour l'erreur WooCommerce Subscriptions
    add_action('plugins_loaded', function() {
        if (class_exists('WC_Subscriptions')) {
            add_filter('woocommerce_subscriptions_object_data_cache_enabled', '__return_false');
        }
    }, 5);

    add_filter('doing_it_wrong_trigger_error', '__return_false');

    define('FANDPIPO_VERSION', '1.0.1');
    define('FANDPIPO_MAIN_FILE', __FILE__);
    define('FANDPIPO_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('FANDPIPO_PLUGIN_DIR', plugin_dir_path(__FILE__));

    register_activation_hook(__FILE__, function() {
        PluginActivator::fandpipo_createPage();
        PluginActivator::fandpipo_createTables();
        flush_rewrite_rules();
    });

    // Inclure le fichier principal du plugin
    require_once FANDPIPO_PLUGIN_DIR . 'plugin.php';
    add_action('wp_loaded', function() {
        fandpipo_is_advanced_active(); 
    });

    new FANDPickupSettings();
