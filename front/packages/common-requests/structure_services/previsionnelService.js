import api from '@helpers/axios';

const getSemestrePreviService = async (semestreId, anneeUnivId) => {
    const response = await api.get(`/api/previsionnels_semestre?anneeUniversitaire=${anneeUnivId}&semestre=${semestreId}`);
    return response.data.member;
}

const getSemestreEnseignementPreviService = async (semestreId, enseignementId, anneeUnivId) => {
    const response = await api.get(`/api/previsionnels_enseignement?anneeUniversitaire=${anneeUnivId}&semestre=${semestreId}&enseignement=${enseignementId}`);
    return response.data.member;
}

const getAnneeUnivPreviService = async (departementId, anneeUnivId) => {
    const response = await api.get(`/api/previsionnels_all_personnels?anneeUniversitaire=${anneeUnivId}&departement=${departementId}`);
    return response.data.member;
}

const getPersonnelPreviService = async (personnelId, anneeUnivId) => {
    const response = await api.get(`/api/previsionnels_personnel?anneeUniversitaire=${anneeUnivId}&personnel=${personnelId}`);
    return response.data.member;
}

export { getSemestrePreviService, getSemestreEnseignementPreviService, getAnneeUnivPreviService };
