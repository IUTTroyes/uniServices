import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getDepartementsPersonnelService } from '@requests'

export const useDepartementStore = defineStore('departement', () => {
  const departements = ref({});
  const departementDefaut = ref({});
  const isLoaded = ref(false);

  const getDepartementsPersonnel = async (personnelId, force = false) => {
    if (isLoaded.value && !force) {
      return departements.value;
    }

    try {
      departements.value = await getDepartementsPersonnelService(personnelId);
      isLoaded.value = true;
      return departements.value;
    } catch (error) {
      console.error('Error fetching departements:', error);
      return [];
    }
  }

  return {
    departements,
    departementDefaut,
    getDepartementsPersonnel,
    isLoaded
  };
})
