(function($) {
    "use strict";

    $(document).ready(function() {
        var searchTimer;
        var typingTimer;
        var $form = $('.wcfmmp-store-search-form');
        var $radiusInput = $('#wcfmmp_radius_addr');
        var $tooltip = $('.search-tooltip');

        // 1. Mise à jour texte + Recherche automatique (Slider Radius)
        $(document).on('input change', '#wcfmmp_radius_range', function() {
            var val = $(this).val();
            $(this).siblings('.wcfmmp_radius_range_cur').text(val + ' Km');
            
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                $form.submit();
            }, 600);
        });

        // 2. Auto-complétion (Recherche d'adresse)
        $(document).on('input', '#wcfmmp_radius_addr', function() {
            var query = $(this).val();

            clearTimeout(typingTimer);
            if (query.length < 3) {
                $tooltip.hide();
                return;
            }

            typingTimer = setTimeout(function() {
                $.getJSON('https://nominatim.openstreetmap.org/search?format=json&q=' + query, function(data) {
                    $tooltip.empty().show();
                    if (data.length > 0) {
                        $.each(data, function(i, item) {
                            $tooltip.append('<li class="search-tip" data-lat="'+item.lat+'" data-lng="'+item.lon+'">' + item.display_name + '</li>');
                        });
                    } else {
                        $tooltip.append('<li class="search-tip">Aucun résultat</li>');
                    }
                });
            }, 400);
        });

        // 3. Clic sur une suggestion de la liste
        $(document).on('click', '.search-tip', function() {
            var lat  = $(this).data('lat');
            var lng  = $(this).data('lng');
            var addr = $(this).text();

            if (lat && lng) {
                $('#wcfmmp_radius_addr').val(addr);
                $('#wcfmmp_radius_lat').val(lat);
                $('#wcfmmp_radius_lng').val(lng);
                $tooltip.hide();
                $('#wcfm_radius_search_clear').show();
                $form.submit();
            }
        });

        // 4. Géolocalisation via l'icône cible (Version Robuste)
        $(document).on('click', '#wcfm_locate_me', function() {
            var $icon = $(this);
            var $inputField = $('.wcfmmp-radius-addr');
            if ($inputField.length === 0) $inputField = $('#wcfmmp_radius_addr');

            // 1. On récupère le nonce depuis l'attribut data de l'icône
            var nonce = $icon.data('nonce');

            // Détection de l'URL AJAX si la variable WCFM manque
            var ajax_url = (typeof wcfm_params !== 'undefined') ? wcfm_params.ajax_url : '/wp-admin/admin-ajax.php';

            if (navigator.geolocation) {
                $icon.css('opacity', '0.5');
                $inputField.val('Localisation GPS...'); 

                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;

                    $('#wcfmmp_radius_lat').val(lat);
                    $('#wcfmmp_radius_lng').val(lng);

                    $inputField.val('Conversion en adresse...');

                    $.ajax({
                        url: ajax_url,
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            action: 'fandpipo_get_address_from_gps',
                            lat: lat,
                            lng: lng,
                            nonce: nonce // Envoi du nonce pour la vérification côté serveur
                        },
                        success: function(data) {
                            if (data && data.display_name) {
                                // ENFIN ! L'adresse complète
                                $inputField.val(data.display_name);
                                $('#wcfm_radius_search_clear').show();
                                
                                setTimeout(function() {
                                    $inputField.closest('form').submit();
                                }, 1000);
                            } else {
                                $inputField.val("Adresse introuvable (Format JSON incorrect)");
                            }
                            $icon.css('opacity', '1');
                        },
                        error: function(xhr, status, error) {
                            console.error("Erreur AJAX :", status, error);
                            $inputField.val("Erreur serveur : " + status);
                            $icon.css('opacity', '1');
                        }
                    });

                }, function(error) {
                    alert("Pour voir les commerces autour de vous, veuillez autoriser la localisation dans les réglages de votre navigateur (cliquez sur le cadenas à gauche de l'adresse).");
                    $icon.css('opacity', '1');
                }, { enableHighAccuracy: false, timeout: 10000 });
            }
        });

        // 5. Reset via la croix (⊗)
        $(document).on('click', '#wcfm_radius_search_clear', function(e) {
            e.preventDefault();
            $('#wcfmmp_radius_addr, #wcfmmp_radius_lat, #wcfmmp_radius_lng').val('');
            $(this).hide();
            $tooltip.hide();
            $form.submit(); 
        });

        // 6. Fermer la liste si on clique en dehors
        $(document).on('click', function(event) {
            if (!$(event.target).closest('.leaflet-control-search').length) {
                $tooltip.hide();
            }
        });

        // Affichage initial de la croix
        if ($('#wcfmmp_radius_addr').val() !== "") {
            $('#wcfm_radius_search_clear').show();
        }

        /** Mise à jour du texte du rayon au chargement sans soumettre le formulaire
         */
        var $rangeInput = $('#wcfmmp_radius_range');
        if ($rangeInput.length) {
            var initialVal = $rangeInput.val();
            // On met à jour le texte (ex: 50 Km) sans déclencher de submit()
            $rangeInput.siblings('.wcfmmp_radius_range_cur').text(initialVal + ' Km');
        }
    });

})(jQuery);