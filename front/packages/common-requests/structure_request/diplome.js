import api from '@/axios';

const getDepartementDiplomes = async (params) => {
    const response = await api.get(`/api/diplomes-par-departement/${params.departementId}`);
    const diplomes = response.data['member'];
    // filter diplomes to keep only the active ones
    return diplomes.filter(diplome => diplome.actif === true);
}

export { getDepartementDiplomes };
