import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getPersonnelEdtEventsService = async (personnel, anneeUniv, departement, showToast = false) => {
    try {
        const params = {
            departement,
            anneeUniversitaire: anneeUniv,
        };

        return await apiCall(
            api.get,
            [`/api/edt_events`, {params}],
            'Événéments de l\'emploi du temps du personnel récupérés avec succès',
            'Erreur lors de la récupération des événements de l\'emploi du temps du personnel',
            showToast
        );
    } catch (error) {
        console.error('Erreur dans getPersonnelEdtEventsService:', error);
        throw error;
    }
}

const getSemestreEdtWeekEventsService = async (semaine, semestre, anneeUniv, departement, showToast = false) => {
    try {
        const params = {
            semaineFormation: semaine,
            semestre: semestre,
            anneeUniversitaire: anneeUniv,
            departement: departement,
        };

        const response = await apiCall(
            api.get,
            [`/api/edt_events`, {params}],
            'Événéments de l\'emploi du temps du personnel récupérés avec succès',
            'Erreur lors de la récupération des événements de l\'emploi du temps du personnel',
            showToast
        );

        return response.member;
    } catch (error) {
        console.error('Erreur dans getPersonnelEdtEventsService:', error);
        throw error;
    }
}

const getEdtWeekEventsService = async (params, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/edt_events`, { params }],
            'Événéments de l\'emploi du temps du personnel récupérés avec succès',
            'Erreur lors de la récupération des événements de l\'emploi du temps du personnel',
            showToast
        );

        return response.member;
    } catch (error) {
        console.error('Erreur dans getPersonnelEdtEventsService:', error);
        throw error;
    }
}

export { getPersonnelEdtEventsService, getEdtWeekEventsService, getSemestreEdtWeekEventsService };
