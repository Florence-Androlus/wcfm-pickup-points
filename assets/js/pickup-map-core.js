window.map = null;
window.searchCircle = null;
var fandpipo_markers = []; // INDISPENSABLE pour la version PRO
window.iconClosed = null; 
window.iconOpen = null;
var popupThreshold = 10;
window.FandPipoState = {
    debounceTimer: null,
    lastLat: 0,
    lastLng: 0
};

document.addEventListener('DOMContentLoaded', () => {
    if (typeof fandpipoData === 'undefined') return;

    // 1. Initialisation
    const urlParams = new URLSearchParams(window.location.search);
    const startLat = parseFloat(urlParams.get('wcfmmp_radius_lat')) || fandpipoData.currentLat || 46.6;
    const startLng = parseFloat(urlParams.get('wcfmmp_radius_lng')) || fandpipoData.currentLng || 2.4;
    const startRange = parseInt(urlParams.get('wcfmmp_radius_range')) || 5;

    window.getDist = function(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * (Math.PI / 180);
        const dLon = (lon2 - lon1) * (Math.PI / 180);
        const a = Math.sin(dLat/2)**2 + Math.cos(lat1*(Math.PI/180)) * Math.cos(lat2*(Math.PI/180)) * Math.sin(dLon/2)**2;
        return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
    };

    window.map = L.map('pickup-map', { minZoom: 4, maxZoom: 18 }).setView([startLat, startLng], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' }).addTo(window.map);

    if (typeof window.initGeoLogic === 'function') {
        window.initGeoLogic(window.map);
    }

    // 3. FONCTION MODIFIÉE : Ne supprime plus, met à jour le cercle existant
    window.updateSearchCircle = function(lat, lng, range) {
        if (!window.searchCircle) {
            window.searchCircle = L.circle([lat, lng], {
                color: '#0073aa', fillColor: '#0073aa', fillOpacity: 0.15, weight: 2,
                radius: range * 1000
            }).addTo(window.map);
        } else {
            window.searchCircle.setLatLng([lat, lng]);
            window.searchCircle.setRadius(range * 1000);
        }
    };

    window.fitMapToRadius = function(lat, lng, radiusKm) {
        const circle = L.circle([lat, lng], { radius: radiusKm * 1000 });
        window.map.fitBounds(circle.getBounds(), { padding: [20, 20] });
    };

   // 4. Icônes et Marqueurs
    const wcfmIconUrl = window.location.origin + "/wp-content/plugins/wc-frontend-manager/includes/libs/leaflet/images/marker-icon.png";
    window.iconClosed = L.icon({ iconUrl: wcfmIconUrl, iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34] });
    window.iconOpen = window.iconClosed;

    if (typeof window.setupProIcons === 'function') {
        window.setupProIcons(); 
    }

    // 4. CRÉATION DES MARQUEURS
    if (fandpipoData && fandpipoData.markers) {
        console.log("Nombre de marqueurs à créer :", fandpipoData.markers.length);

        fandpipoData.markers.forEach((p, index) => {
            // Vérification que les coordonnées existent pour ce point précis
            if (!p.lat || !p.lng) {
                console.warn(`Le marqueur ${index} n'a pas de coordonnées valides.`);
                return; // On passe au suivant au lieu de tout bloquer
            }

            const isPro = (typeof PickupProData !== 'undefined' && PickupProData.is_licensed == 1);
            let currentIcon = window.iconClosed;

            // Calcul de l'icône Pro
            if (isPro && typeof window.checkIsOpen === 'function') {
                currentIcon = window.checkIsOpen(p) ? window.iconOpen : window.iconClosed;
            }

            // Création physique du marqueur
            const marker = L.marker([p.lat, p.lng], { icon: currentIcon }).addTo(window.map);

            // Bind du popup
            if (isPro && typeof getMainPopupContent === 'function') {
                marker.bindPopup(getMainPopupContent(p));
            } else {
                marker.bindTooltip(p.branch_name || "Point de retrait");
            }

            // Stockage pour la mise à jour dynamique
            p.marker = marker;
            p.popupType = null;
            window.fandpipo_markers.push(p);
        });
    }

    // 5. Événement : Déplacement (Début)
    window.map.on('movestart', () => {
        console.log("Mouvement détecté, retrait du cercle...");
        window.map.eachLayer(layer => { 
            if (layer instanceof L.Circle) {
                window.map.removeLayer(layer); 
            }
        });
    });

    // 6. Événement : Déplacement
    window.map.on('moveend', () => {
        const center = window.map.getCenter();
        const range = parseInt(document.getElementById("wcfmmp_radius_range")?.value) || 5;

        // Mise à jour fluide du cercle existant
       updateSearchCircle(center.lat, center.lng, range)

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

    // 7. Événement : Curseur
    document.getElementById('wcfmmp_radius_range').addEventListener('input', function() {
        const val = this.value;
        document.querySelector('.wcfmmp_radius_range_cur').textContent = val + ' Km';
        const center = window.map.getCenter();
        window.updateSearchCircle(center.lat, center.lng, val);
        window.fitMapToRadius(center.lat, center.lng, val);
        window.submitSearchForm(); 
    });

    window.submitSearchForm = function() {
        const form = document.querySelector('.wcfmmp-store-search-form');
        if (form) form.submit();
    };

    // 8. Lancement Initial PRO
    if (typeof window.updateMarkers === 'function' && typeof PickupProData !== 'undefined' && PickupProData.is_licensed == 1) {
        window.updateMarkers();
        setInterval(window.updateMarkers, 60000);
    }

    updateSearchCircle(startLat, startLng, startRange);
});