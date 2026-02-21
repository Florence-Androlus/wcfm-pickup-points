document.addEventListener('DOMContentLoaded', function() {
    // 1. RÉCUPÉRATION SÉCURISÉE
    if (typeof fandpipoData === 'undefined') {
        console.error("ERREUR : fandpipoData est introuvable. Vérifiez Scripts.php");
        return;
    }
    
    // On remplit nos variables avec les données de PHP
    mapMarkers      = fandpipoData.markers || [];
    isSingleView    = fandpipoData.isSingleView || false;
    currentLat      = fandpipoData.currentLat || 46.6;
    currentLng      = fandpipoData.currentLng || 2.4;
    const defaultCategory = fandpipoData.defaultCategory;

});