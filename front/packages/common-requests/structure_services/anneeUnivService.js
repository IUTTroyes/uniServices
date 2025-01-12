import api from '@helpers/axios';

const getAllAnneesUniversitaires = async () => {
    const response = await api.get(`/api/structure_annee_universitaires`);
    return response.data['member'];
}

export { getAllAnneesUniversitaires };
