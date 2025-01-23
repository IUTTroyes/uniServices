import api from '@helpers/axios';

const getSemestrePreviService = async (semestreId, anneeUnivId) => {
    const response = await api.get(`/api/previsionnels?anneeUniversitaire=${anneeUnivId}&semestre=${semestreId}`);
    return response.data.member;
}

const buildSemestrePreviService = async (previ) => {
    const groupedPrevi = {};

    previ.forEach(item => {
        if (!item.enseignement) {
            return; // Ignorer les éléments sans enseignement
        }

        const enseignementId = item.enseignement['@id'];
        if (!groupedPrevi[enseignementId]) {
            groupedPrevi[enseignementId] = { ...item, personnel: [] };
        }

        groupedPrevi[enseignementId].personnel.push(item.personnel);
    });

    return Object.values(groupedPrevi);
}

export { getSemestrePreviService, buildSemestrePreviService };
