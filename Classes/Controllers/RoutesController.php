<?php
namespace fandWCFMPickupPoints\Classes\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RoutesController {

    public function __construct() {
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_filter( 'template_include', [$this, 'fand_wcfm_load_branch_template'], 999 );
        add_action( 'wp', [ $this, 'afficher_excerpt_en_vue_liste' ] );
        add_action( 'woocommerce_after_shop_loop_item', [ $this, 'ma_description_en_vue_liste' ], 1 );

        add_filter('document_title_parts', function($title_parts) {
            $slug = get_query_var('emplacement');

            if (empty($slug) && isset($_SERVER['REQUEST_URI'])) {
                // CORRECTION : Unslash + Sanitize de REQUEST_URI
                $request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
                $parsed_url = wp_parse_url( $request_uri );
                $path = isset( $parsed_url['path'] ) ? trim( $parsed_url['path'], '/' ) : '';
                $segments = explode('/', $path);
                
                if (count($segments) >= 3 && $segments[0] === 'pickup' && $segments[1] === 'emplacement') {
                    $slug = end($segments);
                }
            }

            if (!empty($slug)) {
                $name = ucwords(str_replace('-', ' ', sanitize_title($slug)));
                $title_parts['title'] = $name;
                unset($title_parts['tagline']); 
            }

            return $title_parts;
        }, 100);
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
        $valid_tabs  = array('about', 'policies', 'reviews', 'followers');
        
        if ( empty( $branch_slug ) && ! empty( $tab_slug ) && in_array($tab_slug, $valid_tabs) ) {
            // CORRECTION : Vérification et nettoyage de REQUEST_URI
            if ( isset( $_SERVER['REQUEST_URI'] ) ) {
                $request_uri = trim( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/' );
                $parts = explode( '/', $request_uri );
                $tab_index = array_search( $tab_slug, $parts );
                
                if ( $tab_index !== false && $tab_index > 0 ) {
                    $branch_slug = $parts[$tab_index - 1]; 
                    set_query_var( 'branch_slug', $branch_slug ); 
                }
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
                $new_template = FAND_PICKUP_POINTS_ULTIMATE_PLUGIN_DIR . 'views/single-branch/single-branch-view.php'; 
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
                'products'  => __( 'Produits', 'fand-pickup-points-ultimate' ), // Pour la page de base (pas d'onglet)
                'about'     => __( 'À propos', 'fand-pickup-points-ultimate' ),
                'policies'  => __( 'Politiques', 'fand-pickup-points-ultimate' ),
                'reviews'   => __( 'Avis', 'fand-pickup-points-ultimate' ),
                'followers' => __( 'Abonnés', 'fand-pickup-points-ultimate' ),
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

        // 1. Définir une clé de cache unique (basée sur le slug ou la requête)
        $cache_key = 'fand_wcfm_all_locations';
        $cache_group = 'fand_pickup';

        // 2. Tenter de récupérer les données depuis le cache
        $all_locations = wp_cache_get( $cache_key, $cache_group );

        if ( false === $all_locations ) {
            // 3. Si le cache est vide, on fait la requête SQL
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $all_locations = $wpdb->get_results( 
                "SELECT ID, store_id, name, latitude, longitude, map_address, address, city, postal_code, state, country 
                 FROM {$wpdb->prefix}wcfm_store_locations 
                 WHERE name != ''", 
                ARRAY_A 
            );

            // 4. On enregistre le résultat en cache pour 1 heure (3600 secondes)
            wp_cache_set( $cache_key, $all_locations, $cache_group, 3600 );
        }
        
        if ( $all_locations ) {
            foreach ( $all_locations as $location ) {
                $db_name = $location['name']; 
                if ( sanitize_title( $db_name ) === $branch_slug ) {
                    return [
                        'branch_id'   => $location['ID'],
                        'vendor_id'   => $location['store_id'],
                        'branch_name' => $location['name'], 
                        'lat'         => $location['latitude'],
                        'lng'         => $location['longitude'],
                        'map_address' => $location['map_address'],
                        'address'     => $location['address'],
                        'city'        => $location['city'],
                        'postal_code' => $location['postal_code'],
                        'state'       => $location['state'],
                        'country'     => $location['country'],
                    ];
                }
            }
        }
        return false;
    }

    /**
     * Gère l'activation de l'extrait en mode liste.
     */
    public function afficher_excerpt_en_vue_liste() {
        if ( is_shop() || is_product_category() || is_product_tag() ) {
            
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $view_mode = isset( $_COOKIE['productViewMode'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['productViewMode'] ) ) : '';
            
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $get_view  = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';

            if ( 'list' === $view_mode || 'list' === $get_view ) {
                remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_product_excerpt', 30 );
            } else {
                remove_action( 'woocommerce_after_shop_loop_item', [ $this, 'ma_description_en_vue_liste' ], 1 );
            }
        }
    }

    /**
     * Affiche le contenu de la description courte à l'intérieur du div .product-excerpt.
     */
    public function ma_description_en_vue_liste() {
        global $product;
        if ( $product && method_exists($product, 'get_short_description') && $product->get_short_description() ) {
            echo '<div class="product-excerpt">';
            the_excerpt(); 
            echo '</div>';
        }
    }
}