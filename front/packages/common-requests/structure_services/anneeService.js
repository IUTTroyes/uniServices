import api from '@helpers/axios';

const getDiplomeAnneesService = async (diplomeId) => {
    const response = await api.get(`/api/annees-par-diplome/${diplomeId}`);
    return response.data['member'];
}

const getDiplomeAnneesActifsService = async (diplomeId) => {
    const response = await api.get(`/api/annees-par-diplome/${diplomeId}?actif=true`);
    return response.data['member'];
}

const getDepartementAnneesService = async (departementId, onlyActif) => {
    const response = await api.get(`/api/structure_annees?departement=${departementId}&actif=${onlyActif}`);
    return response.data['member'];
}

export { getDiplomeAnneesService, getDepartementAnneesService };
