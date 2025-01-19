import api from '@helpers/axios';

const getServiceAllAnneesUniversitaires = async () => {
    const response = await api.get(`/api/structure_annee_universitaires`);
    return response.data['member'];
}

const getServiceAnneeUniversitaire = async (id) => {
    const response = await api.get(`/api/structure_annee_universitaires/${id}`);
    return response.data;
}

const getServiceCurrentAnneeUniversitaire = async () => {
    const response = await api.get(`/api/structure_annee_universitaires?actif=true`);
    return response.data.member[0];
}

export { getServiceAllAnneesUniversitaires, getServiceAnneeUniversitaire, getServiceCurrentAnneeUniversitaire };
