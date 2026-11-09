/** Données du cœur utilisées par l'éditeur (statuts, semestres, personnels, étudiants d'un semestre). */
import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getStatutsService = async (showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/statuts`],
            'Statuts récupérés avec succès',
            'Erreur lors de la récupération des statuts',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getStatutsService:', error);
        throw error;
    }
}

const getMiniSemestresService = async (showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/mini/structure_semestres`],
            'Semestres récupérés avec succès',
            'Erreur lors de la récupération des semestres',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getMiniSemestresService:', error);
        throw error;
    }
}

const getPersonnelsDepartementService = async (departement = null, showToast = false) => {
    try {
        const params = { pagination: false };
        const deptId = typeof departement === 'object' && departement !== null ? departement.id : departement;
        if (deptId) {
            params.departement = deptId;
        }
        return await apiCall(
            api.get,
            [`/api/personnels`, { params }],
            'Personnels récupérés avec succès',
            'Erreur lors de la récupération des personnels',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getPersonnelsDepartementService:', error);
        throw error;
    }
}

const getEtudiantsSemestreService = async (semestreId, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/manage-groupes/etudiant_scolarite_semestres`, { params: { semestre: semestreId, pagination: false } }],
            'Étudiants du semestre récupérés avec succès',
            'Erreur lors de la récupération des étudiants',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getEtudiantsSemestreService:', error);
        throw error;
    }
}

export {
    getStatutsService,
    getMiniSemestresService,
    getPersonnelsDepartementService,
    getEtudiantsSemestreService
};
