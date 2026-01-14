<?php
// Si WordPress n'appelle pas ce fichier, on sort
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 1. Récupérer l'ID de la page
$fand_pickup_page_id = get_option('fand_pickup_page_id');

if ($fand_pickup_page_id) {
    // 2. Supprimer la page (true pour forcer la suppression sans corbeille)
    wp_delete_post($fand_pickup_page_id, true);
    
    // 3. Supprimer l'option
    delete_option('fand_pickup_page_id');
}