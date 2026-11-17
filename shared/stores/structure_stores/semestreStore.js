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

  const getSemestresByDiplome = async (diplomeId, anneeUniversitaire = null, scope) => {
    try {
      const params = {
        diplome: diplomeId,
        anneeUniversitaire
      }
        return await getSemestresService(params, scope);
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const getSemestresByDepartement = async (departementId, anneeUniversitaire = null, scope = "") => {
    try {
      const params = {
        departement: departementId,
        anneeUniversitaire
      }
      semestres.value = await getSemestresService(params, scope);
      return semestres.value;
    } catch (error) {
      console.error('Error fetching user:', error);
    }
  };

  const setSelectedSemestre = (semestreData) => {
    semestre.value = semestreData;
  }

  return {
    getSemestre,
    getSemestres,
    getSemestresByDepartement,
    getSemestresByDiplome,
    setSelectedSemestre,
    semestre,
    semestres
  };
})
