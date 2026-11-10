import api from '@helpers/axios';
import { JSON_LD } from './client';

/** Demande d'avenant (étudiant, convention signée) ou avenant saisi par le service des stages. */
const createStageAvenantService = async (data) => (await api.post('/api/stage_avenants', data, JSON_LD)).data;

/** Validation d'un avenant : ses modifications sont appliquées au dossier. */
const validateStageAvenantService = async (id) => (await api.post(`/api/stage_avenants/${id}/validate`)).data;

export { createStageAvenantService, validateStageAvenantService };
