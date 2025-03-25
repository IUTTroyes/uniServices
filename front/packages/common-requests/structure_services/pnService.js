import api from '@helpers/axios';
const getAllPns = async () => {
    const response = await api.get('/api/structure_pns');
    return response.data.member;
}

const getPnsDiplome = async (diplomeId) => {
    const response = await api.get(`/api/structure_pns?diplome=${diplomeId}`);
    return response.data.member;
}

export { getAllPns, getPnsDiplome };
