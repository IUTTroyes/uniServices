import api from '@helpers/axios';

const getAllDiplomesService = async () => {
    const response = await api.get('/api/diplomes');
    return response.data['member'];
}

const getAllDiplomesActifsService = async () => {
    const response = await api.get('/api/diplomes?actif=true');
    return response.data['member'];
}

const getDepartementDiplomesService = async (departementId) => {
    const response = await api.get(`/api/diplomes-par-departement/${departementId}`);
    return response.data['member'];
}

const getDepartementDiplomesActifsService = async (departementId) => {
    const response = await api.get(`/api/diplomes-par-departement/${departementId}?actif=true`);
    return response.data['member'];
}

export { getDepartementDiplomesService, getDepartementDiplomesActifsService };
