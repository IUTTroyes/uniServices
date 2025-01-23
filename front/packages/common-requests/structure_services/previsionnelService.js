import api from '@helpers/axios';

const getSemestrePreviService = async (semestreId, anneeUnivId) => {
    const response = await api.get(`/api/previsionnels?anneeUniversitaire=${anneeUnivId}&semestre=${semestreId}`);
    return response.data.member;
}

const buildSemestrePreviService = async (previ) => {
    const groupedPrevi = {};

    previ.forEach(item => {
        const enseignementId = item.enseignement ? item.enseignement['@id'] : null;
        if (!groupedPrevi[enseignementId]) {
            groupedPrevi[enseignementId] = { ...item, personnel: [] };
        }

        const heuresMaquette = item.enseignement ? item.enseignement.heures.heures : {
            "CM": { "IUT": 0 },
            "TD": { "IUT": 0 },
            "TP": { "IUT": 0 },
            "Projet": { "IUT": 0 }
        };
        const heuresPrevi = item.heures.heures;

        groupedPrevi[enseignementId].heures = {
            "CM": {
                "Maquette": heuresMaquette.CM.IUT,
                "Previ": heuresPrevi.CM
            },
            "TD": {
                "Maquette": heuresMaquette.TD.IUT,
                "Previ": heuresPrevi.TD
            },
            "TP": {
                "Maquette": heuresMaquette.TP.IUT,
                "Previ": heuresPrevi.TP
            },
            "Projet": {
                "Maquette": heuresMaquette.Projet.IUT,
                "Previ": heuresPrevi.Projet
            }
        };
        groupedPrevi[enseignementId].personnel.push(item.personnel);
    });

    return Object.values(groupedPrevi);
}

const calcTotalHeures = (heures) => {
    let totalHeures = 0;
    if (heures && heures.heures) {
        for (const key in heures.heures) {
            if (heures.heures.hasOwnProperty(key) && typeof heures.heures[key] === 'number') {
                totalHeures += heures.heures[key];
            }
        }
    }
    return totalHeures;
}

export { getSemestrePreviService, buildSemestrePreviService, calcTotalHeures };
