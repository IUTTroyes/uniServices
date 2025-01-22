import api from '@helpers/axios';

const getDiplomeAnneesService = async (diplomeId) => {
    const response = await api.get(`/api/annees-par-diplome/${diplomeId}`);
    return response.data['member'];
}

const getDiplomeAnneesActifsService = async (diplomeId) => {
    const response = await api.get(`/api/annees-par-diplome/${diplomeId}?actif=true`);
    return response.data['member'];
}

export { getDiplomeAnneesService };
