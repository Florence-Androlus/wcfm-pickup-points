// Les variables mapMarkers, defaultCategory, i18n, etc. sont disponibles
// car le script est chargé après la balise <script> qui les définit.

if (typeof fandpipo_markers === 'undefined') {
    var fandpipo_markers = []; // Marqueurs Leaflet
} 
var popupThreshold = 10; // minutes avant ouverture/fermeture

// ===================================
// Fonctions utilitaires
// ===================================

function timeToMinutes(timeStr) {
    const [h, m] = timeStr.split(':').map(Number);
    return h * 60 + m;
}

function getCurrentDayIndex() {
    const jsDay = new Date().getDay(); // 0 = Sunday
    // Convention ISO/PHP : Monday = 0 ... Sunday = 6
    return jsDay === 0 ? 6 : jsDay - 1;
}

function getMainPopupContent(p) {
    const dayIndex = getCurrentDayIndex();
    const daysNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    const currentDayName = daysNames[dayIndex];
    
    const allHours = p.opening_hours || {};
    let hours = allHours[dayIndex];

    let hoursDisplay = 'Closed to day';

    if (hours && (Array.isArray(hours) || typeof hours === 'object')) {
        // 1. Conversion en tableau et Tri pour éviter l'inversion (ex: 07:00 avant 22:00)
        let hoursArray = Object.values(hours).sort((a, b) => {
            return timeToMinutes(String(a.open_time)) - timeToMinutes(String(b.open_time));
        });

        // 2. Formatage des plages horaires
        hoursDisplay = hoursArray.map(r => {
            const s = (r && r.open_time) ? String(r.open_time).substring(0, 5) : '??:??';
            const e = (r && r.close_time) ? String(r.close_time).substring(0, 5) : '??:??';
            return `<strong>${s} - ${e}</strong>`;
        }).join(', ');
    }

    // 3. Construction du HTML du Popup
    return `
        <div class="fand-popup-content">
            <strong style="font-size:1.1em;">${p.branch_name}</strong><br>
            <span style="color: #666;">${p.address}</span><br>
            <hr style="margin: 5px 0; border: 0; border-top: 1px solid #eee;">
            <div style="margin-bottom: 5px;">
                <span class="day-label">${currentDayName} :</span> 
                <span class="hours-label">${hoursDisplay}</span>
            </div>
            <a href="${p.store_url}" target="_blank" style="display: inline-block; margin-top: 5px; color: #0073aa; text-decoration: none; font-weight: bold;">
                Voir la boutique →
            </a>
        </div>
    `;
}

// ===================================
// Logique de Mise à Jour du Marqueur
// ===================================
// On définit une fonction vide ou "placeholder" pour éviter les erreurs
// Elle sera écrasée si le fichier PRO est chargé
var updateMarkers = updateMarkers || function() { 
    // En version gratuite, on peut juste mettre une version ultra-simplifiée
    // ou laisser vide pour ne rien faire dynamiquement.
    console.log("UpdateMarkers: Version gratuite (statique)");
};

// ===================================
// Logique de Filtrage
// ===================================

