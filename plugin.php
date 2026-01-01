<?php

namespace fandWCFMPickupPoints;

use fandWCFMPickupPoints\Classes\Admin\Scripts;
use fandWCFMPickupPoints\Classes\Admin\PageManager;
use fandWCFMPickupPoints\Classes\Controllers\RoutesController;
use fandWCFMPickupPoints\Classes\Controllers\ShortcodesController;
use fandWCFMPickupPoints\Classes\Controllers\pickuphoursController;
use fandWCFMPickupPoints\Classes\Controllers\StoreCategoryController;

class FANDPickupSettings {

    public function __construct() {
        new Scripts();
        new pickuphoursController();
        new RoutesController();
        new PageManager();
        new StoreCategoryController();
        
        add_shortcode('pickup_points_map', [ShortcodesController::class, 'renderPickupMapShortcode']);
        
        // Initialisation des réglages Admin
        add_action('admin_init', [$this, 'mon_plugin_settings_init']);
        add_action('admin_menu', [$this, 'mon_plugin_admin_menu']);
        
        add_action('init', [$this, 'register_activation_logic']);
    }

    /**
     * Ajout de la page dans le menu Réglages de WordPress
     */
    public function mon_plugin_admin_menu() {
        add_options_page(
            'Config Catégories Boutique', 
            'Catégories Boutique', 
            'manage_options', 
            'config-categories-boutique', 
            [$this, 'mon_plugin_settings_page'] // Utilisation de [$this, ...]
        );
    }

    /**
     * Initialisation des sections et champs (admin_init)
     */
    public function mon_plugin_settings_init() {
        register_setting('mon_plugin_settings_group', 'liste_categories_boutique');

        add_settings_section(
            'section_principale', 
            'Liste des activités', 
            null, 
            'config-categories-boutique'
        );

        add_settings_field(
            'champ_categories', 
            'Catégories (séparées par des virgules)', 
            [$this, 'mon_plugin_render_field'], // Utilisation de [$this, ...]
            'config-categories-boutique', 
            'section_principale'
        );
    }

    /**
     * Rendu HTML de la page de réglages
     */
    public function mon_plugin_settings_page() {
        ?>
        <div class="wrap">
            <h1>Configuration des catégories de boutique</h1>
            <p>Définissez ici les catégories globales que les vendeurs pourront choisir.</p>
            <form method="post" action="options.php">
                <?php
                settings_fields('mon_plugin_settings_group');
                do_settings_sections('config-categories-boutique');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Rendu du champ Input
     */
    public function mon_plugin_render_field() {
        $val = get_option('liste_categories_boutique', 'Alimentation, Évènementiel, Foodtruck');
        echo '<input type="text" name="liste_categories_boutique" value="' . esc_attr($val) . '" class="regular-text" />';
        echo '<p class="description">Séparez chaque catégorie par une virgule (ex: Bar, Magasin, Foodtruck).</p>';
    }

    public function register_activation_logic() {
        if (get_option('fand_pickup_flush_rewrite')) {
            flush_rewrite_rules();
            delete_option('fand_pickup_flush_rewrite');
        }
    }
}