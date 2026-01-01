(function($){
    $(document).on('change', '#wcfmmp_pickup_store_day, #wcfmmp_pickup_store_status, #wcfmmp_pickup_store_orderby', function(){
        var pickup_day = $('#wcfmmp_pickup_store_day').val();
        var pickup_status = $('#wcfmmp_pickup_store_status').val();
        var pickup_orderby = $('#wcfmmp_pickup_store_orderby').val();

        $.ajax({
            url: ajaxurl,
            method: 'GET',
            data: {
                action: 'fand_filter_pickups',
                pickup_day: pickup_day,
                pickup_status: pickup_status,
                pickup_orderby: pickup_orderby,
            },
            beforeSend: function() {
                $('#wcfmmp-stores-wrap').fadeTo(200, 0.5);
            },
            success: function(response){
                // On remplace le contenu
                $('#wcfmmp-stores-wrap').html(response).fadeTo(200, 1);

                // --- NOUVEAU : Ré-appliquer le mode mémorisé ---
                var savedMode = localStorage.getItem('fand_pickup_layout') || 'grid';
                var wcfmList = $('#wcfmmp-stores-wrap ul.wcfmmp-store-wrap'); // Vérifie bien le sélecteur de ton UL
                
                if (savedMode === 'list') {
                    wcfmList.removeClass('grid').addClass('list');
                } else {
                    wcfmList.removeClass('list').addClass('grid');
                }
            }
        });
    });
})(jQuery);

jQuery(document).ready(function($) {
    // Sélecteurs adaptés à Kadence et WCFM
    const STORE_LIST_SELECTOR = 'ul.wcfmmp-store-wrap, ul.products';
    const KADENCE_HOVER_CLASS = 'woo-archive-action-on-hover';
    const BTN_GRID = '.kadence-toggle-grid';
    const BTN_LIST = '.kadence-toggle-list';

    function applyLayout(mode) {
        const list = $(STORE_LIST_SELECTOR);
        
        if (mode === 'list') {
            list.removeClass('grid ' + KADENCE_HOVER_CLASS).addClass('list');
            $(BTN_LIST).addClass('toggle-active');
            $(BTN_GRID).removeClass('toggle-active');
            localStorage.setItem('fand_pickup_layout', 'list');
        } else {
            // On force le mode GRID
            list.removeClass('list').addClass('grid ' + KADENCE_HOVER_CLASS);
            $(BTN_GRID).addClass('toggle-active');
            $(BTN_LIST).removeClass('toggle-active');
            localStorage.setItem('fand_pickup_layout', 'grid');
        }
    }

    // --- 1. AU CHARGEMENT DE LA PAGE ---
    // On récupère le choix stocké. Si rien n'est stocké, on met 'grid' par défaut.
    const savedMode = localStorage.getItem('fand_pickup_layout') || 'grid';
    
    // On l'applique tout de suite
    applyLayout(savedMode);

    // Sécurité : Kadence met parfois du temps à charger son propre script, 
    // on ré-applique après 300ms pour être sûr d'avoir le dernier mot.
    setTimeout(function() {
        applyLayout(localStorage.getItem('fand_pickup_layout') || 'grid');
    }, 300);

    // --- 2. GESTION DES CLICS ---
    $(document).on('click', BTN_GRID, function(e) {
        e.preventDefault();
        applyLayout('grid');
    });

    $(document).on('click', BTN_LIST, function(e) {
        e.preventDefault();
        applyLayout('list');
    });
});