import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

// ----------------------------------------------
// ------------------- GET ----------------------
// ----------------------------------------------

const getEtudiantsScolariteService = async (params, scope = '', showToast = false) => {
    try {

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

        return await apiCall(
            api.get,
            [`/api${scope}/etudiant_scolarites`, {params}],
            'Scolarités des étudiants récupérées avec succès',
            'Erreur lors de la récupération des scolarités des étudiants',
            showToast
        );
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

// const getEtudiantScolaritesService = async (etudiantId, actif, showToast = false) => {
//     try {
//         const response = await apiCall(
//             api.get,
//             [`/api/etudiant_scolarites?etudiant=${etudiantId}&actif=${actif}`],
//             'Scolarités de l\'étudiant récupérées avec succès',
//             'Erreur lors de la récupération des scolarités de l\'étudiant',
//             showToast
//         );
//         return response.member;
//     } catch (error) {
//         console.error('Erreur dans getEtudiantScolaritesService:', error);
//         throw error;
//     }
// }

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

const updateEtudiantScolariteSemestreService = async (scolariteSemestreId, updatedData, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/etudiant_scolarite_semestres/${scolariteSemestreId}`, updatedData,  {
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

const updateEtudiantScolariteSemestreGroupes = async (scolariteSemestreId, newGroup, showToast = false) => {
    try {
        console.log(scolariteSemestreId);

        // Récupérer les données actuelles du scolariteSemestre
        const currentData = await api.get(`/api/etudiant_scolarite_semestres/${scolariteSemestreId}`);
        const existingGroups = currentData.data.groupes || []; // Initialiser comme tableau vide si null

        if (!newGroup['@id']) {
            // construire l'iri
            newGroup['@id'] = `/api/structure_groupes/${newGroup.id}`;
        }

        // Filtrer les groupes pour exclure ceux du même type que le nouveau groupe
        const updatedGroups = [
            ...existingGroups.filter(group => group.type !== newGroup.type).map(group => group['@id']),
            newGroup['@id']
        ];

        console.log(updatedGroups);

        // Envoyer la mise à jour
        return await apiCall(
            api.patch,
            [`/api/etudiant_scolarite_semestres/${scolariteSemestreId}`, { groupes: updatedGroups }, {
                headers: {
                    'Content-Type': 'application/merge-patch+json',
                },
            }],
            'Groupe mis à jour avec succès',
            'Erreur lors de la mise à jour du groupe',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateScolariteSemestreGroups:', error);
        throw error;
    }
};

// ----------------------------------------------
// ------------------- DELETE -------------------
// ----------------------------------------------

export { getEtudiantsScolariteService, getEtudiantScolariteService, getEtudiantScolaritesService, updateEtudiantScolariteService, updateEtudiantScolariteSemestreService, updateEtudiantScolariteSemestreGroupes };
