export function formatDateCourt(date) {
  return new Date(date).toLocaleDateString();
}

export function formatDateLong(date) {
  return new Date().toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}
