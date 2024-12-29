import api from '@helpers/axios';

const getServiceAllDiplomes = async () => {
    const response = await api.get('/api/diplomes');
    return response.data['member'];
}

const getServiceAllDiplomesActifs = async () => {
    const response = await api.get('/api/diplomes?actif=true');
    return response.data['member'];
}

const getServiceDepartementDiplomes = async (departementId) => {
    const response = await api.get(`/api/diplomes-par-departement/${departementId}`);
    return response.data['member'];
}

const getServiceDepartementDiplomesActifs = async (departementId) => {
    const response = await api.get(`/api/diplomes-par-departement/${departementId}?actif=true`);
    return response.data['member'];
}

export { getServiceDepartementDiplomes, getServiceDepartementDiplomesActifs };
