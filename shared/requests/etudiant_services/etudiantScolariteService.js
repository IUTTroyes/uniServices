import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const buildEtudiantsScolariteQueryParams = (params = {}) => {
    const queryParams = { ...params };
    const filters = params.filters ?? {};

    delete queryParams.filters;

    const filterValues = {
        nom: filters.nom?.value,
        prenom: filters.prenom?.value,
        mailUniv: filters.mailUniv?.value,
        annee: filters.annee?.value,
    };

    Object.entries(filterValues).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            queryParams[key] = value;
        }
    });

    return queryParams;
};

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getEtudiantsScolariteService = async (params, scope = '', showToast = false) => {
    try {
        // formater les filters
        if (params.filters) {
            if (params.filters.nom) {
                params['nom'] = params.filters.nom.value;
            }
            if (params.filters.prenom) {
                params['prenom'] = params.filters.prenom.value;
            }
            if (params.filters.mailUniv) {
                params['mailUniv'] = params.filters.mailUniv.value;
            }
            if (params.filters.annee) {
                params['annee'] = params.filters.annee.value;
            }
        }
        const response = await apiCall(
            api.get,
            [`/api${scope}/etudiant_scolarites`, { params }],
            'Étudiants récupérés avec succès',
            'Erreur lors de la récupération des étudiants',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getEtudiantScolariteService:', error);
        throw error;
    }
}

const getEtudiantScolariteService = async (id, params, scope='', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/etudiant_scolarites/${id}`, { params }],
            'Scolarités de l\'étudiant récupérées avec succès',
            'Erreur lors de la récupération des scolarités de l\'étudiant',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getEtudiantScolaritesService:', error);
        throw error;
    }
}

const getEtudiantScolaritesService = async (params, scope='', showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api${scope}/etudiant_scolarites`, { params }],
            'Scolarités de l\'étudiant récupérées avec succès',
            'Erreur lors de la récupération des scolarités de l\'étudiant',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getEtudiantScolaritesService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- CREATE -------------------
// ----------------------------------------------


// ----------------------------------------------
// ------------------- UPDATE -------------------
// ----------------------------------------------

const updateEtudiantScolariteService = async (scolariteId, updatedData, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/etudiant_scolarites/${scolariteId}`, updatedData,  {
                headers: {
                    'Content-Type': 'application/merge-patch+json',
                },
            }],
            'Scolarité de l\'étudiant mise à jour avec succès',
            'Erreur lors de la mise à jour de la scolarité de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateEtudiantScolariteService:', error);
        throw error;
    }
}

const demissionEtudiantScolariteService = async (scolariteId, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/etudiant_scolarites/demission/${scolariteId}`,  {
                headers: {
                    'Content-Type': 'application/merge-patch+json',
                },
            }],
            'Démission de l\'étudiant appliquée avec succès',
            'Erreur lors de la démission de l\'étudiant',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans demissionEtudiantScolariteService:', error);
        throw error;
    }
}

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { getEtudiantsScolariteService, getEtudiantScolariteService, getEtudiantScolaritesService, updateEtudiantScolariteService, demissionEtudiantScolariteService };
