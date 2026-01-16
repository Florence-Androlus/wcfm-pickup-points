<?php
/**
 * Plugin Name: Pickup Points : Ultimate Edition - FAND Addon
 * Description: Gestion avancée des points de retrait pour WCFM Marketplace. Développé par Fan-develop.
 * Version:            1.0.0
 * Requires at least:  6.9
 * Requires PHP:       8.2
 * Author: Fan-develop
 * Text Domain: pickup-points-ultimate
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * */
 
    namespace fandWCFMPickupPoints;

    use fandWCFMPickupPoints\Classes\Helpers\PluginActivator;
    
    defined('ABSPATH') || exit;

    if ( ! function_exists( 'wcfm_pickup_is_premium_active' ) ) {
        function wcfm_pickup_is_premium_active() {
            // 1. On essaie de récupérer le résultat du cache (le transient)
            $is_active = get_transient('fand_pp_licence_check');

            // 2. Si le cache est vide (false), c'est qu'il est temps de vérifier
            if ( false === $is_active ) {
                
                // On récupère la valeur réelle en base de données
                $status = get_option('fand_pp_licence_status', 'inactive');
                $is_active = ( $status === 'active' ) ? 'yes' : 'no';

                // 3. On enregistre le résultat dans le cache pour 24 heures (DAY_IN_SECONDS)
                set_transient('fand_pp_licence_check', $is_active, DAY_IN_SECONDS);
                
                //error_log('WCFM Pickup : Vérification réelle effectuée et mise en cache pour 24h.');
            }

            // On retourne le résultat (on compare à 'yes' car un transient ne stocke pas bien true/false)
            return ( $is_active === 'yes' );
        }
    }

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
    define('FAND_ULTIMAT_PRO_PLUGIN', 'pickup-points-ultimate-pro/pickup-points-ultimate-pro.php');
    register_activation_hook(__FILE__, function() {
        PluginActivator::createPage();
        PluginActivator::createTables();
        flush_rewrite_rules();
    });


    // Vérification si la version Pro est active
    if (is_plugin_active(FAND_ULTIMAT_PRO_PLUGIN)) {
        define('FAND_PICKUP_PLUGIN_ACTIVE', true);
    } else {
        define('FAND_PICKUP_PLUGIN_ACTIVE', false);
    }

    // Inclure le fichier principal du plugin
    require_once FAND_PICKUP_PLUGIN_DIR . 'plugin.php';
    new FANDPickupSettings();
