import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@helpers/axios';
import { getSemestreService, getSemestresService, getDepartementSemestresService, getDiplomeSemestresService } from "@requests";

export const useSemestreStore = defineStore('semestre', () => {

  const semestre = ref({});
  const semestres = ref({});

  const getSemestre = async (semestreId) => {
    try {
      semestre.value = await getSemestreService(semestreId);
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getSemestres = async () => {
    try {
      semestres.value = await getSemestresService();
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getSemestresByDiplome = async (diplomeId, onlyActif = true) => {
    try {
      semestres.value = await getDiplomeSemestresService(diplomeId, onlyActif);
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getSemestresByDepartement = async (departementId, onlyActif, scope = "") => {
    try {
      semestres.value = await getDepartementSemestresService(departementId, onlyActif, scope);
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  return {
    getSemestre,
    getSemestres,
    getSemestresByDepartement,
    getSemestresByDiplome,
    semestre,
    semestres
  };
})
