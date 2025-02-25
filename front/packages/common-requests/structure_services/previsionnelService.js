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


// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updatePreviEnseignementService = async (previId, enseignementId) => {
    try {
        const enseignementIri = `/api/scol_enseignements/${enseignementId}`;
        const response = await api.patch(`/api/previsionnels/${previId}`, { enseignement: enseignementIri }, {
            headers: {
                'Content-Type': 'application/merge-patch+json'
            }
        });
    } catch (error) {
        console.log(error);
    }
}
const updatePreviPersonnelService = async (previId, personnelId) => {
    try {
        const personnelIri = `/api/personnels/${personnelId}`;
        const response = await api.patch(`/api/previsionnels/${previId}`, { personnel: personnelIri }, {
            headers: {
                'Content-Type': 'application/merge-patch+json'
            }
        });
    } catch (error) {
        console.log(error);
    }
}

export { getSemestrePreviService, getSemestreEnseignementPreviService, getAnneeUnivPreviService, updatePreviEnseignementService, updatePreviPersonnelService };
