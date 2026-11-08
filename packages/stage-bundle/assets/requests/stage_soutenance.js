import api from '@helpers/axios';
import { elements } from './client';

const getStageSoutenancesService = async (stagePeriodeId) =>
    elements((await api.get('/api/stage_soutenances', { params: { stagePeriode: stagePeriodeId, pagination: false } })).data);

/** Création ou modification d'un créneau (contrôle des conflits côté serveur). */
const saveStageSoutenanceService = async (data) => (await api.post('/api/stage_soutenances/save', data)).data;

/** Conflits d'un créneau envisagé : { hasConflict, messages }. */
const checkConflitStageSoutenanceService = async (data) => (await api.post('/api/stage_soutenances/validate-conflict', data)).data;

/** Génération d'une série de créneaux sur une journée : { slotsCreated }. */
const generateStageSoutenancesService = async (data) => (await api.post('/api/stage_soutenances/bulk-generate', data)).data;

const deleteStageSoutenanceService = async (id) => { await api.delete(`/api/stage_soutenances/${id}`); };

export {
    getStageSoutenancesService,
    saveStageSoutenanceService,
    checkConflitStageSoutenanceService,
    generateStageSoutenancesService,
    deleteStageSoutenanceService
};
