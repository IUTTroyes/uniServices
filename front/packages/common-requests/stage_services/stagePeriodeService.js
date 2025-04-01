import api from '@helpers/axios';

// récupérer les périodes de state d'une année
const getPeriodeStageSemestreService = async (semestreId) => {
    const response = await api.get(`/api/stage_periodes/?semestreProgramme=${semestreId}`);
    return response.data['member'];
}

const getPeriodeStageService = async (periodeId) => {
    const response = await api.get(`/api/stage_periodes/${periodeId}`);
    return response.data;
}

export { getPeriodeStageService, getPeriodeStageSemestreService };
