<template>
  <div class="fiches-heures-create-page p-4">
    <h1 class="text-2xl font-bold mb-4">Nouvelle Fiche d'Heures</h1>
    <FicheHeureFormBiats :isEditMode="false" @save="handleSaveFicheHeure" @cancel="handleCancel" />
  </div>
</template>

<script setup>
import FicheHeureFormBiats from '@/components/FicheHeures/FicheHeureFormBiats.vue'; // Adjust path
import { useFicheHeureStore } from '@/stores/ficheHeureStore'; // Adjust path
import { useRouter } from 'vue-router';

const ficheHeureStore = useFicheHeureStore();
const router = useRouter();

const handleSaveFicheHeure = async (formData) => {
  try {
    // Ensure 'heures' is stringified if backend expects JSON string, or sent as array if backend handles it.
    // The store action should ideally handle this transformation if needed.
    await ficheHeureStore.createFicheHeure(formData);
    // Toast for success is handled by apiCall used in store action
    router.push({ name: 'MesFichesHeures' });
  } catch (error) {
    console.error('Erreur création fiche (page):', error);
    // Toast for error also handled by apiCall
  }
};

const handleCancel = () => {
  router.push({ name: 'MesFichesHeures' });
};
</script>
