import api from '@helpers/axios';

const getAllEnseignementService = async () => {
    const response = await api.get('/api/scol_enseignements');
    return response.data.member;
}

const getEnseignementSemestreService = async (semestreId) => {
    const response = await api.get(`api/scol_enseignements?semestre=${semestreId}`);
    return response.data.member;
}

const getEnseignementDepartementService = async (departementId) => {
    const response = await api.get(`api/scol_enseignements?departement=${departementId}`);
    return response.data.member;
}

const getEnseignementService = async (id) => {
    const response = await api.get(`api/scol_enseignements/${id}`);
    return response.data;
}

export { getAllEnseignementService, getEnseignementSemestreService, getEnseignementService, getEnseignementDepartementService };
