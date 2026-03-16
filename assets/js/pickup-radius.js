(function($) {
    "use strict";

    // 1. Fonction de redirection avec User-Agent pour respecter Nominatim
    function redirectWithCoords(lat, lng, range = null) {
        var url = window.location.origin + window.location.pathname + 
                  '?wcfmmp_radius_lat=' + lat + 
                  '&wcfmmp_radius_lng=' + lng;
        
        if (range) { url += '&wcfmmp_radius_range=' + range; }
        window.location.href = url;
    }

    $(document).ready(function() {
        var $tooltip = $('.search-tooltip');
        var timer = null; // Un seul timer suffit pour tout gérer

        // Auto-complétion avec User-Agent requis
        $(document).on('input', '#wcfmmp_radius_addr', function() {
            var query = $(this).val();
            clearTimeout(timer);
            
            if (query.length < 4) { 
                $tooltip.hide(); 
                return; 
            }

            timer = setTimeout(function() {
                $.ajax({
                    url: fandpipo_pickup_data.ajax_url,
                    type: 'GET',
                    data: { action: 'get_osm_address', q: query },
                    success: function(response) {
                        $tooltip.empty().show();
                        
                        if (response && Array.isArray(response)) {
                            $.each(response, function(i, item) {
                                // N'oublie pas de mettre ta classe CSS ici si nécessaire
                                $tooltip.append('<li class="search-tip" data-lat="'+item.lat+'" data-lng="'+item.lon+'">' + item.display_name + '</li>');
                            });
                        } else {
                            $tooltip.append('<li>Aucun résultat trouvé.</li>');
                        }
                    },
                    error: function() {
                        $tooltip.empty().show().append('<li>Erreur temporaire, réessayez.</li>');
                    }
                });
            }, 800); // Fin du setTimeout
        }); // Fin du .on('input')

        // Clic sur une suggestion
        $(document).on('click', '.search-tip', function() {
            redirectWithCoords($(this).data('lat'), $(this).data('lng'));
        });

        // Reset
        $(document).on('click', '#wcfm_radius_search_clear', function(e) {
            e.preventDefault();
            window.location.href = window.location.origin + window.location.pathname;
        });

        // Géolocalisation
        $(document).on('click', '#wcfm_locate_me', function() {
            var $icon = $(this);
            var ajax_url = (typeof wcfm_params !== 'undefined') ? wcfm_params.ajax_url : '/wp-admin/admin-ajax.php';

            if (navigator.geolocation) {
                $icon.css('opacity', '0.5');
                navigator.geolocation.getCurrentPosition(function(pos) {
                    // On redirige directement avec les coordonnées GPS
                    redirectWithCoords(pos.coords.latitude, pos.coords.longitude);
                }, function() { alert("Erreur localisation"); $icon.css('opacity', '1'); });
            }
        });

        // --- Slider Radius ---
        $(document).on('input', '#wcfmmp_radius_range', function() {
            var val = $(this).val();
            $(this).siblings('.wcfmmp_radius_range_cur').text(val + ' Km');
            
            var lat = $('#wcfmmp_radius_lat').val();
            var lng = $('#wcfmmp_radius_lng').val();

            if (lat && lng) {
                clearTimeout(timer);
                timer = setTimeout(function() {
                    redirectWithCoords(lat, lng, val);
                }, 1000); // Délai confortable pour le slider
            }
        });
    });
})(jQuery);