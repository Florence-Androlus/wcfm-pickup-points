<?php

namespace fandWCFMPickupPoints;

// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use fandWCFMPickupPoints\Classes\Admin\PageManager;
use fandWCFMPickupPoints\Classes\Admin\Scripts;
use fandWCFMPickupPoints\Classes\Controllers\pickuphoursController;
use fandWCFMPickupPoints\Classes\Controllers\RoutesController;
use fandWCFMPickupPoints\Classes\Controllers\ShortcodesController;
use fandWCFMPickupPoints\Classes\Controllers\StoreCategoryController;
use fandWCFMPickupPoints\Classes\Database\Database;

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
    /*public function fandpipo_render_liste_categories_page() {
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
    }*/

    /*public function fandpipo_render_liste_categories_page() {
    
        // --- 1. TRAITEMENT ---
        if (isset($_POST['fandpipo_add_category'])) {
            error_log('FANDPIPO: Formulaire reçu pour l\'ajout'); // LOG
            
            if (!check_admin_referer('fandpipo_add_cat_action')) {
                error_log('FANDPIPO: Échec du Nonce'); // LOG
            }

            $result = Database::add_category($_POST['cat_name']);

            if (is_wp_error($result)) {
                error_log('FANDPIPO: Erreur WP_Error: ' . $result->get_error_message()); // LOG
                echo '<div class="error"><p>❌ ' . $result->get_error_message() . '</p></div>';
            } elseif ($result) {
                error_log('FANDPIPO: Succès insertion ID ' . $result); // LOG
                echo '<div class="updated"><p>✅ Catégorie ajoutée avec succès !</p></div>';
            } else {
                error_log('FANDPIPO: Résultat Database::add_category est false'); // LOG
            }
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['cat_id'])) {
            check_admin_referer('delete_cat_' . $_GET['cat_id']);
            Database::delete_category($_GET['cat_id']);
            echo '<div class="updated"><p>Catégorie supprimée.</p></div>';
        }

        // --- 2. RÉCUPÉRATION ---
        $categories = Database::get_all_categories();

        // --- 3. AFFICHAGE (HTML identique au précédent) ---
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-tag"></span> Catégories de points de collecte</h1>

            <div id="col-container" class="wp-clearfix">

                <div id="col-left">
                    <div class="col-wrap">
                        <div class="form-wrap">
                            <h2>Ajouter une nouvelle catégorie</h2>
                            <form method="post" action="">
                                <?php wp_nonce_field('fandpipo_add_cat_action'); ?>
                                
                                <div class="form-field form-required term-name-wrap">
                                    <label for="cat_name">Nom de la catégorie</label>
                                    <input name="cat_name" id="cat_name" type="text" value="" size="40" aria-required="true" required>
                                    <p>Le nom tel qu'il apparaîtra sur votre site.</p>
                                </div>

                                <p class="submit">
                                    <?php submit_button('Ajouter la catégorie', 'primary', 'fandpipo_add_category', false); ?>
                                </p>
                            </form>
                        </div>
                    </div>
                </div><div id="col-right">
                    <div class="col-wrap">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col" class="manage-column column-id" style="width: 50px;">ID</th>
                                    <th scope="col" class="manage-column column-name">Nom</th>
                                    <th scope="col" class="manage-column column-slug">Slug</th>
                                    <th scope="col" class="manage-column column-action" style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($categories) : ?>
                                    <?php foreach ($categories as $cat) : ?>
                                        <tr>
                                            <td><strong><?php echo $cat->id; ?></strong></td>
                                            <td>
                                                <strong><?php echo esc_html($cat->nom); ?></strong>
                                            </td>
                                            <td><?php echo esc_html($cat->slug); ?></td>
                                            <td>
                                                <?php 
                                                $delete_url = wp_nonce_url(
                                                    admin_url('admin.php?page=' . $_GET['page'] . '&action=delete&cat_id=' . $cat->id), 
                                                    'delete_cat_' . $cat->id
                                                ); 
                                                ?>
                                                <span class="delete">
                                                    <a href="<?php echo $delete_url; ?>" class="submitdelete" style="color: #a00;" onclick="return confirm('Supprimer cette catégorie ?');">Supprimer</a>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="4">Aucune catégorie trouvée.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div></div></div>
        <?php
    }*/

    public function fandpipo_render_liste_categories_page() {
        // 1. TRAITEMENT AVANT TOUT AFFICHAGE HTML
        if (isset($_POST['fandpipo_add_category'])) {
            
            // Vérification du jeton de sécurité
            check_admin_referer('fandpipo_add_cat_action');

            $result = Database::add_category($_POST['cat_name']);

            if (!is_wp_error($result) && $result) {
                // REDIRECTION pour éviter le renvoi du formulaire et l'expiration du lien
                wp_safe_redirect(admin_url('admin.php?page=' . $_GET['page'] . '&msg=success'));
                exit;
            }
        }

        // 2. AFFICHAGE DES NOTICES (via l'URL)
        if (isset($_GET['msg']) && $_GET['msg'] === 'success') {
            echo '<div class="updated notice is-dismissible"><p>✅ Catégorie ajoutée avec succès !</p></div>';
        }

        // --- ACTION : MISE À JOUR ---
        if (isset($_POST['fandpipo_update_category'])) {
            check_admin_referer('fandpipo_update_cat_action');

            $updated = Database::update_category($_POST['cat_id'], $_POST['cat_name']);

            if ($updated) {
                wp_safe_redirect(admin_url('admin.php?page=' . $_GET['page'] . '&msg=updated'));
                exit;
            }
        }
        
        if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
            echo '<div class="updated notice is-dismissible"><p>✅ Catégorie mise à jour !</p></div>';
        }

        // --- DETECTION DU MODE : Si action=edit, on change d'affichage ---
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['cat_id'])) {
            $this->render_edit_category_page(intval($_GET['cat_id']));
            return; // On arrête là pour ne pas afficher la liste en dessous
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['cat_id'])) {
            check_admin_referer('delete_cat_' . $_GET['cat_id']);
            Database::delete_category($_GET['cat_id']);
            echo '<div class="updated"><p>Catégorie supprimée.</p></div>';
        }

        // --- ACTION : SUPPRESSION GROUPÉE ---
        if (isset($_GET['action']) && $_GET['action'] === 'bulk-delete' && isset($_GET['delete_tags'])) {
            // Note : WordPress ne génère pas de nonce automatique pour les bulk actions natives
            // sans une List Table Class, mais on peut vérifier manuellement si on veut.
            
            $ids_to_delete = $_GET['delete_tags'];
            Database::delete_categories_bulk($ids_to_delete);
            
            wp_safe_redirect(admin_url('admin.php?page=' . $_GET['page'] . '&msg=bulk_deleted'));
            exit;
        }

        // Ajoute le message de succès dans les notices
        if (isset($_GET['msg']) && $_GET['msg'] === 'bulk_deleted') {
            echo '<div class="updated notice is-dismissible"><p>✅ Catégories supprimées avec succès.</p></div>';
        }

        // --- 2. RÉCUPÉRATION ---
        $categories = Database::get_all_categories();
        $message = isset($_GET['message']) ? $_GET['message'] : null;
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Catégories de points de collecte</h1>
            <hr class="wp-header-end">

            <?php if ($message == 1): ?>
                <div id="message" class="updated notice is-dismissible"><p>Catégorie ajoutée.</p></div>
            <?php endif; ?>

            <div id="col-container" class="wp-clearfix">
                
                <div id="col-left">
                    <div class="col-wrap">
                        <div class="form-wrap">
                            <h2>Ajouter une nouvelle catégorie</h2>
                            <form id="addtag" method="post" action="" class="validate">
                                <?php wp_nonce_field('fandpipo_add_cat_action'); ?>
                                
                                <div class="form-field form-required term-name-wrap">
                                    <label for="tag-name">Nom</label>
                                    <input name="cat_name" id="tag-name" type="text" value="" size="40" aria-required="true" required>
                                    <p>Le nom tel qu'il apparaîtra sur votre site.</p>
                                </div>

                                <!--div class="form-field term-slug-wrap">
                                    <label for="tag-slug">Slug</label>
                                    <input name="cat_slug" id="tag-slug" type="text" value="" size="40" placeholder="Laisser vide pour auto-générer">
                                    <p>Le « slug » est la version de l’URL utilisable pour le nom.</p>
                                </div-->

                                <p class="submit">
                                    <?php submit_button('Ajouter une catégorie', 'primary', 'fandpipo_add_category', false); ?>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
                <div id="col-right">
                    <div class="col-wrap">
                        <form id="posts-filter" method="get">
                            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>" />

                            <div class="tablenav top">
                                <div class="alignleft actions bulkactions">
                                    <label for="bulk-action-selector-top" class="screen-reader-text">Sélectionner l’action groupée</label>
                                    <select name="action" id="bulk-action-selector-top">
                                        <option value="-1">Actions groupées</option>
                                        <option value="bulk-delete">Supprimer</option>
                                    </select>
                                    <input type="submit" id="doaction" class="button action" value="Appliquer">
                                </div>
                                <div class="tablenav-pages-container">
                                    <span class="displaying-num"><?php echo count($categories); ?> éléments</span>
                                </div>
                                <br class="clear">
                            </div>

                            <table class="wp-list-table widefat fixed striped tags">
                                <thead>
                                    <tr>
                                        <td id="cb" class="manage-column column-cb check-column">
                                            <label class="screen-reader-text" for="cb-select-all-1">Tout sélectionner</label>
                                            <input id="cb-select-all-1" type="checkbox">
                                        </td>
                                        <th scope="col" id="name" class="manage-column column-name column-primary">
                                            <span>Nom</span>
                                        </th>
                                        <th scope="col" id="slug" class="manage-column column-slug">Slug</th>
                                        <th scope="col" id="posts" class="manage-column column-posts num">Total</th>
                                    </tr>
                                </thead>

                                <tbody id="the-list">
                                    <?php if ($categories) : foreach ($categories as $cat) : ?>
                                        <tr id="tag-<?php echo $cat->id; ?>">
                                            <th scope="row" class="check-column">
                                                <input type="checkbox" name="delete_tags[]" value="<?php echo $cat->id; ?>">
                                            </th>
                                            <td class="name column-name has-row-actions column-primary">
                                                <strong>
                                                    <a class="row-title" href="#" aria-label="« <?php echo esc_html($cat->nom); ?> » (Modifier)"><?php echo esc_html($cat->nom); ?></a>
                                                </strong>
                                                <div class="row-actions">
                                                    <?php
                                                    // Dans ton foreach ($categories as $cat)
                                                    $edit_url = admin_url('admin.php?page=' . $_GET['page'] . '&action=edit&cat_id=' . $cat->id);
                                                    ?>
                                                    <span class="edit">
                                                        <a href="<?php echo $edit_url; ?>" aria-label="Modifier « <?php echo esc_html($cat->nom); ?> »">Modifier</a> | 
                                                    </span>
                                                    <span class="delete">
                                                        <?php 
                                                        $del_url = wp_nonce_url(
                                                            admin_url('admin.php?page=' . $_GET['page'] . '&action=delete&cat_id=' . $cat->id), 
                                                            'delete_cat_' . $cat->id
                                                        ); 
                                                        ?>
                                                        <a href="<?php echo $del_url; ?>" class="delete-tag aria-button-if-js" style="color:#a00;" onclick="return confirm('Supprimer définitivement ?');">Supprimer</a>
                                                    </span>
                                                </div>
                                                <button type="button" class="toggle-row"><span class="screen-reader-text">Afficher les détails</span></button>
                                            </td>
                                            <td class="slug column-slug"><?php echo esc_html($cat->slug); ?></td>
                                            <td class="posts column-posts num">0</td> </tr>
                                    <?php endforeach; else : ?>
                                        <tr class="no-items"><td class="colspanchange" colspan="5">Aucune catégorie trouvée.</td></tr>
                                    <?php endif; ?>
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <td class="manage-column column-cb check-column"><input type="checkbox"></td>
                                        <th scope="col" class="manage-column column-name column-primary">Nom</th>
                                        <th scope="col" class="manage-column column-slug">Slug</th>
                                        <th scope="col" class="manage-column column-posts num">Total</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>  
        <?php
    }

    private function render_edit_category_page($id) {
        global $wpdb;
        $cat = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}fandpipo_categories WHERE id = %d", $id));

        if (!$cat) {
            echo '<div class="error"><p>Catégorie introuvable.</p></div>';
            return;
        }
        ?>
        <div class="wrap">
            <h1>Modifier la catégorie</h1>
            <form method="post" action="" class="validate">
                <?php wp_nonce_field('fandpipo_update_cat_action'); ?>
                <input type="hidden" name="cat_id" value="<?php echo $cat->id; ?>">
                
                <table class="form-table" role="presentation">
                    <tr class="form-field form-required">
                        <th scope="row"><label for="name">Nom</label></th>
                        <td>
                            <input name="cat_name" id="name" type="text" value="<?php echo esc_attr($cat->nom); ?>" size="40" aria-required="true">
                            <p class="description">Le nom tel qu'il apparaîtra sur votre site.</p>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row"><label for="slug">Slug</label></th>
                        <td>
                            <input name="cat_slug" id="slug" type="text" value="<?php echo esc_attr($cat->slug); ?>" size="40" disabled>
                            <p class="description">Le slug est généré automatiquement à partir du nom.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Mettre à jour', 'primary', 'fandpipo_update_category'); ?>
                <a href="<?php echo admin_url('admin.php?page=' . $_GET['page']); ?>">Retour aux catégories</a>
            </form>
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