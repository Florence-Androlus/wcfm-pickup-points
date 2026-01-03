<?php
namespace fandWCFMPickupPoints\Classes\Controllers;

class RoutesController {

    public function __construct() {
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'register_query_vars']);

        // Logique pour la page SINGLE BRANCH pickup
        add_filter( 'template_include', [$this, 'fand_wcfm_load_branch_template'], 999 );

        // Enregistre la fonction principale pour les hooks WooCommerce
        add_action( 'wp', [ $this, 'afficher_excerpt_en_vue_liste' ] );
        // Enregistre la fonction qui affiche le contenu
        add_action( 'woocommerce_after_shop_loop_item', [ $this, 'ma_description_en_vue_liste' ], 1 );

        // Modifier le titre de l'onglet
        add_filter('document_title_parts', function($title_parts) {
            // On essaie de récupérer la variable propre
            $slug = get_query_var('emplacement');

            // Sécurité : Si get_query_var est vide, on regarde directement l'URL
            if (empty($slug)) {
                $parsed_url = wp_parse_url( $_SERVER['REQUEST_URI'] );
                $path = isset( $parsed_url['path'] ) ? trim( $parsed_url['path'], '/' ) : '';
                $segments = explode('/', $path);
                // Si l'URL est /pickup/emplacement/nom-branch/, le slug est le dernier segment
                if (count($segments) >= 3 && $segments[0] === 'pickup' && $segments[1] === 'emplacement') {
                    $slug = end($segments);
                }
            }

            if (!empty($slug)) {
                // On nettoie le slug pour l'affichage (nom-branch -> nom branch)
                $name = ucwords(str_replace('-', ' ', $slug));
                
                // On change le titre de l'onglet
                $title_parts['title'] = $name;
                // Optionnel : on peut enlever le slogan du site pour cette page
                unset($title_parts['tagline']); 
            }

            return $title_parts;
        }, 100); // Priorité haute pour passer après les plugins SEO
    }

    public function register_rewrite_rules() {

        //Pages Single Branch
        add_rewrite_tag('%tab_slug%', '([^&]+)'); 
        // Règles pour les branches individuelles
        add_rewrite_rule('pickup/emplacement/([^/]+)/([^/]+)/?$', 'index.php?branch_slug=$matches[1]&tab_slug=$matches[2]', 'top');
        add_rewrite_rule('pickup/emplacement/([^/]+)/?$', 'index.php?branch_slug=$matches[1]', 'top');
    }

    public function register_query_vars($vars) {
        $vars[] = 'branch_slug';
        $vars[] = 'tab_slug';
        $vars[] = 'emplacement';
        return $vars;
    }

    /**
     * Gère l'affichage de la page single branch pickup
     */
    function fand_wcfm_load_branch_template( $template ) {
        $branch_slug = get_query_var( 'branch_slug' );
        $tab_slug    = get_query_var( 'tab_slug' ); 

        // Définir les slugs d'onglets que nous gérons (utilisé pour la logique de routage)
        $valid_tabs = array('about', 'policies', 'reviews', 'followers');
        
        // --- Étape 1 : Extraire le slug de l'emplacement si l'on est sur un onglet ---
        if ( empty( $branch_slug ) && ! empty( $tab_slug ) && in_array($tab_slug, $valid_tabs) ) {
            
            // Si $branch_slug est vide, mais $tab_slug est rempli (ex: URL est .../emplacement-slug/about/)
            
            $request_uri = trim( $_SERVER['REQUEST_URI'], '/' );
            $parts = explode( '/', $request_uri );
            
            // L'onglet est le dernier segment, l'avant-dernier devrait être le slug de l'emplacement.
            $tab_index = array_search( $tab_slug, $parts );
            
            if ( $tab_index !== false && $tab_index > 0 ) {
                // On récupère le segment avant le slug de l'onglet (C'est le branch_slug)
                $branch_slug = $parts[$tab_index - 1]; 
                // On met à jour la query_var, même si elle n'a pas été trouvée automatiquement
                set_query_var( 'branch_slug', $branch_slug ); 
            }
        }
        // --- Fin Étape 1 ---
        
        // La condition de test devient: si branch_slug est rempli (que ce soit pour la page de base ou un onglet).
        if ( ! empty( $branch_slug ) ) {
            
            // --- Étape 2 : Récupération des données uniquement si on a le slug ---
            $branch_data = self::fand_wcfm_get_branch_by_slug( $branch_slug ); 

            if ( $branch_data ) {
                
                // Rendre les variables disponibles dans le template (y compris le tab_slug)
                set_query_var( 'current_branch_id', $branch_data['branch_id'] );
                set_query_var( 'current_vendor_id', $branch_data['vendor_id'] );
                set_query_var( 'current_branch_data', $branch_data );
                
                // On s'assure que le slug d'onglet est aussi disponible, même s'il est vide (pour le template)
                if( ! empty( $tab_slug ) ) {
                    set_query_var( 'active_tab_slug', $tab_slug );
                } else {
                    set_query_var( 'active_tab_slug', 'products' ); // Par défaut
                }

                // AJOUT CRITIQUE POUR DÉFINIR LE CONTEXTE (Identique à votre code)
                global $wp_query;
                
                // ... (Votre code pour simuler WP_Query, is_single=true, $fake_post, setup_postdata($post)) ...
                
                if ( ! isset( $wp_query ) || is_null( $wp_query ) ) {
                    $wp_query = new \WP_Query();
                }
                if ( ! $wp_query instanceof \WP_Query ) {
                    $wp_query = new \WP_Query();
                }

                $wp_query->is_404 = false;
                $wp_query->is_single = true;
                $wp_query->is_singular = true; 
                $wp_query->is_main_query = true; 
                $wp_query->query_vars['post_type'] = 'wcfm_branch';
                
                $fake_post = (object) [
                    'ID'                      => 0,
                    'post_type'               => 'wcfm_branch',
                    'post_title'              => $branch_data['branch_name'],
                    'post_content'            => '',
                    'post_status'             => 'publish',
                    'post_name'               => $branch_slug,
                    'post_date'               => current_time('mysql'),
                    'post_author'             => 1, 
                ];

                $wp_query->posts = [ $fake_post ]; 
                $wp_query->post = $fake_post;
                $wp_query->found_posts = 1;
                $wp_query->post_count = 1;

                global $post;
                $post = $fake_post;
                setup_postdata( $post ); 

                // Remplacer le template :
                $new_template = FAND_PICKUP_PLUGIN_DIR . 'views/single-branch/single-branch-view.php'; 
                if ( file_exists( $new_template ) ) {
                    return $new_template; 
                }
            }

        }
        return $template;
    }

    // Dans la même classe que fand_wcfm_load_branch_template
    public function fand_wcfm_custom_branch_title( $title ) {
        // On vérifie d'abord si nous sommes dans le contexte de notre 'branch' custom
        $branch_slug = get_query_var( 'branch_slug' );
        $active_tab_slug = get_query_var( 'active_tab_slug' ); // C'est la variable que vous définissez
        $branch_data = get_query_var( 'current_branch_data' ); // C'est la variable que vous définissez

        if ( ! empty( $branch_slug ) && ! empty( $branch_data ) ) {
            
            $branch_name = $branch_data['branch_name']; // Ex: 'WCFMCasier'

            // Mapping des slugs d'onglets pour un affichage convivial
            $tab_titles = [
                'products'  => __( 'Produits', 'wcfm-pickup-points' ), // Pour la page de base (pas d'onglet)
                'about'     => __( 'À propos', 'wcfm-pickup-points' ),
                'policies'  => __( 'Politiques', 'wcfm-pickup-points' ),
                'reviews'   => __( 'Avis', 'wcfm-pickup-points' ),
                'followers' => __( 'Abonnés', 'wcfm-pickup-points' ),
            ];

            // Déterminer le titre de l'onglet (Produits par défaut si $active_tab_slug n'est pas trouvé)
            $tab_title = isset( $tab_titles[ $active_tab_slug ] ) ? $tab_titles[ $active_tab_slug ] : $tab_titles['products'];

            // 2. Récupérer le nom du site
            $site_name = get_bloginfo( 'name' ); // Ceci récupère le titre du site défini dans Réglages > Général

            // 3. Construire le nouveau titre complet (Nom de la Branche - Nom de l'Onglet - Nom du Site)
            // Notez l'ajout de l'opérateur de concaténation . ' - ' . $site_name
            $new_title = sprintf(
                '%s - %s - %s', // Le format avec trois parties
                $branch_name, 
                $tab_title,
                $site_name
            );
            
            // Vous pouvez ajouter le nom du site si nécessaire, ou laisser WordPress le faire.
            // Optionnel: $new_title .= ' - ' . get_bloginfo( 'name' );

            // Retourner le titre final. Le plugin SEO pourrait encore l'altérer, mais nous partons d'une base correcte.
            return $new_title;
        }
        
        // Si ce n'est pas notre page de branche, on retourne le titre original.
        return $title;
    }

    /**
     * Recherche les informations d'une branche (emplacement) dans la table WCFM
     * en utilisant le slug généré à partir du nom.
     *
     * @param string $branch_slug Le slug de l'emplacement recherché.
     * @return array|false Un tableau contenant l'ID de la branche, l'ID du vendeur, et le nom.
     */
    public static function fand_wcfm_get_branch_by_slug( $branch_slug ) {
        global $wpdb;
        $table_locations = $wpdb->prefix . 'wcfm_store_locations'; 

        $all_locations = $wpdb->get_results( 
            "SELECT * FROM $table_locations WHERE name != ''", // Récupérez TOUTES les colonnes (*)
            ARRAY_A 
        );
        
        foreach ( $all_locations as $location ) {
            $db_name = $location['name']; 
            $current_slug = sanitize_title( $db_name ); 
            
            if ( $current_slug === $branch_slug ) {
                // Retournez l'objet/tableau complet de la ligne.
                return [
                    'branch_id'     => $location['ID'],
                    'vendor_id'     => $location['store_id'],
                    'branch_name'   => $location['name'], 
                    'lat'           => $location['latitude'],
                    'lng'           => $location['longitude'],
                    'map_address'   => $location['map_address'],
                    'address'       => $location['address'],
                    'city'          => $location['city'],
                    'postal_code'   => $location['postal_code'],
                    'state'         => $location['state'],
                    'country'       => $location['country'],
                ];
            }
        }
        
        return false;
    }

    /**
     * Gère l'activation de l'extrait en mode liste.
     */
    public function afficher_excerpt_en_vue_liste() {
        // Ne s'exécute que sur les pages d'archives WooCommerce (boutique, catégorie, etc.)
        if ( is_shop() || is_product_category() || is_product_tag() ) {
            
            // --- Logique pour déterminer si la vue liste est active ---
            $view_mode = isset($_COOKIE['productViewMode']) ? $_COOKIE['productViewMode'] : null;
            
            if ( $view_mode == 'list' || (isset($_GET['view']) && $_GET['view'] == 'list') ) {
                
                // Si la vue liste est active, nous nous assurons que notre fonction sera appelée
                // et retirons l'extrait par défaut si le thème ou WooCommerce l'avait mis ailleurs.
                remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_product_excerpt', 30 );
            } else {
                // Si ce n'est PAS la vue liste, nous retirons notre action pour qu'elle n'affiche rien
                remove_action( 'woocommerce_after_shop_loop_item', [ $this, 'ma_description_en_vue_liste' ], 1 );
            }
        }
    }

    /**
     * Affiche le contenu de la description courte à l'intérieur du div .product-excerpt.
     */
    public function ma_description_en_vue_liste() {
        // La vérification is_shop, etc., est faite dans la méthode précédente (afficher_excerpt_en_vue_liste)
        
        global $product;
        
        // On vérifie si l'extrait existe pour éviter d'afficher un div vide
        if ( $product && $product->get_short_description() ) {
            echo '<div class="product-excerpt">';
            the_excerpt(); // Affiche la description courte
            echo '</div>';
        }
    }
}