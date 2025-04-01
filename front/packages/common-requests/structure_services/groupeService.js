import {onMounted, ref} from 'vue';
import api from '@helpers/axios';

const getGroupesService = async () => {
    const response = await api.get(`/api/structure_groupes`);
    return response.data.member;
}

const getSemestreGroupesService = async (semestreId, onlyActif = true) => {
    const response = await api.get(`/api/structure_groupes?semestre=${semestreId}&actif=${onlyActif}`);
    return response.data;
}

const getDepartementGroupesService = async (departementId, onlyActif = true) => {
    const response = await api.get(`/api/structure_groupes?departement=${departementId}&actif=${onlyActif}`);
    return response.data.member;
}

const getDiplomeGroupesService = async (diplomeId, onlyActif = true) => {
    const response = await api.get(`/api/structure_groupes?diplome=${diplomeId}&actif=${onlyActif}`);
    return response.data.member;
}

export { getGroupesService, getSemestreGroupesService, getDepartementGroupesService, getDiplomeGroupesService };
