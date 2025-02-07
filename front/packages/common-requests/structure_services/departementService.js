import api from '@helpers/axios';

const getAllDepartementsService = async () => {
    const response = await api.get('/api/structure_departements');
    return response.data;
}

const getDepartementService = async (departementId) => {
    const response = await api.get(`/api/structure_departements/${departementId}`);
    return response.data;
}

const changeDepartementActifService = async (departementId) => {
    const response = await api.post(`/api/structure_departement_personnels/${departementId}/change_departement`, {departementId}, {
        headers: {
            'Content-Type': 'application/ld+json'
        }
    });
    return response.data;
}

export { getAllDepartementsService, getDepartementService, changeDepartementActifService };
