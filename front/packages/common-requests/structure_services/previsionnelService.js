import api from '@helpers/axios';
import createApiService from '@requests/apiService';
import apiCall from '@helpers/apiCall';

// Create API services for different endpoints
const previsionnelsService = createApiService('/api/previsionnels');
const previsionnelsSemestreService = createApiService('/api/previsionnels_semestre');
const previsionnelsSemestreTestService = createApiService('/api/previsionnels_semestre_test');
const previsionnelsEnseignementService = createApiService('/api/previsionnels_enseignement');
const previsionnelsAllPersonnelsService = createApiService('/api/previsionnels_all_personnels');
const previsionnelsPersonnelService = createApiService('/api/previsionnels_personnel');

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getSemestrePreviService = async (semestreId, anneeUnivId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/previsionnels_semestre?anneeUniversitaire=${anneeUnivId}&semestre=${semestreId}`],
            'Prévisionnel du semestre récupéré avec succès',
            'Erreur lors de la récupération du prévisionnel du semestre',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getSemestrePreviService:', error);
        throw error;
    }
}

const getSemestrePreviTestService = async (semestreId, anneeUnivId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/previsionnels_semestre_test?anneeUniversitaire=${anneeUnivId}&semestre=${semestreId}`],
            'Prévisionnel test du semestre récupéré avec succès',
            'Erreur lors de la récupération du prévisionnel test du semestre',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getSemestrePreviTestService:', error);
        throw error;
    }
}

const getSemestreEnseignementPreviService = async (semestreId, enseignementId, anneeUnivId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/previsionnels_enseignement?anneeUniversitaire=${anneeUnivId}&semestre=${semestreId}&enseignement=${enseignementId}`],
            'Prévisionnel de l\'enseignement récupéré avec succès',
            'Erreur lors de la récupération du prévisionnel de l\'enseignement',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getSemestreEnseignementPreviService:', error);
        throw error;
    }
}

const getAnneeUnivPreviService = async (departementId, anneeUnivId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/previsionnels_all_personnels?anneeUniversitaire=${anneeUnivId}&departement=${departementId}`],
            'Prévisionnels de l\'année universitaire récupérés avec succès',
            'Erreur lors de la récupération des prévisionnels de l\'année universitaire',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getAnneeUnivPreviService:', error);
        throw error;
    }
}

const getPersonnelPreviService = async (departementId, anneeUnivId, personnelId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/previsionnels_personnel?anneeUniversitaire=${anneeUnivId}&personnel=${personnelId}&departement=${departementId}`],
            'Prévisionnel du personnel récupéré avec succès',
            'Erreur lors de la récupération du prévisionnel du personnel',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getPersonnelPreviService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------


// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updatePreviEnseignementService = async (previId, enseignementId, showToast = true) => {
    try {
        const enseignementIri = `/api/scol_enseignements/${enseignementId}`;
        const data = { enseignement: enseignementIri };

        await apiCall(
            previsionnelsService.update,
            [previId, data],
            'L\'élément a été mis à jour avec succès',
            'Erreur lors de la mise à jour de l\'élément',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updatePreviEnseignementService:', error);
        throw error;
    }
}

const updatePreviPersonnelService = async (previId, personnelId, showToast = true) => {
    try {
        const personnelIri = `/api/personnels/${personnelId}`;
        const data = { personnel: personnelIri };

        await apiCall(
            previsionnelsService.update,
            [previId, data],
            'L\'élément a été mis à jour avec succès',
            'Erreur lors de la mise à jour de l\'élément',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updatePreviPersonnelService:', error);
        throw error;
    }
}

const updatePreviService = async (previId, data, showToast = true) => {
    try {
        await apiCall(
            previsionnelsService.update,
            [previId, data],
            'L\'élément a été mis à jour avec succès',
            'Erreur lors de la mise à jour de l\'élément',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updatePreviService:', error);
        throw error;
    }
}

export { getSemestrePreviService, getSemestreEnseignementPreviService, getAnneeUnivPreviService, updatePreviEnseignementService, updatePreviPersonnelService, updatePreviService, getSemestrePreviTestService, getPersonnelPreviService };