function applyFilters() {
    const search = document.getElementById("pickup-search")?.value.toLowerCase().trim() || "";
    const category = document.getElementById("pickup-category")?.value || "";
    const country = document.getElementById("pickup-country")?.value || "";
    const pickupDayValue = document.getElementById("wcfmmp_pickup_store_day")?.value || "";
    const pickupDay = pickupDayValue !== "" ? parseInt(pickupDayValue, 10) : null;
    const pickupStatus = document.getElementById("wcfmmp_pickup_store_status")?.value || null;

    if (typeof fandpipo_markers === 'undefined' || fandpipo_markers.length === 0) return;

    fandpipo_markers.forEach(p => {
        let visible = true;

        // --- A. Filtre Recherche (Live) ---
        if (search) {
            const searchMatches =
                (p.branch_name || "").toLowerCase().includes(search) ||
                (p.address || "").toLowerCase().includes(search) ||
                (p.vendor_name || "").toLowerCase().includes(search);
            if (!searchMatches) visible = false;
        }

        // --- B. Filtre Catégorie ---
        // On ne filtre en JS que si le PHP ne l'a pas déjà fait (sécurité)
        if (visible && category && category !== "") {
            if (String(p.fandpipo_category).trim() !== String(category).trim()) {
                visible = false;
            }
        }

        // --- C. Filtre Pays  ---
        if (visible && country && country !== "") {
            const pCountry = String(p.country || "").toUpperCase();
            const fCountry = String(country).toUpperCase();

            // Si le pays du marqueur est "FRANCE" et le filtre "FR", on accepte quand même
            const isFrance = (pCountry === "FRANCE" && fCountry === "FR");

            if (pCountry !== fCountry && !isFrance) {
                visible = false;
            }
        }

        // --- D. Filtre par Jour ---
        if (visible && pickupDay !== null) {
            const hasDay = p.opening_hours && p.opening_hours[pickupDay] && p.opening_hours[pickupDay].length > 0;
            if (!hasDay && pickupStatus !== "Closed") visible = false;
        }

        // --- E. Filtre par Statut ---
        if (visible && pickupStatus) {
            const statusCheckDayIndex = pickupDay !== null ? pickupDay : getCurrentDayIndex();
            const todayHours = (p.opening_hours && p.opening_hours[statusCheckDayIndex]) || [];
            let hasOpeningHours = todayHours.length > 0;
            if (pickupStatus === "open" && !hasOpeningHours) visible = false;
            if (pickupStatus === "closed" && hasOpeningHours) visible = false;
        }

        // --- F. Affichage/Masquage ---
        if (visible) {
            p.marker.addTo(map);
        } else {
            p.marker.remove();
        }
    });
}

// ===================================
// Initialisation
// ===================================

var map; // Déclarer la carte globalement si nécessaire
var iconClosed, iconOpen;
var searchCircle; // Variable pour garder une trace du cercle

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. RÉCUPÉRATION (Le coffre-fort) ---
    if (typeof fandpipoData === 'undefined') {
        console.error("fandpipoData est introuvable");
        return;
    }

    // On vérifie si l'objet injecté par PHP existe
    if (typeof fandpipoData !== 'undefined') {
        mapMarkers   = fandpipoData.markers || [];
        isSingleView = fandpipoData.isSingleView || false;
        currentLat   = fandpipoData.currentLat || 46.6;
        currentLng   = fandpipoData.currentLng || 2.4;
    } else {
        console.error("L'objet fandpipoData est manquant. Vérifiez Scripts.php");
    }

    // --- 2. INITIALISATION LEAFLET (Ton code existant) ---
    // Maintenant startLat va trouver currentLat sans erreur !
    const startLat = (typeof currentLat !== 'undefined' && currentLat) ? currentLat : 46.6;
    const startLng = (typeof currentLng !== 'undefined' && currentLng) ? currentLng : 2.4;
    const startZoom = (typeof isSingleView !== 'undefined' && isSingleView) ? 15 : 6;

    map = L.map('pickup-map').setView([startLat, startLng], startZoom);


map.on('moveend', function() {
    // Vérifie si un verrou est actif dans la mémoire persistante du navigateur
    if (sessionStorage.getItem('isUpdating') === 'true') return;
    
    clearTimeout(window.moveTimer);
    window.moveTimer = setTimeout(function() {
        const center = map.getCenter();
        const lat = center.lat.toFixed(4);
        const lng = center.lng.toFixed(4);

        if (lat === window.lastLat && lng === window.lastLng) return;
        window.lastLat = lat;
        window.lastLng = lng;

        // --- VERROUILLAGE PERSISTANT ---
        sessionStorage.setItem('isUpdating', 'true');

        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById("wcfmmp_radius_lat").value = lat;
            document.getElementById("wcfmmp_radius_lng").value = lng;
            document.getElementById("wcfmmp_radius_addr").value = data.display_name;

            // Déclenchement de la soumission
            jQuery('.wcfmmp-store-search-form').trigger('submit');
        })
        .catch(() => { 
            sessionStorage.removeItem('isUpdating'); 
        });
    }, 1000);
});

