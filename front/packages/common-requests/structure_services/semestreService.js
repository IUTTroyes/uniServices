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

const getDepartementSemestresActifsService = async (departementId) => {
    const diplomes = ref([]);
    const annees = ref([]);
    const semestresFc = ref([]);
    const semestresFi = ref([]);
    const diplomeStore = useDiplomeStore();

    diplomes.value = await diplomeStore.getDepartementDiplomesActifs(departementId);

    annees.value = diplomes.value.map(diplome => diplome.annees);
    annees.value.forEach(annee => {
        annee.forEach(a => {
            if (a.opt.alternance) {
                a.structureSemestres.forEach(semestre => {
                    if (semestre.actif) {
                        semestresFc.value.push(semestre);
                    }
                });
            } else {
                a.structureSemestres.forEach(semestre => {
                    if (semestre.actif) {
                        semestresFi.value.push(semestre);
                    }
                });
            }
        });
    });
    return {
        semestresFc: semestresFc.value,
        semestresFi: semestresFi.value
    };
}

const getDepartementSemestresService = async (departementId, onlyActif) => {
    const response = await api.get(`/api/structure_semestres?departement=${departementId}&actif=${onlyActif}`);
    return response.data.member;
}

export { getSemestresService, getSemestreService, getDepartementSemestresService, getDepartementSemestresActifsService };
