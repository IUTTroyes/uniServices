<template>
  <div class="validation-actions mt-4 p-card p-4" v-if="canValidateOrReject">
    <h3 class="text-lg font-semibold mb-3">Actions de Validation</h3>
    <div class="p-fluid grid">
      <div class="field col-12" v-if="showCommentField">
        <label for="commentaireValidation">Commentaire (requis pour rejet)</label>
        <Textarea id="commentaireValidation" v-model.trim="commentaire" rows="3" :class="{ 'p-invalid': rejectionAttempted && !commentaire }" />
        <small v-if="rejectionAttempted && !commentaire" class="p-error">Le commentaire est requis pour rejeter.</small>
      </div>
      <div class="col-12 flex justify-content-end">
        <Button label="Rejeter" icon="pi pi-times" class="p-button-danger mr-2" @click="handleReject" :loading="ficheHeureStore.isRejecting" />
        <Button label="Valider" icon="pi pi-check" class="p-button-success" @click="handleValidate" :loading="ficheHeureStore.isValidating" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, computed } from 'vue';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import { useFicheHeureStore } from '@/stores/ficheHeureStore'; // Adjust path as needed

// Local placeholder for FicheHeureStatutEnum as per note
const FicheHeureStatutEnum = {
  BROUILLON: 'BROUILLON',
  SOUMISE: 'SOUMISE', // Used in canValidateOrReject
  VALIDEE: 'VALIDEE',
  REJETEE: 'REJETEE',
  // Add other statuses if needed by other logic in this component
};

const props = defineProps({
  ficheHeure: { type: Object, required: true }
});

const ficheHeureStore = useFicheHeureStore();
const commentaire = ref('');
const rejectionAttempted = ref(false);
const showCommentField = ref(true); // Always show for now, or can be conditional on reject click

// Helper to get the core string value of the status
const getStatutValue = (statutData) => {
  if (typeof statutData === 'string') return statutData;
  // API Platform often uses 'name' for enum string value if it's a backed enum exposed as IRI then object
  // or 'value' if it's part of a more complex object.
  // Given previous components, props.ficheHeure.statut might be the direct string, or an object from API.
  return statutData?.name || statutData?.value || statutData;
};

const canValidateOrReject = computed(() => {
  if (!props.ficheHeure) return false;
  const currentStatus = getStatutValue(props.ficheHeure.statut);
  return currentStatus === FicheHeureStatutEnum.SOUMISE;
});

const handleValidate = async () => {
  if (!props.ficheHeure || !props.ficheHeure.id) return;
  if (confirm('Êtes-vous sûr de vouloir valider cette fiche d\'heures ?')) {
    try {
      await ficheHeureStore.validateFicheHeure(props.ficheHeure.id);
      // Parent component (FicheHeureDetailPage) should observe store changes
      // and might re-fetch or update based on the store's currentFicheHeure.
      // The store action (validateFicheHeure) should ideally update currentFicheHeure.
    } catch (error) {
      console.error('Erreur validation (ValidationView):', error);
      // Toast for error handled by apiCall in store
    }
  }
};

const handleReject = async () => {
  if (!props.ficheHeure || !props.ficheHeure.id) return;
  rejectionAttempted.value = true;
  if (!commentaire.value) {
    // UI feedback is provided by :class and <small> tag
    return;
  }
  if (confirm('Êtes-vous sûr de vouloir rejeter cette fiche d\'heures ?')) {
    try {
      await ficheHeureStore.rejectFicheHeure(props.ficheHeure.id, commentaire.value);
      // Similar to validate, parent/store should ensure data is refreshed.
    } catch (error) {
      console.error('Erreur rejet (ValidationView):', error);
      // Toast for error handled by apiCall in store
    }
  }
};
</script>

<style scoped>
.validation-actions {
  border-top: 1px solid #eee; /* Example separator */
}
.p-error {
  display: block; /* Ensure error messages take full width under inputs */
  margin-top: 0.25rem;
}
</style>
