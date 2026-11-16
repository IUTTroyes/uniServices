import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// Conserver la date civile locale : toISOString() peut décaler le jour en UTC.
const dateCivile = (date) => {
  if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) return date;
  const value = date instanceof Date ? date : new Date(date);
  if (Number.isNaN(value.getTime())) throw new Error('Date de calendrier invalide.');
  return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
};

const getCalendrierUniversitaireService = async (anneeUniversitaire, date = null, showToast = false) => {
  if (!anneeUniversitaire) throw new Error('Une année universitaire est requise.');
  return apiCall(
    api.get,
    [`/api/annees-universitaires/${anneeUniversitaire}/calendrier`, { params: date === null ? {} : { date: dateCivile(date) } }],
    'Calendrier récupéré avec succès',
    'Impossible de calculer le calendrier universitaire',
    showToast
  );
};

const getSemaineUniversitaireService = async (date, anneeUniversitaire, showToast = false) => {
  const response = await getCalendrierUniversitaireService(anneeUniversitaire, date, showToast);
  return response.semaine;
};

export { getCalendrierUniversitaireService, getSemaineUniversitaireService };
