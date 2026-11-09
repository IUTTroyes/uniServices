import api from '@helpers/axios';
import { elements, JSON_LD, MERGE_PATCH } from './client';

// ---------- Grille d'une période (responsable des stages) ----------

const getCriteresEvaluationService = async (periodeId) =>
    elements((await api.get('/api/stage_evaluation_criteres', { params: { stagePeriode: periodeId, pagination: false } })).data);

const createCritereEvaluationService = async (periodeId, data) =>
    (await api.post('/api/stage_evaluation_criteres', { ...data, stagePeriode: `/api/stage_periodes/${periodeId}` }, JSON_LD)).data;

const updateCritereEvaluationService = async (id, data) => (await api.patch(`/api/stage_evaluation_criteres/${id}`, data, MERGE_PATCH)).data;

const deleteCritereEvaluationService = async (id) => { await api.delete(`/api/stage_evaluation_criteres/${id}`); };

// ---------- Suivi d'un dossier (tuteur universitaire, responsables) ----------

const getEvaluationEntrepriseService = async (stageId) => (await api.get(`/api/stage_etudiants/${stageId}/evaluation-entreprise`)).data;

/** Envoie (ou renvoie) le lien d'évaluation au tuteur en entreprise. */
const envoyerEvaluationEntrepriseService = async (stageId) => (await api.post(`/api/stage_etudiants/${stageId}/evaluation-entreprise/envoyer`)).data;

// ---------- Formulaire public (tuteur en entreprise, sans compte) ----------

const getFormulaireEvaluationService = async (jeton) => (await api.get(`/api/stage/evaluation-entreprise/${encodeURIComponent(jeton)}`)).data;

const soumettreEvaluationService = async (jeton, data) => (await api.post(`/api/stage/evaluation-entreprise/${encodeURIComponent(jeton)}`, data)).data;

export {
    getCriteresEvaluationService,
    createCritereEvaluationService,
    updateCritereEvaluationService,
    deleteCritereEvaluationService,
    getEvaluationEntrepriseService,
    envoyerEvaluationEntrepriseService,
    getFormulaireEvaluationService,
    soumettreEvaluationService
};
