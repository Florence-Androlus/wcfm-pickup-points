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
        
        add_shortcode('pickup_points_map', [ShortcodesController::class, 'renderPickupMapShortcode']);
        
        // Initialisation des réglages Admin        
        add_action('admin_menu', [$this, 'register_pickup_points_menu']);
        
        add_action('init', [$this, 'register_activation_logic']);
    }

    /**
     * Ajout de la page dans le menu Réglages de WordPress
     */
	public function register_pickup_points_menu() {

		// Ajouter le menu principal "Fournisseurs"
		add_menu_page(
			'Pickup Points Ultimate', // Le titre de votre page de paramètres
			'Pickup Points Ultimate', // Le nom du menu
			'manage_options', // La capacité requise
			'pickup-points-ultimate-settings', // Le slug de la page
			[$this, 'render_liste_categories_page'], // La fonction de rappel pour afficher le contenu de la page
			'dashicons-location', // L'icône à utiliser pour ce menu
			59 // La position dans l'ordre du menu où celui-ci doit apparaître
		);

	}
	
    /**
     * Rendu HTML de la page principale (Liste des Catégories)
     */
    public function render_liste_categories_page() {
        // 1. Traitement manuel de la sauvegarde
        if (isset($_POST['save_fand_categories'])) {
            // Vérification de sécurité
            check_admin_referer('fand_save_categories_action'); 
            
            $categories = sanitize_text_field($_POST['liste_categories_boutique']);
            update_option('liste_categories_boutique', $categories);
            
            echo '<div class="updated notice is-dismissible"><p>✅ Catégories mises à jour avec succès !</p></div>';
        }

        // 2. Récupération de la valeur actuelle
        $val = get_option('liste_categories_boutique', 'Alimentation, Évènementiel, Foodtruck');
        ?>

        <div class="wrap">
            <h1><span class="dashicons dashicons-location"></span> Pickup Points Ultimate</h1>

            <div class="card" style="max-width: 100%; margin-top: 20px; padding: 15px;">
                <h2 style="margin-top:0;">Configuration des catégories</h2>
                <p>Définissez ici les catégories globales que les vendeurs pourront choisir.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('fand_save_categories_action'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="liste_categories_boutique">Liste des activités</label></th>
                            <td>
                                <input type="text" name="liste_categories_boutique" id="liste_categories_boutique" value="<?php echo esc_attr($val); ?>" class="regular-text" />
                                <p class="description">Séparez chaque catégorie par une virgule (ex: Bar, Magasin, Foodtruck).</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('Enregistrer les catégories', 'primary', 'save_fand_categories'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    public function register_activation_logic() {
        if (get_option('fand_pickup_flush_rewrite')) {
            flush_rewrite_rules();
            delete_option('fand_pickup_flush_rewrite');
        }
    }
}