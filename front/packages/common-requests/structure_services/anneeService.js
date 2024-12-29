import api from '@helpers/axios';

const getServiceDiplomeAnnees = async (diplomeId) => {
    const response = await api.get(`/api/annees-par-diplome/${diplomeId}`);
    return response.data['member'];
}

const getServiceDiplomeAnneesActifs = async (diplomeId) => {
    const response = await api.get(`/api/annees-par-diplome/${diplomeId}?actif=true`);
    return response.data['member'];
}

export { getServiceDiplomeAnnees };
