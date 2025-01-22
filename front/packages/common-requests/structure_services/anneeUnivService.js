import api from '@helpers/axios';

const getAllAnneesUniversitairesService = async () => {
    const response = await api.get(`/api/structure_annee_universitaires`);
    return response.data['member'];
}

const getAnneeUniversitaireService = async (id) => {
    const response = await api.get(`/api/structure_annee_universitaires/${id}`);
    return response.data;
}

const getCurrentAnneeUniversitaireService = async () => {
    const response = await api.get(`/api/structure_annee_universitaires?actif=true`);
    return response.data.member[0];
}

export { getAllAnneesUniversitairesService, getAnneeUniversitaireService, getCurrentAnneeUniversitaireService };
