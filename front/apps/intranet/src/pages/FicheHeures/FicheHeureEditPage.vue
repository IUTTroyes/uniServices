<template>
  <div class="fiches-heures-edit-page p-4">
    <h1 class="text-2xl font-bold mb-4">Modifier Fiche d'Heures</h1>
    <div v-if="ficheHeureStore.isLoadingCurrentFicheHeure && !ficheHeureData">Chargement...</div>
    <div v-else-if="!ficheHeureData && !ficheHeureStore.isLoadingCurrentFicheHeure">Fiche non trouvée ou erreur de chargement.</div>
    <FicheHeureFormBiats v-else :isEditMode="true" :initialData="ficheHeureData" @save="handleUpdateFicheHeure" @cancel="handleCancel" :isSaving="ficheHeureStore.isSubmitting" />
  </div>
</template>

<script setup>
import { onMounted, ref, watchEffect } from 'vue'; // Changed to watchEffect for simplicity if direct cloning is fine
import FicheHeureFormBiats from '@/components/FicheHeures/FicheHeureFormBiats.vue'; // Adjust path
import { useFicheHeureStore } from '@/stores/ficheHeureStore'; // Adjust path
import { useRouter, useRoute } from 'vue-router';

const ficheHeureStore = useFicheHeureStore();
const router = useRouter();
const route = useRoute();
const ficheHeureId = route.params.id;

const ficheHeureData = ref(null);

onMounted(async () => {
  // It's often better to clear previous state, especially if the store holds a single 'current' item
  ficheHeureStore.clearCurrentFicheHeure(); 
  await ficheHeureStore.fetchFicheHeure(ficheHeureId);
  // The watchEffect will handle setting ficheHeureData from the store's currentFicheHeure
});

// Use watchEffect to react to changes in store.currentFicheHeure
watchEffect(() => {
  if (ficheHeureStore.currentFicheHeure) {
    // Clone to prevent direct mutation of store state by the form,
    // especially if the form modifies the object before saving.
    ficheHeureData.value = JSON.parse(JSON.stringify(ficheHeureStore.currentFicheHeure));
  } else {
    ficheHeureData.value = null;
  }
});

const handleUpdateFicheHeure = async (formData) => {
  try {
    await ficheHeureStore.updateFicheHeure(ficheHeureId, formData);
    // Toast for success is handled by apiCall used in store action
    router.push({ name: 'MesFichesHeures' });
  } catch (error) {
    console.error('Erreur MàJ fiche (page):', error);
    // Toast for error also handled by apiCall
  }
};

const handleCancel = () => {
  router.push({ name: 'MesFichesHeures' });
};
</script>