// Nettoyage : On déverrouille dès que la page a fini de charger
window.addEventListener('load', () => {
    setTimeout(() => { sessionStorage.removeItem('isUpdating'); }, 2000);
});

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Définition de l'icône par défaut (WCFM Original)
    // On construit l'URL pour pointer vers le dossier de WCFM
    const wcfmIconUrl = window.location.origin + "/wp-content/plugins/wc-frontend-manager/includes/libs/leaflet/images/marker-icon.png";
    // On initialise les icônes avec l'image par défaut
    // Elles sont déclarées SANS 'const' ou 'let' car elles ont été déclarées globalement au début du fichier
    iconClosed = L.icon({
        iconUrl: wcfmIconUrl,
        iconSize: [25, 41],
        iconAnchor: [20, 57],
        popupAnchor: [0, -57]
    });

    iconOpen = iconClosed; // En gratuit, pas de distinction de couleur

    // --- LOGIQUE PRO : Surcharge des icônes ---
    // Si une fonction de surcharge existe (définie dans le script Pro), on l'appelle
    // On vérifie si la fonction setupProIcons a été injectée par le fichier PRO
    if (typeof window.setupProIcons === 'function') {
        window.setupProIcons();
    }

    // 2. Création des marqueurs
    mapMarkers.forEach(p => {
        const marker = L.marker([p.lat, p.lng], { icon: iconClosed }).addTo(map);

        // MODIFICATION : On ne lie le popup QUE si on n'est PAS en vue unique
        if (typeof isSingleView === 'undefined' || !isSingleView) {
            marker.bindPopup(getMainPopupContent(p));
        } else {
            // En vue unique, on peut désactiver l'interaction avec le marqueur
            marker.off('click'); 
            // Optionnel : changer le curseur pour montrer qu'il n'est pas cliquable
            marker.getElement().style.cursor = 'default';
        }

        p.marker = marker;
        p.popupType = null;
        fandpipo_markers.push(p);
    });

    // 3. Initialisation Select2 (doit être fait après le chargement du DOM)
    jQuery(document).ready(function($) {
        // Initialise Select2
        $('#pickup-country').select2();

        // Déclenche la soumission du formulaire parent lors du changement
        $('#pickup-country').on('change', function() {
            $(this).closest('form').submit();
        });

        $('#pickup-category').on('change', function() {
            $(this).closest('form').submit(); // Recharge pour filtrer proprement la liste PHP
        });

        // --- FILTRAGE LIVE (RECHERCHE) ---
        $('#pickup-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase().trim();

            // 1. On filtre les cartes (HTML)
            $('.wcfmmp-single-store').each(function() {
                const $card = $(this);
                // On récupère le nom de la branche et du vendeur à l'intérieur de la structure
                const branchName = $card.find('.branch-title').first().text().toLowerCase();
                const vendorName = $card.find('.branch-title a').text().toLowerCase();
                const address = $card.find('.store-address').text().toLowerCase();

                if (branchName.includes(searchTerm) || vendorName.includes(searchTerm) || address.includes(searchTerm)) {
                    $card.fadeIn(200); // On montre
                } else {
                    $card.fadeOut(200); // On cache
                }
            });

            // 2. On synchronise les marqueurs sur la carte Leaflet
            // On appelle la fonction applyFilters (assure-toi qu'elle prend en compte la recherche)
            applyFilters();

            // 3. Mise à jour du compteur
            setTimeout(() => {
                const visibleCount = $('.wcfmmp-single-store:visible').length;
                $('.woocommerce-result-count').text('Montrer ' + visibleCount + ' résultat' + (visibleCount > 1 ? 's' : ''));
            }, 250);
        });

        // --- FILTRAGE PAYS (RECHARGEMENT) ---
        // On garde le rechargement pour le pays car c'est une requête base de données différente
        $('#pickup-country').on('change', function() {
            $(this).closest('form').submit();
        });

        /**
         * Gestion du changement de layout (Grid/List)
         * Spécifique aux thèmes utilisant Kadence ou des sélecteurs similaires
         */
        // Sélecteur 1 : Le conteneur '#products-wrapper'
        const $productWrapper = $('#products-wrapper'); 

        // Sélecteur 2 : Le conteneur principal du magasin (si #products-wrapper est trop petit)
        // Essayons un conteneur plus général si #products-wrapper ne fonctionne pas.
        const $storeContent = $productWrapper.closest('.product_area'); // Remonte au parent .product_area

        $('.kadence-toggle-shop-layout').on('click', function(e) {
            e.preventDefault();
            const toggleType = $(this).data('archive-toggle'); 

            // 1. Gérer les classes actives des boutons
            $('.kadence-toggle-shop-layout').removeClass('toggle-active');
            $(this).addClass('toggle-active');

            // 2. Appliquer les classes de vue
            // On retire les anciennes classes 'list'/'grid' et on ajoute la nouvelle.
            $productWrapper.removeClass('list grid').addClass(toggleType); 
            $storeContent.removeClass('list grid').addClass(toggleType); 
            $productWrapper.find('ul.products').removeClass('list grid').addClass(toggleType); // Cible la liste des produits WooCommerce
        });

        // Initialisation au chargement
        const $activeButton = $('.kadence-toggle-shop-layout.toggle-active');
        if ($activeButton.length) {
            const defaultToggle = $activeButton.data('archive-toggle');
            $productWrapper.addClass(defaultToggle);
            $storeContent.addClass(defaultToggle);
            $productWrapper.find('ul.products').addClass(defaultToggle);
        }
        
    });

    // 5. Gestion de la géolocalisation et du premier affichage
    const initialUpdate = (error) => {
        applyFilters();
        updateMarkers();
        // En vue unique, on force l'ouverture du popup au démarrage
        if (typeof isSingleView !== 'undefined' && isSingleView && mapMarkers.length > 0) {
            mapMarkers[0].marker.openPopup();
        }
    }

    
    // Logique de géolocalisation automatique :
    const urlParams = new URLSearchParams(window.location.search);
    const hasRadiusSearch = urlParams.has('wcfmmp_radius_lat') && urlParams.get('wcfmmp_radius_lat') !== "";

    if (hasRadiusSearch) {
        const rLat = parseFloat(urlParams.get('wcfmmp_radius_lat'));
        const rLng = parseFloat(urlParams.get('wcfmmp_radius_lng'));
        const rRange = parseInt(urlParams.get('wcfmmp_radius_range')) || 5; // Rayon en Km, par défaut 5 Km

        // Calcul du zoom approximatif pour Leaflet selon le rayon (Km)
        // Plus le rayon est grand, plus le zoom doit être petit
        let zoomLevel = 10;
        if (rRange <= 5) zoomLevel = 13;
        else if (rRange <= 15) zoomLevel = 11;
        else if (rRange <= 50) zoomLevel = 9;
        else if (rRange <= 150) zoomLevel = 7;
        else if (rRange <= 300) zoomLevel = 6;
        else zoomLevel = 5;

        map.setView([rLat, rLng], zoomLevel);

        //Nettoyage du cercle précédent s'il existe
        if (searchCircle) {
            map.removeLayer(searchCircle);
        }

        // OPTIONNEL : Dessiner le cercle bleu du rayon sur la carte
        if (rRange > 1) {
            searchCircle = L.circle([rLat, rLng], {
                color: '#0073aa',
                fillColor: '#0073aa',
                fillOpacity: 0.15,
                weight: 2,
                radius: rRange * 1000 // Conversion Km en Mètres
            }).addTo(map);
        }

        initialUpdate();
    } else if (navigator.geolocation && (typeof isSingleView === 'undefined' || !isSingleView)) {
        // Nettoyage immédiat d'un éventuel cercle résiduel d'une recherche précédente
        if (searchCircle) {
            map.removeLayer(searchCircle);
            searchCircle = null;
        }
        
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            map.setView([lat, lng], 13);
            // On remplit les champs pour le script de filtrage JS
            if(document.getElementById("pickup-lat")) document.getElementById("pickup-lat").value = lat;
            if(document.getElementById("pickup-lng")) document.getElementById("pickup-lng").value = lng;
            
            // On remplit aussi les champs pour le calcul PHP (Radius)
            if(document.getElementById("wcfmmp_radius_lat")) document.getElementById("wcfmmp_radius_lat").value = lat;
            if(document.getElementById("wcfmmp_radius_lng")) document.getElementById("wcfmmp_radius_lng").value = lng;

            initialUpdate();
        }, initialUpdate); 
    } else {
        // Si on arrive ici, c'est qu'il n'y a pas de recherche active
        // On nettoie le cercle au cas où
        if (searchCircle) {
            map.removeLayer(searchCircle);
            searchCircle = null;
        }
        // Si on est en Single View, on reste sur les coordonnées du PHP
        initialUpdate();
    }

    // 6. Mise à jour périodique du statut (ouvert/fermé)
    // On ne lance le timer que si on est en mode PRO (updateMarkers a été remplacé)
    if (typeof PickupProData !== 'undefined' && PickupProData.is_licensed == 1) {
        updateMarkers();
        setInterval(updateMarkers, 60000);
    }

});