<template>
  <div v-if="ficheHeure" class="p-card p-4 fiche-heure-view-biats">
    <h2 class="text-xl font-bold mb-4">Détail de la Fiche d'Heures</h2>

    <div class="grid">
      <div class="col-12 md:col-6">
        <p><strong>Période (Semaine/Année):</strong> {{ ficheHeure.semaineAnnee }}</p>
        <p><strong>Statut:</strong> <Tag :value="getStatutLibelle(ficheHeure.statut)" :severity="getStatutSeverity(ficheHeure.statut)"></Tag></p>
      </div>
      <div class="col-12 md:col-6">
        <p><strong>Date de soumission:</strong> {{ formatDate(ficheHeure.dateSoumission) }}</p>
        <p v-if="ficheHeure.validateur"><strong>Validateur:</strong> {{ ficheHeure.validateur.display || ficheHeure.validateur.prenom + ' ' + ficheHeure.validateur.nom || ficheHeure.validateur.username }}</p>
        <p><strong>Date de validation/rejet:</strong> {{ formatDate(ficheHeure.dateValidation) }}</p>
      </div>
      <div class="col-12" v-if="ficheHeure.commentaireValidation">
        <p><strong>Commentaire de validation/rejet:</strong></p>
        <pre class="commentaire-validation">{{ ficheHeure.commentaireValidation }}</pre>
      </div>
    </div>

    <h3 class="text-lg font-semibold mt-4 mb-2">Détail des heures</h3>
    <DataTable :value="ficheHeure.heures" v-if="ficheHeure.heures && ficheHeure.heures.length" responsiveLayout="scroll">
      <Column field="date" header="Date">
        <template #body="{data}">{{ formatDateForDisplay(data.date) }}</template>
      </Column>
      <Column field="startTime" header="Début"></Column>
      <Column field="endTime" header="Fin"></Column>
      <Column field="task" header="Tâche/Description"></Column>
       <template #empty>
        Aucune entrée d'heure détaillée pour cette fiche.
      </template>
    </DataTable>
    <p v-else class="mt-2">Aucune entrée d'heure détaillée pour cette fiche.</p>
  </div>
  <div v-else class="p-4">
    <p>Chargement des détails de la fiche d'heures ou aucune fiche sélectionnée...</p>
  </div>
</template>

<script setup>
import { defineProps, computed } from 'vue';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

// Local placeholder for enum until available globally
const FicheHeureStatutEnum = {
  BROUILLON: 'BROUILLON',
  SOUMISE: 'SOUMISE',
  VALIDEE: 'VALIDEE',
  REJETEE: 'REJETEE',
  // Helper to get a displayable libelle if the API sends an object or direct string
  getLibelle: function(statutObjOrString) {
    if (typeof statutObjOrString === 'string') { // Could map to prettier names here if needed
        const mapping = {
            'BROUILLON': 'Brouillon',
            'SOUMISE': 'Soumise',
            'VALIDEE': 'Validée',
            'REJETEE': 'Rejetée',
        };
        return mapping[statutObjOrString] || statutObjOrString;
    }
    // If it's an object (e.g. from API Platform with 'name' or 'libelle' from enum)
    return statutObjOrString?.libelle || statutObjOrString?.name || statutObjOrString?.value || 'N/A';
  }
};

const props = defineProps({
  ficheHeure: {
    type: Object,
    default: null // Changed from required: true to allow for null state
  }
});

const formatDate = (dateString) => {
  if (!dateString) return '-';
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Date invalide';
    return date.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' });
  } catch (e) {
    return 'Date invalide';
  }
};

const formatDateForDisplay = (dateValue) => {
  if (!dateValue) return '';
  const date = (typeof dateValue === 'string') ? new Date(dateValue) : dateValue;
  if (isNaN(date.getTime())) return '';
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};


const getStatutValue = (statutData) => {
  if (typeof statutData === 'string') return statutData;
  return statutData?.name || statutData?.value || statutData; // API Platform often uses 'name' for enum string value
};

const getStatutLibelle = (statutData) => {
  const value = getStatutValue(statutData);
  return FicheHeureStatutEnum.getLibelle(value);
};

const getStatutSeverity = (statutData) => {
  const value = getStatutValue(statutData);
  if (value === FicheHeureStatutEnum.VALIDEE) return 'success';
  if (value === FicheHeureStatutEnum.SOUMISE) return 'info';
  if (value === FicheHeureStatutEnum.BROUILLON) return 'warning';
  if (value === FicheHeureStatutEnum.REJETEE) return 'danger';
  return 'secondary';
};

</script>

<style scoped>
.fiche-heure-view-biats p {
  margin-bottom: 0.75rem;
}
.commentaire-validation {
  white-space: pre-wrap; /* Preserve line breaks and spaces */
  background-color: #f8f9fa;
  padding: 0.5rem;
  border-radius: 4px;
  border: 1px solid #dee2e6;
}
</style>
