import api from '@helpers/axios'
import apiCall from '@helpers/apiCall'

const getStageTemplatesService = async (showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            ['/api/stage_convention_templates'],
            'Modèles de stage récupérés avec succès',
            'Erreur lors de la récupération des modèles',
            showToast
        );
        return response['member'] || response;
    } catch (error) {
        console.error('Erreur dans getStageTemplatesService:', error);
        throw error;
    }
};

const updateStageTemplateService = async (id, data, showToast = false) => {
    try {
        return await apiCall(
            api.patch,
            [`/api/stage_convention_templates/${id}`, data, { headers: { 'Content-Type': 'application/merge-patch+json' } }],
            'Modèle mis à jour avec succès',
            'Erreur lors de la mise à jour du modèle',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans updateStageTemplateService:', error);
        throw error;
    }
};

const createStageTemplateService = async (data, showToast = false) => {
    try {
        return await apiCall(
            api.post,
            ['/api/stage_convention_templates', data, { headers: { 'Content-Type': 'application/ld+json' } }],
            'Modèle créé avec succès',
            'Erreur lors de la création du modèle',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans createStageTemplateService:', error);
        throw error;
    }
};

export {
    getStageTemplatesService,
    updateStageTemplateService,
    createStageTemplateService
};
