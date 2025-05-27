import createApiService from '@requests/apiService';
import apiCall from '@helpers/apiCall';

// Base endpoint for FicheHeure, assuming API Platform routes are prefixed with /api
const ficheHeureApiService = createApiService('/api/fiches_heures');

// For BIATSS Users
export const getMesFichesHeures = async (params = {}, showToast = false) => {
  // GET /api/fiches_heures (params for filtering/pagination)
  return apiCall(ficheHeureApiService.getAll, [params], 'Fiches d\'heures récupérées', 'Erreur récupération fiches d\'heures', showToast);
};

export const getFicheHeure = async (id, showToast = false) => {
  // GET /api/fiches_heures/{id}
  return apiCall(ficheHeureApiService.getOne, [id], 'Fiche d\'heure récupérée', 'Erreur récupération fiche d\'heure', showToast);
};

export const createFicheHeure = async (data, showToast = true) => {
  // POST /api/fiches_heures
  return apiCall(ficheHeureApiService.create, [data], 'Fiche d\'heure créée', 'Erreur création fiche d\'heure', showToast);
};

export const updateFicheHeure = async (id, data, showToast = true) => {
  // PATCH /api/fiches_heures/{id} (General update)
  return apiCall(ficheHeureApiService.update, [id, data], 'Fiche d\'heure mise à jour', 'Erreur mise à jour fiche d\'heure', showToast);
};

export const submitFicheHeure = async (id, showToast = true) => {
  // PATCH /api/fiches_heures/{id}/submit
  // Assuming ficheHeureApiService.axiosInstance is available as per prompt.
  const callApi = () => ficheHeureApiService.axiosInstance.patch(`/api/fiches_heures/${id}/submit`);
  return apiCall(callApi, [], 'Fiche d\'heure soumise', 'Erreur soumission fiche d\'heure', showToast);
};

// For Validators
export const getFichesHeuresPourValidation = async (params = {}, showToast = false) => {
  // GET /api/fiches_heures (params for filtering for validators)
  // Backend should handle filtering based on user role (validator) and status (e.g., SOUMISE)
  return apiCall(ficheHeureApiService.getAll, [params], 'Fiches d\'heures pour validation récupérées', 'Erreur récupération fiches pour validation', showToast);
};

export const validateFicheHeure = async (id, showToast = true) => {
  // PATCH /api/fiches_heures/{id}/validate
  const callApi = () => ficheHeureApiService.axiosInstance.patch(`/api/fiches_heures/${id}/validate`);
  return apiCall(callApi, [], 'Fiche d\'heure validée', 'Erreur validation fiche d\'heure', showToast);
};

export const rejectFicheHeure = async (id, commentaire, showToast = true) => {
  // PATCH /api/fiches_heures/{id}/reject
  const callApi = () => ficheHeureApiService.axiosInstance.patch(`/api/fiches_heures/${id}/reject`, { commentaireValidation: commentaire });
  return apiCall(callApi, [], 'Fiche d\'heure rejetée', 'Erreur rejet fiche d\'heure', showToast);
};
