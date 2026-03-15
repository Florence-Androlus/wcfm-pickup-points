<?php
namespace fandWCFMPickupPoints\Classes\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RoutesController {

    public function __construct() {
        // Créer une route AJAX pour récupérer l'adresse sans blocage
        add_action('wp_ajax_fandpipo_get_address_from_gps', [$this, 'fandpipo_get_address_from_gps']);
        add_action('wp_ajax_nopriv_fandpipo_get_address_from_gps', [$this, 'fandpipo_get_address_from_gps']);

        add_action('init', [$this, 'fandpipo_register_rewrite_rules']);
        add_filter('query_vars', [$this, 'fandpipo_register_query_vars']);
        add_filter( 'template_include', [$this, 'fandpipo_load_branch_template'], 999 );
        add_action( 'wp', [ $this, 'fandpipo_excerpt_en_vue_liste' ] );
        add_action( 'woocommerce_after_shop_loop_item', [ $this, 'fandpipo_description_en_vue_liste' ], 1 );

        add_filter('document_title_parts', function($title_parts) {
            // 1. On récupère les variables de requête que vous avez déjà définies
            $branch_slug = get_query_var('branch_slug');
            $tab_slug    = get_query_var('tab_slug');
            $branch_data = get_query_var('current_branch_data');

            // 2. Si on est sur une page de branche (avec ou sans onglet)
            if ( ! empty( $branch_slug ) ) {
                
                // On récupère le nom propre de la branche (depuis la DB si dispo, sinon via le slug)
                if ( ! empty( $branch_data['branch_name'] ) ) {
                    $name = $branch_data['branch_name'];
                } else {
                    $name = ucwords(str_replace('-', ' ', sanitize_title($branch_slug)));
                }

                // 3. Mapping des titres d'onglets
                $tab_titles = [
                    'about'     => 'À propos',
                    'policies'  => 'Politiques',
                    'reviews'   => 'Avis',
                    'followers' => 'Abonnés',
                ];

                // 4. Construction du titre
                if ( ! empty( $tab_slug ) && isset( $tab_titles[$tab_slug] ) ) {
                    // Si on est sur un onglet spécifique (ex: Avis)
                    // Résultat : "Intermarche Express - Avis"
                    $title_parts['title'] = $name . ' - ' . $tab_titles[$tab_slug];
                } else {
                    // Si on est sur la page par défaut (Produits)
                    $title_parts['title'] = $name;
                }

                // On supprime le slogan (tagline) pour éviter que le titre soit trop long dans Chrome
                unset($title_parts['tagline']); 
            }

            return $title_parts;
        }, 100);

        add_filter('body_class', [$this, 'fandpipo_add_branch_page_body_classes']);
    }

    
    function fandpipo_get_address_from_gps() {
        // 1. Vérification du Nonce (Sécurité indispensable pour les appels AJAX/API)
        // Assurez-vous d'envoyer un nonce nommé 'fandpipo_nonce' dans votre appel JS
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['nonce'] ), 'fandpipo_gps_action' ) ) {
            wp_send_json_error( 'Accès non autorisé (Nonce invalide)' );
        }

        $lat = isset( $_GET['lat'] ) ? sanitize_text_field( wp_unslash( $_GET['lat'] ) ) : '';
        $lng = isset( $_GET['lng'] ) ? sanitize_text_field( wp_unslash( $_GET['lng'] ) ) : '';
        
        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lng}&addressdetails=1&accept-language=fr";
        
        $args = array(
            'timeout'    => 15,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            wp_send_json_error('Erreur serveur');
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Si le JSON est invalide ou vide
        if (empty($data)) {
            wp_send_json_error('Données GPS invalides');
        }

        // wp_send_json envoie les headers, échappe les données et fait wp_die()
        wp_send_json($data);
        wp_die();
    }

    public function fandpipo_add_branch_page_body_classes($classes) {
        $branch_slug = get_query_var('branch_slug');
        
        // Si on est sur notre page de branche
        if ( ! empty($branch_slug) ) {
            $classes[] = 'wcfm-store-page';
            $classes[] = 'wcfmmp-store-page';
        }
        
        return $classes;
    }

    public function fandpipo_register_rewrite_rules() {

        //Pages Single Branch
        add_rewrite_tag('%tab_slug%', '([^&]+)'); 
        // Règles pour les branches individuelles
        add_rewrite_rule('pickup/emplacement/([^/]+)/([^/]+)/?$', 'index.php?branch_slug=$matches[1]&tab_slug=$matches[2]', 'top');
        add_rewrite_rule('pickup/emplacement/([^/]+)/?$', 'index.php?branch_slug=$matches[1]', 'top');
    }

    public function fandpipo_register_query_vars($vars) {
        $vars[] = 'branch_slug';
        $vars[] = 'tab_slug';
        $vars[] = 'emplacement';
        return $vars;
    }

    /**
     * Gère l'affichage de la page single branch pickup
     */
    function fandpipo_load_branch_template( $template ) {
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
            $branch_data = self::fandpipo_get_branch_by_slug( $branch_slug ); 

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
                $fake_post_id = -99;
                $fake_post = (object) [
                    'ID'                      => $fake_post_id,
                    'post_type'               => 'wcfm_branch',
                    'post_title'              => $branch_data['branch_name'],
                    'post_content'            => '',
                    'post_status'             => 'publish',
                    'post_name'               => $branch_slug,
                    'post_date'             => current_time('mysql'),
                    'post_date_gmt'         => current_time('mysql', 1),
                    'post_modified'         => current_time('mysql'),
                    'post_modified_gmt'     => current_time('mysql', 1),
                    'post_author'             => 1, 
                    'comment_status'        => 'closed',
                    'ping_status'           => 'closed',
                    'guid'                  => home_url('/' . $branch_slug),
                    'filter'                => 'raw', // Important pour éviter certains traitements
                ];

                $wp_query->post = $fake_post;
                $wp_query->posts = [ $fake_post ];
                $wp_query->queried_object = $fake_post;
                $wp_query->queried_object_id = $fake_post_id;
                $wp_query->found_posts = 1;
                $wp_query->post_count = 1;

                global $post;
                $post = $fake_post;
                setup_postdata( $post ); 

                // Remplacer le template :
                $new_template = FANDPIPO_PLUGIN_DIR . 'views/single-branch/fandpipo-single-branch-view.php'; 
                if ( file_exists( $new_template ) ) {
                    return $new_template; 
                }
            }

        }
        return $template;
    }

    // Dans la même classe que fandpipo_load_branch_template
    public function fand_wcfm_custom_branch_title( $title ) {
        // On vérifie d'abord si nous sommes dans le contexte de notre 'branch' custom
        $branch_slug = get_query_var( 'branch_slug' );
        $active_tab_slug = get_query_var( 'active_tab_slug' ); // C'est la variable que vous définissez
        $branch_data = get_query_var( 'current_branch_data' ); // C'est la variable que vous définissez

        if ( ! empty( $branch_slug ) && ! empty( $branch_data ) ) {
            
            $branch_name = $branch_data['branch_name']; // Ex: 'WCFMCasier'

            // Mapping des slugs d'onglets pour un affichage convivial
            $tab_titles = [
                'products'  => __( 'products', 'fand-pickup-points-ultimate-edition-for-wcfm' ), // Pour la page de base (pas d'onglet)
                'about'     => __( 'about', 'fand-pickup-points-ultimate-edition-for-wcfm' ),
                'policies'  => __( 'policies', 'fand-pickup-points-ultimate-edition-for-wcfm' ),
                'reviews'   => __( 'reviews', 'fand-pickup-points-ultimate-edition-for-wcfm' ),
                'followers' => __( 'followers', 'fand-pickup-points-ultimate-edition-for-wcfm' ),
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
    public static function fandpipo_get_branch_by_slug( $branch_slug ) {
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
                        'branch_id'         => $location['ID'],
                        'vendor_id'         => $location['store_id'],
                        'branch_name'       => $location['name'], 
                        'lat'               => $location['latitude'],
                        'lng'               => $location['longitude'],
                        'map_address'       => $location['map_address'],
                        'address'           => $location['address'],
                        'city'              => $location['city'],
                        'postal_code'       => $location['postal_code'],
                        'state'             => $location['state'],
                        'fandpipo_country'  => $location['country'],
                    ];
                }
            }
        }
        return false;
    }

    /**
     * Gère l'activation de l'extrait en mode liste.
     */
    public function fandpipo_excerpt_en_vue_liste() {
        if ( is_shop() || is_product_category() || is_product_tag() ) {
            
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $view_mode = isset( $_COOKIE['productViewMode'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['productViewMode'] ) ) : '';
            
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $get_view  = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';

            if ( 'list' === $view_mode || 'list' === $get_view ) {
                remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_product_excerpt', 30 );
            } else {
                remove_action( 'woocommerce_after_shop_loop_item', [ $this, 'fandpipo_description_en_vue_liste' ], 1 );
            }
        }
    }

    /**
     * Affiche le contenu de la description courte à l'intérieur du div .product-excerpt.
     */
    public function fandpipo_description_en_vue_liste() {
        global $product;
        if ( $product && method_exists($product, 'get_short_description') && $product->get_short_description() ) {
            echo '<div class="product-excerpt">';
            // the_excerpt() contient déjà ses propres filtres d'échappement
            the_excerpt(); 
            echo '</div>';
        }
    }
}