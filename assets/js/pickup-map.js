// 1. Déclarations globales en haut du fichier (hors de la fonction)
window.map = null;
window.mapMarkers = [];
window.isSingleView = false;
window.currentLat = 46.6;
window.currentLng = 2.4;



// --- 1. FONCTION D'INITIALISATION ---
function initMap() {
    const urlParams = new URLSearchParams(window.location.search);
    const formLat = document.getElementById("wcfmmp_radius_lat")?.value;
    const formLng = document.getElementById("wcfmmp_radius_lng")?.value;

    // A. Configuration de la carte
    const startLat = (formLat) ? parseFloat(formLat) : (fandpipoData.currentLat || 46.6);
    const startLng = (formLng) ? parseFloat(formLng) : (fandpipoData.currentLng || 2.4);
    const startZoom = (typeof isSingleView !== 'undefined' && isSingleView) ? 15 : 6;

    window.map = L.map('pickup-map').setView([startLat, startLng], startZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(window.map);

    // B. Icônes
    const wcfmIconUrl = window.location.origin + "/wp-content/plugins/wc-frontend-manager/includes/libs/leaflet/images/marker-icon.png";
    window.iconClosed = L.icon({ iconUrl: wcfmIconUrl, iconSize: [25, 41], iconAnchor: [20, 57], popupAnchor: [0, -57] });
    window.iconOpen = window.iconClosed;

    if (typeof window.setupProIcons === 'function') window.setupProIcons();

    // C. Marqueurs
    window.mapMarkers.forEach(p => {
        const marker = L.marker([p.lat, p.lng], { icon: window.iconClosed }).addTo(window.map);
        if (!window.isSingleView) {
            marker.bindPopup(getMainPopupContent(p));
        } else {
            marker.off('click');
        }
        p.marker = marker;
        window.fandpipo_markers.push(p);
    });

    // D. Lancement des services
    initMapEvents(); // Événements (moveend, etc.)
    initSearchAndGeo(); // Géolocalisation et filtres
}

// --- 2. LANCEMENT AU CHARGEMENT ---
document.addEventListener('DOMContentLoaded', function() {
    // 2. Vérification de sécurité
    if (typeof fandpipoData === 'undefined') {
        console.error("ERREUR : fandpipoData est introuvable. Vérifiez Scripts.php");
        return;
    }
    
    // 3. Assignation sécurisée
    window.mapMarkers = fandpipoData.markers || [];
    window.isSingleView = fandpipoData.isSingleView || false;
    window.currentLat = fandpipoData.currentLat || 46.6;
    window.currentLng = fandpipoData.currentLng || 2.4;
    
    console.log("Données chargées avec succès :", window.mapMarkers.length, "marqueurs trouvés.");

    // Maintenant, tu peux appeler ta fonction d'initialisation de la carte
    initMap();
});