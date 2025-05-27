<template>
  <div class="fiches-heures-detail-page p-4">
    <div v-if="ficheHeureStore.isLoadingCurrentFicheHeure && !ficheHeureStore.currentFicheHeure">Chargement des détails...</div>
    <FicheHeureViewBiats v-else-if="ficheHeureStore.currentFicheHeure" :ficheHeure="ficheHeureStore.currentFicheHeure" />
    <div v-else>Fiche non trouvée ou erreur de chargement.</div>

    <!-- Validator Actions -->
    <ValidationView 
      v-if="shouldShowValidationView"
      :ficheHeure="ficheHeureStore.currentFicheHeure"
      class="mt-4" 
    />

    <Button label="Retour à la liste" icon="pi pi-arrow-left" @click="goBack" class="mt-4" />
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, computed } from 'vue';
import FicheHeureViewBiats from '@/components/FicheHeures/FicheHeureViewBiats.vue';
import ValidationView from '@/components/FicheHeures/Validation/ValidationView.vue';
import { useFicheHeureStore } from '@/stores/ficheHeureStore';
import { useUsersStore } from '@/stores/usersStore'; // Path confirmed from router setup
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';

// Local placeholder for FicheHeureStatutEnum
const FicheHeureStatutEnum = {
  SOUMISE: 'SOUMISE',
  // Add other statuses if needed by other logic in this component
};

const ficheHeureStore = useFicheHeureStore();
const usersStore = useUsersStore();
const route = useRoute();
const router = useRouter();
const ficheHeureId = route.params.id;

const isValidator = computed(() => {
  return usersStore.user?.roles?.includes('ROLE_FICHE_HEURE_VALIDATEUR') || false;
});

// Helper to get the core string value of the status from potential object/string
const getStatutValue = (statutData) => {
  if (typeof statutData === 'string') return statutData;
  // API Platform often uses 'name' for enum string value if it's a backed enum exposed as IRI then object
  // or 'value' if it's part of a more complex object.
  return statutData?.name || statutData?.value || statutData; 
};

const shouldShowValidationView = computed(() => {
  if (!isValidator.value || !ficheHeureStore.currentFicheHeure) {
    return false;
  }
  const currentStatus = getStatutValue(ficheHeureStore.currentFicheHeure.statut);
  return currentStatus === FicheHeureStatutEnum.SOUMISE;
});

onMounted(() => {
  // Clear previous data before fetching new one
  ficheHeureStore.clearCurrentFicheHeure(); 
  ficheHeureStore.fetchFicheHeure(ficheHeureId);
  
  // Ensure user data is loaded if not already (usersStore should handle its own loading state)
  // usersStore.isLoaded and usersStore.isLoading were used in router.js
  if (!usersStore.isLoaded && !usersStore.isLoading) { 
    usersStore.getUser(); // Or specific action to load if not automatically done by store
  }
});

onUnmounted(() => {
  ficheHeureStore.clearCurrentFicheHeure(); // Clear when leaving the page
});

const goBack = () => {
  // Improved back navigation: if validator came from validation list, go there. Else, 'MesFichesHeures'.
  // router.options.history.state.back might be null if it's the first page visited in the session.
  // A more robust way might be to check route.meta or pass a query param from the list page.
  // For now, checking the history state or a meta field from the previous route.
  const previousRouteName = router.options.history.state.back ? router.resolve(router.options.history.state.back)?.name : null;

  if (isValidator.value && (previousRouteName === 'ValidationFichesHeures' || router.currentRoute.value.meta.fromValidationList)) {
     router.push({ name: 'ValidationFichesHeures'});
  } else {
     router.push({ name: 'MesFichesHeures' });
  }
};
</script>

<style scoped>
/* Page specific styles if any */
</style>
