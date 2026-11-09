/** Résultats : statistiques agrégées et exports (Excel, PDF). */
import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';
import { telecharger } from './client';

const getAnalyticsService = async (surveyUuid, showToast = false) => {
    try {
        return await apiCall(
            api.get,
            [`/api/questionnaires/${surveyUuid}/analytics`],
            'Statistiques du questionnaire récupérées avec succès',
            'Erreur lors de la récupération des statistiques du questionnaire',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getAnalyticsService:', error);
        throw error;
    }
}

/** Nom de fichier annoncé par le serveur (Content-Disposition), sinon celui proposé. */
const nomFichier = (response, defaut) => {
    const entete = response.headers?.['content-disposition'] || '';
    const trouve = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(entete);
    return trouve ? decodeURIComponent(trouve[1]) : defaut;
};

/** Export Excel des statistiques (refusé en 409 sous le seuil d'anonymat). */
/** Export Excel, éventuellement limité à des sections (restitution individuelle). */
const exportExcelService = async (surveyUuid, sections = []) => {
    const params = sections.length ? { sections: sections.join(',') } : {};
    const response = await api.get(`/api/questionnaires/${surveyUuid}/export-excel`, { responseType: 'blob', params });
    telecharger(new Blob([response.data]), nomFichier(response, `Statistiques_${surveyUuid}.xlsx`));
};

/** Export PDF : les graphiques rendus côté client sont transmis en images ({ cle: 'data:image/png;base64,…' }). */
const exportPdfService = async (surveyUuid, charts = {}, sections = []) => {
    const params = sections.length ? { sections: sections.join(',') } : {};
    const response = await api.post(`/api/questionnaires/${surveyUuid}/export-pdf`, { charts }, { responseType: 'blob', params });
    telecharger(new Blob([response.data], { type: 'application/pdf' }), nomFichier(response, `Statistiques_${surveyUuid}.pdf`));
};

export {
    getAnalyticsService,
    exportExcelService,
    exportPdfService
};
