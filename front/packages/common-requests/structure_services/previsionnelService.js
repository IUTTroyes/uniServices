import api from '@helpers/axios';

const getSemestrePreviService = async (semestreId, anneeUnivId) => {
    const response = await api.get(`/api/previsionnels_semestre?anneeUniversitaire=${anneeUnivId}&semestre=${semestreId}`);
    return response.data.member;
}

const getSemestreEnseignementPreviService = async (semestreId, enseignementId, anneeUnivId) => {
    const response = await api.get(`/api/previsionnels_enseignement?anneeUniversitaire=${anneeUnivId}&semestre=${semestreId}&enseignement=${enseignementId}`);
    return response.data.member;
}

const buildSemestreMatierePreviService = async (previ) => {
    previ.forEach(item => {
        item.heuresGroupes = {
            "CM": {
                "NbH/Gr": item.heures.heures.CM,
                "NbGr": item.groupes.groupes.CM,
                "NbSeance/Gr": item.heures.heures.CM*item.groupes.groupes.CM,
                "NbHAttendu": item.enseignement.heures.heures.CM.IUT
            },
            "TD": {
                "NbH/Gr": item.heures.heures.TD,
                "NbGr": item.groupes.groupes.TD,
                "NbSeance/Gr": item.heures.heures.TD*item.groupes.groupes.TD,
                "NbHAttendu": item.enseignement.heures.heures.TD.IUT
            },
            "TP": {
                "NbH/Gr": item.heures.heures.TP,
                "NbGr": item.groupes.groupes.TP,
                "NbSeance/Gr": item.heures.heures.TP*item.groupes.groupes.TP,
                "NbHAttendu": item.enseignement.heures.heures.TP.IUT
            },
        }
    });
    return previ;
}

export { getSemestrePreviService, getSemestreEnseignementPreviService, buildSemestreMatierePreviService };
