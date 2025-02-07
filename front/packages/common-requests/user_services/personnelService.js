import api from '@helpers/axios';

const getPersonnelsDepartementService = async (departementId) => {
    const response = await api.get(`/api/structure_departement_personnels/by_departement/${departementId}`);
    return response.data['member'];
}

export { getPersonnelsDepartementService };
