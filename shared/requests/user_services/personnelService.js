import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getPersonnelsService = async (params, scope = '', showToast = false) => {
    try {
        // formater les filters
        if (params.filters) {
            if (params.filters.nom) {
                params['nom'] = params.filters.nom.value;
            }
            if (params.filters.prenom) {
                params['prenom'] = params.filters.prenom.value;
            }
            if (params.filters.statut) {
                params['statut'] = params.filters.statut.value?.value;
            }
            if (params.filters.numeroHarpege) {
                params['numeroHarpege'] = params.filters.numeroHarpege.value;
            }
            if (params.filters.mailUniv) {
                params['mailUniv'] = params.filters.mailUniv.value;
            }
        }
        const response = await apiCall(
            api.get,
            [`/api${scope}/personnels`, {params}],
            'Personnels récupérés avec succès',
            'Erreur lors de la récupération des personnels',
            showToast
        );
        return response.member;
    } catch (error) {
        console.error('Erreur dans getPersonnelsDepartementService:', error);
        throw error;
    }
}

export { getPersonnelsService };
