// Les variables mapMarkers, defaultCategory, i18n, etc. sont disponibles
// car le script est chargé après la balise <script> qui les définit.

let markers = []; // Marqueurs Leaflet
const popupThreshold = 10; // minutes avant ouverture/fermeture

// ===================================
// Fonctions utilitaires
// ===================================

function timeToMinutes(timeStr) {
    const [h, m] = timeStr.split(':').map(Number);
    return h * 60 + m;
}

function getCurrentDayIndex() {
    const jsDay = new Date().getDay(); // 0 = dimanche
    // Convention ISO/PHP : lundi = 0 ... dimanche = 6
    return jsDay === 0 ? 6 : jsDay - 1;
}

function getMainPopupContent(p) {
    const day = getCurrentDayIndex();
    const allHours = p.opening_hours || {};
    let hours = allHours[day];

    // Traitement du texte pour l'affichage
    const openStr = (hours && (Array.isArray(hours) || typeof hours === 'object')) 
        ? Object.values(hours).map(r => {
            // Correction ici : on utilise open_time et close_time
            const s = (r && r.open_time) ? String(r.open_time).substring(0, 5) : '??:??';
            const e = (r && r.close_time) ? String(r.close_time).substring(0, 5) : '??:??';
            return s + ' - ' + e;
        }).join(', ')
        : 'Horaires non définis';

    return `
        <strong>${p.branch_name}</strong><br>
        ${p.address}<br>
        <em>Boutique : ${p.vendor_name}</em><br>
        <small>Horaires : ${openStr}</small><br>
        <a href="${p.store_url}" target="_blank">Voir la boutique</a>
    `;
}

// ===================================
// Logique de Mise à Jour du Marqueur
// ===================================

function updateMarkers() {
    const now = new Date();
    const nowMinutes = now.getHours() * 60 + now.getMinutes();
    const dayIndex = getCurrentDayIndex();

    if (typeof markers === 'undefined' || !markers) return;

    markers.forEach(p => {
        const allHours = p.opening_hours || {}; 
        let todayHours = allHours[dayIndex] || [];

        // Sécurité conversion objet -> tableau
        if (todayHours !== null && typeof todayHours === 'object' && !Array.isArray(todayHours)) {
            todayHours = Object.values(todayHours);
        }

        let isOpen = false;
        let nextOpenTime = null;
        let soonClose = null;

        if (Array.isArray(todayHours)) {
            todayHours.forEach(r => {
                // SÉCURITÉ : On utilise open_time et close_time
                if (!r.open_time || !r.close_time) return;

                const openMin = timeToMinutes(String(r.open_time));
                const closeMin = timeToMinutes(String(r.close_time));

                // 1. Ouvert Actuellement
                if (nowMinutes >= openMin && nowMinutes < closeMin) {
                    isOpen = true;
                }

                // 2. Ouvre Bientôt
                const openTimeDiff = openMin - nowMinutes;
                if (!isOpen && openTimeDiff > 0 && openTimeDiff <= popupThreshold) {
                    nextOpenTime = String(r.open_time).substring(0, 5);
                }

                // 3. Ferme Bientôt
                const closeTimeDiff = closeMin - nowMinutes;
                if (isOpen && closeTimeDiff > 0 && closeTimeDiff <= popupThreshold) {
                    // SÉCURITÉ : substring sur close_time
                    soonClose = String(r.close_time).substring(0, 5);
                }
            });
        }

        // --- Mise à jour de la liste (Cercle Logo) ---
        const branchID = p.branch_id || p.ID;
        const $avatarCircle = jQuery('#avatar-branch-' + branchID);

        if ($avatarCircle.length > 0) {
            if (isOpen) {
                $avatarCircle.addClass('is-open').removeClass('is-closed');
            } else {
                $avatarCircle.addClass('is-closed').removeClass('is-open');
            }
        }

        // --- Mise à jour de l'icône ---
        p.marker.setIcon(isOpen ? iconOpen : iconClosed);

        // --- Gestion des Popups Automatiques ---
        if (nextOpenTime && !isOpen) {
            if (p.popupType !== 'soonOpen') {
                p.marker.setPopupContent(`<strong>${p.branch_name}</strong><br>Ouvre bientôt à ${nextOpenTime}`);
                p.marker.openPopup();
                p.popupType = 'soonOpen';
            }
        } else if (soonClose) {
            if (p.popupType !== 'soonClose') {
                p.marker.setPopupContent(`<strong>${p.branch_name}</strong><br>Ferme bientôt à ${soonClose}`);
                p.marker.openPopup();
                p.popupType = 'soonClose';
            }
        } else if (p.popupType !== null) {
            // Remise à zéro du popup
            p.marker.setPopupContent(getMainPopupContent(p));
            // On ne ferme pas forcément le popup, on le réinitialise juste pour le clic manuel
            if (p.popupType === 'soonOpen' || p.popupType === 'soonClose') {
                p.marker.closePopup();
            }
            p.popupType = null;
        }
    });
}
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

    if (typeof markers === 'undefined' || markers.length === 0) return;

    markers.forEach(p => {
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
            if (String(p.category).trim() !== String(category).trim()) {
                visible = false;
            }
        }

        // --- C. Filtre Pays (CORRIGÉ) ---
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
            if (!hasDay && pickupStatus !== "closed") visible = false;
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

let map; // Déclarer la carte globalement si nécessaire
let iconClosed, iconOpen;

document.addEventListener('DOMContentLoaded', () => {

    // 1. Initialisation Leaflet
    map = L.map('pickup-map').setView([46.6, 2.4], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    iconClosed = L.icon({
        iconUrl: fandPickupPluginUrl + "assets/images/fand_map_icon_rouge.png",
        iconSize: [40, 57],
        iconAnchor: [20, 57],
        popupAnchor: [0, -57]
    });

    iconOpen = L.icon({
        iconUrl: fandPickupPluginUrl + "assets/images/fand_map_icon_vert.png",
        iconSize: [40, 57],
        iconAnchor: [20, 57],
        popupAnchor: [0, -57]
    });

    // 2. Création des marqueurs
    mapMarkers.forEach(p => {
        const marker = L.marker([p.lat, p.lng], { icon: iconClosed }).addTo(map);
        // 1. Lier un popup est OBLIGATOIRE pour updateMarkers

        marker.bindPopup(getMainPopupContent(p)); // On enlève { autoClose: false, closeOnClick: false } pour la gestion manuelle

        // 2. Empêcher l'ouverture au clic en vue unique
        if (typeof isSingleView !== 'undefined' && isSingleView) {
            // Si c'est une vue unique, on enlève le listener de clic de Leaflet
            marker.off('click');
        }

        p.marker = marker;
        p.popupType = null;
        markers.push(p);
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
                // On récupère le nom de la branche et du vendeur à l'intérieur de TA structure
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
    });

    // 5. Gestion de la géolocalisation et du premier affichage
    const initialUpdate = (error) => {
        applyFilters();
        updateMarkers();
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            map.setView([lat, lng], 15);
            document.getElementById("pickup-lat").value = lat;
            document.getElementById("pickup-lng").value = lng;
            initialUpdate();
        }, initialUpdate); // En cas d'erreur ou refus
    } else {
        initialUpdate();
    }

    // 6. Mise à jour périodique du statut (ouvert/fermé)
    setInterval(updateMarkers, 60000); // toutes les minutes

});

