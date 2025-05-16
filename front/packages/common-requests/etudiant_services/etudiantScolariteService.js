import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getEtudiantsScolaritesDepartementService = async (departement, anneeUniv, limit, page, filters, showToast = false) => {
    try {
        const params = {
            departement,
            anneeUniversitaire: anneeUniv,
            page,
            itemsPerPage: limit,
        };

        if (filters.nom.value) {
            params['nom'] = filters.nom.value;
        }
        if (filters.prenom.value) {
            params['prenom'] = filters.prenom.value;
        }
        if (filters.mailUniv.value) {
            params['mailUniv'] = filters.mailUniv.value;
        }
        if (filters.annee.value) {
            params['annee'] = filters.annee.value;
        }

        const response = await apiCall(
            api.get,
            [`/api/etudiant_scolarites`, { params }],
            'Scolarités des étudiants récupérées avec succès',
            'Erreur lors de la récupération des scolarités des étudiants',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getEtudiantsScolaritesDepartementService:', error);
        throw error;
    }
}

const getEtudiantScolaritesService = async (etudiantId, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/etudiant_scolarites?etudiant=${etudiantId}`],
            'Scolarités de l\'étudiant récupérées avec succès',
            'Erreur lors de la récupération des scolarités de l\'étudiant',
            showToast
        );
        return response;
    } catch (error) {
        console.error('Erreur dans getEtudiantScolaritesService:', error);
        throw error;
    }
}

export { getEtudiantsScolaritesDepartementService, getEtudiantScolaritesService };
