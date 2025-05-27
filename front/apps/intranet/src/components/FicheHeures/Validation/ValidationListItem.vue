<template>
  <div class="validation-list-item p-card mb-3 p-4">
    <p><strong>Soumis par:</strong> {{ ficheHeure.personnel?.display || ficheHeure.personnel?.username || 'N/A' }}</p>
    <p><strong>Période:</strong> {{ ficheHeure.semaineAnnee }}</p>
    <p><strong>Date de soumission:</strong> {{ formatDate(ficheHeure.dateSoumission) }}</p>
    <p><strong>Statut:</strong> <Tag :value="ficheHeure.statut?.libelle || ficheHeure.statut" :severity="getStatutSeverity(ficheHeure.statut)"></Tag></p>
    <div class="actions mt-2">
      <Button label="Examiner" icon="pi pi-search" class="p-button-sm p-button-info" @click="reviewFicheHeure(ficheHeure.id)" />
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useRouter } from 'vue-router';

// Placeholder for FicheHeureStatutEnum - import or define locally
const FicheHeureStatutEnum = { BROUILLON: 'BROUILLON', SOUMISE: 'SOUMISE', VALIDEE: 'VALIDEE', REJETEE: 'REJETEE' };

const props = defineProps({ ficheHeure: { type: Object, required: true } });
const router = useRouter();

const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString();
};

const getStatutSeverity = (statut) => {
  const val = statut?.value || statut;
  if (val === FicheHeureStatutEnum.VALIDEE) return 'success';
  if (val === FicheHeureStatutEnum.SOUMISE) return 'info';
  if (val === FicheHeureStatutEnum.BROUILLON) return 'warning';
  if (val === FicheHeureStatutEnum.REJETEE) return 'danger';
  return 'secondary';
};

const reviewFicheHeure = (id) => {
  router.push({ name: 'FicheHeureDetail', params: { id } }); // Or a specific validator detail route if different
};
</script>

<style scoped>
.validation-list-item { border: 1px solid #ccc; }
</style>
