import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const getSemaineUniversitaireService = async (weekNumber, anneeUniversitaire, showToast = false) => {
    try {
        const response = await apiCall(
            api.get,
            [`/api/structure_calendriers?semaineReelle=${weekNumber}&anneeUniversitaire=${anneeUniversitaire}`],
            'Semaine universitaire récupérée avec succès',
            'Erreur lors de la récupération de la semaine universitaire',
            showToast
        );
        return response['member'];
    } catch (error) {
        console.error('Erreur dans getSemaineUniversitaireService:', error);
        throw error;
    }
}


export { getSemaineUniversitaireService };
