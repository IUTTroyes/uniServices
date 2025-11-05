<script setup>
import {ref, onMounted} from 'vue';
import {getAnneesService} from '@requests';
import { useUsersStore } from '@stores';
import { SimpleSkeleton } from '@components';

const usersStore = useUsersStore();
const selectedAnneeUniversitaire = ref(null);
const departementId = ref(null);
const annees = ref([]);
const isLoadingAnnees = ref(false);

onMounted(() => {
  departementId.value = usersStore.departementDefaut.id;
  selectedAnneeUniversitaire.value = JSON.parse(localStorage.getItem('selectedAnneeUniv'))
  getAnnees();
});

const getAnnees = async () => {
  isLoadingAnnees.value = true;
  try {
    const params = {
      departement: departementId.value,
      actif: true,
    };
    annees.value = await getAnneesService(params);
  } catch (error) {
    console.error('Error fetching annees universitaires:', error);
  } finally {
    console.log('annees:', annees.value);
    isLoadingAnnees.value = false;
  }
};
</script>

<template>

</template>

<style scoped>

</style>
