<?php
/**
 * Plugin Name: Fand Pickup Points : Ultimate Edition for WCFM
 * Description: Gestion avancée des points de retrait pour WCFM Marketplace. Développé par Fan-develop.
 * Version:            1.0.0
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
    delete_transient('fandpipo_licence_check');
    if ( ! function_exists( 'fandpipo_is_premium_active' ) ) {
        function fandpipo_is_premium_active() {
            // 1. On essaie de récupérer le résultat du cache (le transient)
            $is_active = get_transient('fandpipo_licence_check');

            // 2. Si le cache est vide (false), c'est qu'il est temps de vérifier
            if ( false === $is_active ) {
                
                // On récupère la valeur réelle en base de données
                $status = get_option('fandpipo_licence_status', 'inactive');
                $is_active = ( $status === 'active' ) ? 'yes' : 'no';

                // 3. On enregistre le résultat dans le cache pour 24 heures (DAY_IN_SECONDS)
                set_transient('fandpipo_licence_check', $is_active, DAY_IN_SECONDS);
                
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

    define('FANDPIPO_VERSION', '1.0.0');
    define('FANDPIPO_MAIN_FILE', __FILE__);
    define('FANDPIPO_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('FANDPIPO_PLUGIN_DIR', plugin_dir_path(__FILE__));
    define('FANDPIPO_PRO_PLUGIN', 'fand-pickup-points-ultimate-pro/pickup-points-ultimate-pro.php');

    register_activation_hook(__FILE__, function() {
        PluginActivator::fandpipo_createPage();
        PluginActivator::fandpipo_createTables();
        flush_rewrite_rules();
    });

    // On ne définit le statut à FALSE que si le PRO n'est pas là pour le faire
    if (!is_plugin_active(FANDPIPO_PRO_PLUGIN)) {
        if (!defined('FANDPIPO_PRO_LICENCE_STATUS')) {
            define('FANDPIPO_PRO_LICENCE_STATUS', false);
        }
    }

    // Maintenant on crée l'interrupteur général
    // On vérifie si la constante a été définie (par le pro ou par le bloc au-dessus)
    if (is_plugin_active(FANDPIPO_PRO_PLUGIN) && $final_status) {
        define('FANDPIPO_PRO_PLUGIN_ACTIVE', true);
    } else {
        define('FANDPIPO_PRO_PLUGIN_ACTIVE', false);
    }
    
    // Sécurité : Si le pro est absent, on définit des valeurs par défaut vides
    if ( ! defined( 'FANDPIPO_PRO_PLUGIN_URL' ) ) {
        define( 'FANDPIPO_PRO_PLUGIN_URL', '' );
    }
    if ( ! defined( 'FANDPIPO_PRO_PLUGIN_ACTIVE' ) ) {
        define( 'FANDPIPO_PRO_PLUGIN_ACTIVE', false );
    }

    // Inclure le fichier principal du plugin
    require_once FANDPIPO_PLUGIN_DIR . 'plugin.php';
    new FANDPickupSettings();
