import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getPersonnelsService = async (params, scope = '', showToast = false) => {
    try {
        const apiParams = {...params};

        // formater les filters
        if (apiParams.filters) {
            if (apiParams.filters.nom) {
                apiParams['nom'] = apiParams.filters.nom.value;
            }
            if (apiParams.filters.prenom) {
                apiParams['prenom'] = apiParams.filters.prenom.value;
            }
            if (apiParams.filters.statut) {
                apiParams['statut'] = apiParams.filters.statut.value?.value;
            }
            if (apiParams.filters.numeroHarpege) {
                apiParams['numeroHarpege'] = apiParams.filters.numeroHarpege.value;
            }
            if (apiParams.filters.mailUniv) {
                apiParams['mailUniv'] = apiParams.filters.mailUniv.value;
            }
        }

        if (Array.isArray(apiParams.sort) && apiParams.sort.length > 0) {
            apiParams.order = apiParams.sort.reduce((acc, sortItem) => {
                if (!sortItem?.field || !sortItem?.order) return acc;

                acc[sortItem.field] = sortItem.order > 0 ? 'asc' : 'desc';
                return acc;
            }, {});
        }

        delete apiParams.filters;
        delete apiParams.sort;

        const response = await apiCall(
            api.get,
            [`/api${scope}/personnels`, {params: apiParams}],
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
