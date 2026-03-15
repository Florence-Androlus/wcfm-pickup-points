<?php
namespace fandWCFMPickupPoints\Classes\Database;

// Empêche l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class Database {

    public static function fandpipo_createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // On définit les noms de tables proprement
        $table_hours = $wpdb->prefix . 'fand_wcfm_pickup_hours';
        $table_holidays = $wpdb->prefix . 'fand_wcfm_pickup_holidays';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Note pour dbDelta : Il faut DEUX espaces après PRIMARY KEY
        // et ne pas utiliser d'apostrophes autour des noms de colonnes.
        $sql_hours = "CREATE TABLE $table_hours (
            ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            branch_id bigint(20) unsigned NOT NULL,
            day_of_week tinyint(1) NOT NULL,
            open_time time DEFAULT NULL,
            close_time time DEFAULT NULL,
            is_closed tinyint(1) DEFAULT 0,
            PRIMARY KEY  (ID),
            KEY branch_day (branch_id, day_of_week)
        ) $charset_collate;";

        $sql_holidays = "CREATE TABLE $table_holidays (
            ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            branch_id bigint(20) unsigned NOT NULL,
            holiday_date date NOT NULL,
            note varchar(255) DEFAULT NULL,
            PRIMARY KEY  (ID),
            UNIQUE KEY branch_holiday (branch_id, holiday_date)
        ) $charset_collate;";

        dbDelta($sql_hours);
        dbDelta($sql_holidays);


        $table_categories = $wpdb->prefix . 'fandpipo_categories';
        $sql_categories = "CREATE TABLE $table_categories (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nom varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            PRIMARY KEY  (id),
            KEY slug (slug)
        ) $charset_collate;"; // <-- Bien fermer la chaîne ici

        dbDelta($sql_categories);
    }

    /**
     * Ajoute une nouvelle catégorie de point de collecte
     */
    public static function add_category($nom) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fandpipo_categories';

        $nom = sanitize_text_field($nom);
        $slug = sanitize_title($nom);

        // Vérification existence table
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return new \WP_Error('table_missing', 'La table SQL est manquante.');
        }

        // Vérifier doublons
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE slug = %s",
            $slug
        ));

        if ($exists > 0) {
            return new \WP_Error('duplicate', 'Cette catégorie existe déjà.');
        }

        $result = $wpdb->insert(
            $table_name,
            ['nom' => $nom, 'slug' => $slug],
            ['%s', '%s']
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Récupère toutes les catégories
     */
    public static function get_all_categories() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fandpipo_categories';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY nom ASC");
    }

    /**
     * Récupère une seule catégorie par son ID
     */
    public static function get_category($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fandpipo_categories';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }

    /**
     * Met à jour une catégorie existante
     */
    public static function update_category($id, $nom) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fandpipo_categories';

        $nom  = sanitize_text_field($nom);
        $slug = sanitize_title($nom);

        $result = $wpdb->update(
            $table_name,
            [
                'nom'  => $nom,
                'slug' => $slug
            ],
            ['id' => intval($id)],
            ['%s', '%s'], // formats des données
            ['%d']        // format du WHERE
        );

        // $result retourne le nombre de lignes affectées, ou false en cas d'erreur
        return $result !== false;
    }

    /**
     * Supprime une catégorie
     */
    public static function delete_category($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fandpipo_categories';
        return $wpdb->delete($table_name, ['id' => intval($id)], ['%d']);
    }

    /**
     * Suppression groupée de catégories
     */
    public static function delete_categories_bulk($ids) {
        global $wpdb;
        if (empty($ids) || !is_array($ids)) return false;

        $table_name = $wpdb->prefix . 'fandpipo_categories';
        
        // On sécurise les IDs (on force des entiers)
        $ids = array_map('intval', $ids);
        $ids_string = implode(',', $ids);

        return $wpdb->query("DELETE FROM $table_name WHERE id IN ($ids_string)");
    }

}
