import { format, addDays } from 'date-fns';

export const jourLocal = (date = new Date()) => format(date, 'yyyy-MM-dd');
export const instant = (date) => {
  const locale = new Date(date);
  if (!date || Number.isNaN(locale.getTime())) {
    throw new Error('Renseignez une date et une heure valides.');
  }
  return locale.toISOString().replace(/\.\d{3}Z$/, 'Z');
};
export const dateHeure = (date) => date ? new Date(date).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' }) : '';
export const periodePlanning = (date = new Date()) => ({ debut: jourLocal(date), fin: jourLocal(addDays(date, 14)) });
export const messageErreur = (erreur) => erreur.response?.data?.message || erreur.response?.data?.detail || erreur.message;
