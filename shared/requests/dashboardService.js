import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getDashboardService = async (params = {}, showToast = false) => {
    return await apiCall(
        api.get,
        ['/api/dashboard', { params }],
        'Dashboard récupéré avec succès',
        'Erreur lors du chargement du dashboard',
        showToast,
    );
};

const getDashboardWidgetDataService = async (url, params = {}, showToast = false) => {
    return await apiCall(
        api.get,
        [url, { params }],
        'Données du widget récupérées avec succès',
        'Erreur lors du chargement des données du widget',
        showToast,
    );
};

const getWidgetsCatalogService = async (params = {}, showToast = false) => {
    return await apiCall(
        api.get,
        ['/api/widgets/catalog', { params }],
        'Catalogue des widgets récupéré avec succès',
        'Erreur lors du chargement du catalogue des widgets',
        showToast,
    );
};

const getWidgetDataByCodeService = async (code, params = {}, showToast = false) => {
    return await apiCall(
        api.get,
        [`/api/widgets/${code}/data`, { params }],
        'Données du widget récupérées avec succès',
        'Erreur lors du chargement des données du widget',
        showToast,
    );
};

const patchDashboardWidgetLayoutService = async (widgetKey, payload, params = {}, showToast = false) => {
    return await apiCall(
        api.patch,
        [`/api/dashboard/widgets/${widgetKey}/layout`, payload, {
            params,
            headers: { 'Content-Type': 'application/merge-patch+json' },
        }],
        'Préférences du widget sauvegardées',
        'Erreur lors de la sauvegarde des préférences du widget',
        showToast,
    );
};

export {
    getDashboardService,
    getDashboardWidgetDataService,
    getWidgetsCatalogService,
    getWidgetDataByCodeService,
    patchDashboardWidgetLayoutService,
};
