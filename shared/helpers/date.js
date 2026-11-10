export function formatDateCourt(date) {
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'Invalid Date';
    return d.toLocaleDateString();
}

export function formatDateLong(date) {
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'Invalid Date';
    return d.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

export function jourDate(date) {
    const d = new Date(date);
    if (isNaN(d.getTime())) return 'Invalid Date';
    return d.toLocaleDateString('fr-FR', {
        weekday: 'long'
    });
}

export function heuresMinutesDate(date) {
    const localDate = new Date(date);
    if (isNaN(localDate.getTime())) return 'Invalid Date';
    return localDate.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

export function getISOWeekNumber(date) {
    const tempDate = new Date(date);
    if (isNaN(tempDate.getTime())) return NaN;
    tempDate.setHours(0, 0, 0, 0);
    tempDate.setDate(tempDate.getDate() + 3 - ((tempDate.getDay() + 6) % 7));
    const week1 = new Date(tempDate.getFullYear(), 0, 4);
    return 1 + Math.round(((tempDate - week1) / 86400000 - 3 + ((week1.getDay() + 6) % 7)) / 7);
}

export function parseApiDate(dateStr) {
    if (!dateStr) return null;
    if (dateStr instanceof Date) return dateStr;
    const parts = typeof dateStr === 'string' ? dateStr.split('-') : [];
    if (parts.length !== 3) return new Date(dateStr);
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);
    return new Date(year, month, day);
};

/** Présentations de date en français, communes aux écrans. */
export const STYLES_DATE = {
    moyenne: { day: 'numeric', month: 'short', year: 'numeric' },
    longue: { day: 'numeric', month: 'long', year: 'numeric' },
    jourMois: { weekday: 'long', day: 'numeric', month: 'long' },
    moisAnnee: { month: 'short', year: 'numeric' },
    moisAnneeLong: { month: 'long', year: 'numeric' },
    jourMoisHeure: { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' },
    moyenneHeure: { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' },
};

/**
 * Date lisible en français selon un style prédéfini (voir STYLES_DATE).
 * Renvoie `vide` (null par défaut) si la date est absente.
 */
export function formatDateFr(date, style = 'moyenne', vide = null) {
    if (!date) return vide;

    return new Date(date).toLocaleDateString('fr-FR', STYLES_DATE[style] ?? STYLES_DATE.moyenne);
}
