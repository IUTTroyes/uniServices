import api from '@helpers/axios';

const getSemestrePreviService = async (semestreId) => {
    const response = await api.get(`/api/previsionnels?semestre=${semestreId}`);
    return response.data.member;
}

export { getSemestrePreviService };
