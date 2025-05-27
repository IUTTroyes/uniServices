<template>
  <div class="fiche-heure-list-item p-card mb-3 p-4">
    <p><strong>Période:</strong> {{ ficheHeureProp.semaineAnnee }}</p>
    <p><strong>Statut:</strong> <Tag :value="ficheHeureProp.statut?.libelle || ficheHeureProp.statut" :severity="getStatutSeverity(ficheHeureProp.statut)"></Tag></p>
    <p><strong>Soumise le:</strong> {{ formatDate(ficheHeureProp.dateSoumission) }}</p>
    <div class="actions mt-2">
      <Button label="Voir" icon="pi pi-eye" class="p-button-sm p-button-info mr-2" @click="viewFicheHeure(ficheHeureProp.id)" />
      <Button label="Modifier" icon="pi pi-pencil" class="p-button-sm p-button-warning mr-2" @click="editFicheHeure(ficheHeureProp.id)" :disabled="!canEdit(ficheHeureProp)" />
      <Button label="Soumettre" icon="pi pi-send" class="p-button-sm p-button-success" @click="submitFicheHeure(ficheHeureProp.id)" :disabled="!canSubmit(ficheHeureProp)" />
    </div>
  </div>
</template>

<script setup>
import { defineProps, computed } from 'vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
// Assuming enum is copied/available here for severity logic
// import { FicheHeureStatutEnum } from '@/enum/FicheHeureStatutEnum'; 
import { useRouter } from 'vue-router';

// Local placeholder for enum until available globally
const FicheHeureStatutEnum = {
  BROUILLON: 'BROUILLON',
  SOUMISE: 'SOUMISE',
  VALIDEE: 'VALIDEE',
  REJETEE: 'REJETEE',
  // Assuming your backend enum might send objects with 'value' or 'libelle'
  // For direct string comparison or object property access:
  getLibelle: function(statutObjOrString) {
    if (typeof statutObjOrString === 'string') return statutObjOrString;
    return statutObjOrString?.libelle || statutObjOrString?.value || statutObjOrString?.name || 'N/A';
  }
};


const props = defineProps({
  ficheHeureProp: { // Renamed from ficheHeure to avoid conflict if store uses 'ficheHeure'
    type: Object,
    required: true
  }
});

const router = useRouter();

const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString();
};

const getStatutSeverity = (statut) => {
  const val = statut?.value || statut; // Handle both string and potential object from API
  if (val === FicheHeureStatutEnum.VALIDEE) return 'success';
  if (val === FicheHeureStatutEnum.SOUMISE) return 'info';
  if (val === FicheHeureStatutEnum.BROUILLON) return 'warning';
  if (val === FicheHeureStatutEnum.REJETEE) return 'danger';
  return 'secondary';
};

const canEdit = (fh) => (fh.statut?.value || fh.statut) === FicheHeureStatutEnum.BROUILLON;
const canSubmit = (fh) => (fh.statut?.value || fh.statut) === FicheHeureStatutEnum.BROUILLON;

const viewFicheHeure = (id) => {
  router.push({ name: 'FicheHeureDetail', params: { id } }); // Assuming route name
};
const editFicheHeure = (id) => {
  router.push({ name: 'FicheHeureEdit', params: { id } }); // Assuming route name
};
const submitFicheHeure = (id) => {
  // Placeholder - will later call store action
  console.log('Submit fiche heure:', id);
  // In a real app, this would likely be:
  // const store = useFicheHeureStore(); // if not already available
  // store.submitFicheHeure(id).then(...).catch(...);
  alert('Soumission en cours... (Placeholder - voir FicheHeureListBiats pour implémentation avec store)'); 
};
</script>

<style scoped>
.fiche-heure-list-item {
  border: 1px solid #ccc;
  border-radius: 4px;
}
/* PrimeVue p-button-sm might require custom styling or using icon sizes */
.p-button-sm {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}
</style>
