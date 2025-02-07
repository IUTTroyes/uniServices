import {onMounted, ref} from 'vue';
import api from '@helpers/axios';
import { useDiplomeStore } from '@stores';

const getSemestresService = async () => {
    const response = await api.get(`/api/structure_semestres`);
    return response.data.member;
}

const getSemestreService = async (semestreId) => {
    const response = await api.get(`/api/structure_semestres/${semestreId}`);
    return response.data;
}

const getDepartementSemestresService = async (departementId, onlyActif) => {
    const response = await api.get(`/api/structure_semestres?departement=${departementId}&actif=${onlyActif}`);
    return response.data.member;
}

const getDiplomeSemestresService = async (diplomeId, onlyActif) => {
    const response = await api.get(`/api/structure_semestres?diplome=${diplomeId}&actif=${onlyActif}`);
    return response.data.member;
}

export { getSemestresService, getSemestreService, getDepartementSemestresService, getDiplomeSemestresService };
