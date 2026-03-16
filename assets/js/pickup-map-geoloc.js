window.initGeoLogic = function(map) {
    // 6. Événement : Déplacement (Fin)
    window.map.on('moveend', () => {
        const center = window.map.getCenter();
        const range = parseInt(document.getElementById("wcfmmp_radius_range")?.value) || 5;

        // Mise à jour fluide du cercle existant
        updateSearchCircle(startLat, startLng, startRange);

        document.getElementById("wcfmmp_radius_lat").value = center.lat.toFixed(4);
        document.getElementById("wcfmmp_radius_lng").value = center.lng.toFixed(4);

        if (window.getDist(window.FandPipoState.lastLat, window.FandPipoState.lastLng, center.lat, center.lng) > 0.5) {
            window.FandPipoState.lastLat = center.lat;
            window.FandPipoState.lastLng = center.lng;

            jQuery.ajax({ 
                url: fandpipo_pickup_data.ajax_url,
                type: 'GET',
                dataType: 'json',
                data: { action: 'get_reverse_address', lat: center.lat.toFixed(6), lng: center.lng.toFixed(6) },
                success: function(response) {
                    if (response && response.display_name) {
                        const input = document.getElementById("wcfmmp_radius_addr");
                        if (input) input.value = response.display_name;
                    }
                }
            });
        }

        clearTimeout(window.FandPipoState.debounceTimer);
        window.FandPipoState.debounceTimer = setTimeout(() => {
            const addrField = document.getElementById("wcfmmp_radius_addr");
            if (addrField && addrField.value !== "") {
                const form = document.querySelector('.wcfmmp-store-search-form');
                if (form) form.submit();
            }
        }, 1200);
    });
};