import api from '@helpers/axios';

const getAllAnneesUniversitaires = async () => {
    const response = await api.get(`/api/structure_annee_universitaires`);
    return response.data['member'];
}

const getAnneeUniversitaire = async (id) => {
    const response = await api.get(`/api/structure_annee_universitaires/${id}`);
    return response.data;
}

const getCurrentAnneeUniversitaire = async () => {
    const response = await api.get(`/api/structure_annee_universitaires?actif=true`);
    return response.data.member[0];
}

export { getAllAnneesUniversitaires, getAnneeUniversitaire, getCurrentAnneeUniversitaire };
