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
