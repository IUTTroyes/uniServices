import api from '@helpers/axios';

const getSemestrePreviService = async (semestreId, anneeUnivId) => {
    const response = await api.get(`/api/previsionnels?anneeUniversitaire=${anneeUnivId}&semestre=${semestreId}`);
    return response.data.member;
}

const getMatierePreviService = async (matiereId, anneeUnivId) => {
    const response = await api.get(`/api/previsionnels?anneeUniversitaire=${anneeUnivId}&enseignement=${matiereId}`);
    return response.data.member;
}

const calcTotalHeuresByType = (previGrouped, type) => {
    const totalMaq = previGrouped.reduce((acc, previ) => acc + previ.heures[type].Maquette, 0);
    const totalPrev = previGrouped.reduce((acc, previ) => acc + previ.heures[type].Previ, 0);
    const totalDiff = previGrouped.reduce((acc, previ) => acc + previ.heures[type].Diff, 0);
    return [totalMaq, totalPrev, totalDiff];
};

const calcTotalHeures = (heures) => {
    let totalHeures = 0;
    if (heures) {
        for (const key in heures) {
            if (heures.hasOwnProperty(key) && typeof heures[key] === 'number') {
                totalHeures += heures[key];
            }
        }
    }
    return totalHeures;
}

const buildSemestrePreviService = async (previ) => {
    const groupedPrevi = {};
    previ.forEach(item => {
        const enseignementId = item.enseignement && item.enseignement['@id'] ? item.enseignement['@id'] : `unknown_${item.id}`;
        if (!groupedPrevi[enseignementId]) {
            groupedPrevi[enseignementId] = { ...item, personnel: [] };
        }
        const heuresMaquette = (item.enseignement && item.enseignement.heures && item.enseignement.heures.heures) || {
            "CM": { "IUT": 0 },
            "TD": { "IUT": 0 },
            "TP": { "IUT": 0 },
            "Projet": { "IUT": 0 }
        };
        // retirer le tableau IUT pour avoir les heures directement
        for (const key in heuresMaquette) {
            if (heuresMaquette.hasOwnProperty(key)) {
                heuresMaquette[key] = heuresMaquette[key].IUT;
            }
        }
        const heuresPrevi = (item.heures && item.heures.heures) || {
            "CM": 0,
            "TD": 0,
            "TP": 0,
            "Projet": 0
        };

        const totalHeuresPrevi = calcTotalHeures(heuresPrevi);
        const totalHeuresMaquette = calcTotalHeures(heuresMaquette);

        groupedPrevi[enseignementId].heures = {
            "CM": {
                "Maquette": heuresMaquette.CM,
                "Previ": heuresPrevi.CM,
                "Diff": (typeof heuresPrevi.CM === 'number' && typeof heuresMaquette.CM === 'number')
                    ? heuresPrevi.CM - heuresMaquette.CM
                    : 0
            },
            "TD": {
                "Maquette": heuresMaquette.TD,
                "Previ": heuresPrevi.TD,
                "Diff": (typeof heuresPrevi.TD === 'number' && typeof heuresMaquette.TD === 'number')
                    ? heuresPrevi.TD - heuresMaquette.TD
                    : 0
            },
            "TP": {
                "Maquette": heuresMaquette.TP,
                "Previ": heuresPrevi.TP,
                "Diff": (typeof heuresPrevi.TP === 'number' && typeof heuresMaquette.TP === 'number')
                    ? heuresPrevi.TP - heuresMaquette.TP
                    : 0
            },
            "Projet": {
                "Maquette": heuresMaquette.Projet,
                "Previ": heuresPrevi.Projet,
                "Diff": (typeof heuresPrevi.Projet === 'number' && typeof heuresMaquette.Projet === 'number')
                    ? heuresPrevi.Projet - heuresMaquette.Projet
                    : 0
            },
            "Total": {
                "Maquette": totalHeuresMaquette,
                "Previ": totalHeuresPrevi,
                "Diff": totalHeuresPrevi - totalHeuresMaquette
            }
        };

        if (item.personnel) {
            if (Array.isArray(item.personnel)) {
                groupedPrevi[enseignementId].personnel.push(...item.personnel);
            } else {
                groupedPrevi[enseignementId].personnel.push(item.personnel);
            }
        }
    });
    const previGrouped = Object.values(groupedPrevi);
    const totalCM = calcTotalHeuresByType(previGrouped, 'CM');
    const totalTD = calcTotalHeuresByType(previGrouped, 'TD');
    const totalTP = calcTotalHeuresByType(previGrouped, 'TP');
    const totalProjet = calcTotalHeuresByType(previGrouped, 'Projet');
    const totalTotal = calcTotalHeuresByType(previGrouped, 'Total');

    return { previGrouped, totalCM, totalTD, totalTP, totalProjet, totalTotal };
};

export { getSemestrePreviService, buildSemestrePreviService, calcTotalHeures };
