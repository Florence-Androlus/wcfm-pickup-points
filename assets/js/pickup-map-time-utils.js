// Fonctions utilitaires globales
function timeToMinutes(timeStr) {
    const [h, m] = timeStr.split(':').map(Number);
    return h * 60 + m;
}

function getCurrentDayIndex() {
    const jsDay = new Date().getDay();
    return jsDay === 0 ? 6 : jsDay - 1;
}

function getMainPopupContent(p) {
    const dayIndex = getCurrentDayIndex();
    const daysNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    const currentDayName = daysNames[dayIndex];
    const allHours = p.opening_hours || {};
    let hours = allHours[dayIndex];
    let hoursDisplay = 'Closed today';

    if (hours && (Array.isArray(hours) || typeof hours === 'object')) {
        let hoursArray = Object.values(hours).sort((a, b) => timeToMinutes(String(a.open_time)) - timeToMinutes(String(b.open_time)));
        hoursDisplay = hoursArray.map(r => `<strong>${String(r.open_time).substring(0, 5)} - ${String(r.close_time).substring(0, 5)}</strong>`).join(', ');
    }

    return `<div class="fand-popup-content"><strong>${p.branch_name}</strong><br>${p.address}<hr><div style="margin-bottom: 5px;">${currentDayName} : ${hoursDisplay}</div><a href="${p.store_url}" target="_blank">Voir la boutique →</a></div>`;
}

var updateMarkers = updateMarkers || function() { console.log("UpdateMarkers: Statique"); };