// Fonctions utilitaires de calcul
function timeToMinutes(timeStr) {
    if (!timeStr) return 0;
    const [h, m] = timeStr.split(':').map(Number);
    return h * 60 + m;
}

function getCurrentDayIndex() {
    const jsDay = new Date().getDay();
    // Ajustement pour Lundi=0, Dimanche=6
    return jsDay === 0 ? 6 : jsDay - 1;
}

function getMainPopupContent(p) {
    const dayIndex = getCurrentDayIndex();
    const daysNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    const currentDayName = daysNames[dayIndex];
    
    const allHours = p.opening_hours || {};
    let hours = allHours[dayIndex];
    let hoursDisplay = 'Fermé aujourd\'hui';

    if (hours && (Array.isArray(hours) || typeof hours === 'object')) {
        let hoursArray = Object.values(hours).sort((a, b) => {
            return timeToMinutes(String(a.open_time)) - timeToMinutes(String(b.open_time));
        });

        hoursDisplay = hoursArray.map(r => {
            const s = (r && r.open_time) ? String(r.open_time).substring(0, 5) : '??:??';
            const e = (r && r.close_time) ? String(r.close_time).substring(0, 5) : '??:??';
            return `<strong>${s} - ${e}</strong>`;
        }).join(', ');
    }

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