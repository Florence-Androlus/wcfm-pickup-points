<?php

namespace fandWCFMPickupPoints;

// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use fandWCFMPickupPoints\Classes\Admin\Scripts;
use fandWCFMPickupPoints\Classes\Admin\PageManager;
use fandWCFMPickupPoints\Classes\Controllers\pickuphoursController;
use fandWCFMPickupPoints\Classes\Controllers\RoutesController;
use fandWCFMPickupPoints\Classes\Controllers\ShortcodesController;
use fandWCFMPickupPoints\Classes\Controllers\StoreCategoryController;

class FANDPickupSettings {

    public function __construct() {
        new Scripts();
        new RoutesController();
        new PageManager();
        new StoreCategoryController();
        new pickuphoursController;
        
        add_shortcode('fandpipo_map', [ShortcodesController::class, 'fandpipo_renderPickupMapShortcode']);
        
        // Initialisation des réglages Admin        
        add_action('admin_menu', [$this, 'fandpipo_register_menu']);
        
        add_action('init', [$this, 'fandpipo_register_activation_logic']);
        add_action( 'plugins_loaded', [$this, 'fandpipo_init_constants'], 10 );

    }

    function fandpipo_init_constants() {
        if ( !defined( 'FANDPIPO_AVATAR_DEFAULT' ) ) {
            // On utilise ton propre dossier d'assets
            define( 'FANDPIPO_AVATAR_DEFAULT', FANDPIPO_PLUGIN_URL . 'assets/images/wcfmmp-blue.png' );
        }
    }

    /**
     * Ajout de la page dans le menu Réglages de WordPress
     */
	public function fandpipo_register_menu() {

		// Ajouter le menu principal "Fournisseurs"
		add_menu_page(
			'FAND Pickup Points Ultimate', // Le titre de votre page de paramètres
			'FAND Pickup Points Ultimate', // Le nom du menu
			'manage_options', // La capacité requise
			'fandpipo-settings', // Le slug de la page
			[$this, 'fandpipo_render_liste_categories_page'], // La fonction de rappel pour afficher le contenu de la page
			'dashicons-location', // L'icône à utiliser pour ce menu
			59 // La position dans l'ordre du menu où celui-ci doit apparaître
		);

	}
	
    /**
     * Rendu HTML de la page principale (Liste des Catégories)
     */
    public function fandpipo_render_liste_categories_page() {
        // 1. Traitement manuel de la sauvegarde
        if (isset($_POST['fandpipo_save_categories'])) {
            check_admin_referer('fandpipo_save_categories_action'); 
            
            if ( isset( $_POST['fandpipo_liste_categories_boutique'] ) ) {
                // On nettoie et on sauvegarde
                $categories_brutes = sanitize_text_field( wp_unslash( $_POST['fandpipo_liste_categories_boutique'] ) );
                update_option( 'fandpipo_liste_categories_boutique', $categories_brutes );
                
                echo '<div class="updated notice is-dismissible"><p>✅ Liste globale mise à jour !</p></div>';
            }
        }

        // 2. Récupération de la valeur (On force une valeur par défaut si vide)
        $val = get_option('fandpipo_liste_categories_boutique', 'Alimentation, Évènementiel, Foodtruck');
        ?>
        <input type="text" name="fandpipo_liste_categories_boutique" value="<?php echo esc_attr($val); ?>" class="regular-text" />
        

        <div class="wrap">
            <h1><span class="dashicons dashicons-location"></span> Pickup Points Ultimate</h1>

            <div class="card" style="max-width: 100%; margin-top: 20px; padding: 15px;">
                <h2 style="margin-top:0;">Configuration des catégories</h2>
                <p>Définissez ici les catégories globales que les vendeurs pourront choisir.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('fandpipo_save_categories_action'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="fandpipo_liste_categories_boutique">Liste des activités</label></th>
                            <td>
                                <input type="text" name="fandpipo_liste_categories_boutique" id="fandpipo_liste_categories_boutique" value="<?php echo esc_attr($val); ?>" class="regular-text" />
                                <p class="description">Séparez chaque catégorie par une virgule (ex: Bar, Magasin, Foodtruck).</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Enregistrer les catégories', 'primary', 'fandpipo_save_categories'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    public function fandpipo_register_activation_logic() {
        if (get_option('fandpipo_flush_rewrite')) {
            flush_rewrite_rules();
            delete_option('fandpipo_flush_rewrite');
        }
    }
}