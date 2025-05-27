<template>
  <div class="fiche-heure-list">
    <div class="mb-3">
      <Button label="Nouvelle Fiche d'Heures" icon="pi pi-plus" @click="createNewFicheHeure" />
    </div>
    <DataTable :value="fichesHeures" :loading="isLoading" :paginator="true" :rows="10" responsiveLayout="scroll">
      <Column field="semaineAnnee" header="Période" :sortable="true"></Column>
      <Column field="statut" header="Statut" :sortable="true">
        <template #body="slotProps">
          <Tag :value="getStatutLibelle(slotProps.data.statut)" :severity="getStatutSeverity(slotProps.data.statut)" />
        </template>
      </Column>
      <Column field="dateSoumission" header="Date Soumission" :sortable="true">
        <template #body="slotProps">
          {{ formatDate(slotProps.data.dateSoumission) }}
        </template>
      </Column>
      <Column header="Actions" style="width: 20rem">
        <template #body="slotProps">
          <Button icon="pi pi-eye" class="p-button-rounded p-button-info mr-2" @click="viewFicheHeure(slotProps.data.id)" v-tooltip.top="'Voir'" />
          <Button icon="pi pi-pencil" class="p-button-rounded p-button-warning mr-2" @click="editFicheHeure(slotProps.data.id)" :disabled="!canEdit(slotProps.data)" v-tooltip.top="'Modifier'" />
          <Button icon="pi pi-send" class="p-button-rounded p-button-success" @click="submitFicheHeureHandler(slotProps.data.id)" :disabled="!canSubmit(slotProps.data)" v-tooltip.top="'Soumettre'" />
        </template>
      </Column>
      <template #empty>
        Aucune fiche d'heures trouvée.
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useFicheHeureStore } from '@/stores/ficheHeureStore'; // Adjust path if store is elsewhere
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useRouter } from 'vue-router';
import Tooltip from 'primevue/tooltip';

// Local placeholder for enum until available globally
const FicheHeureStatutEnum = {
  BROUILLON: 'BROUILLON', // Assuming backend sends this string value
  SOUMISE: 'SOUMISE',
  VALIDEE: 'VALIDEE',
  REJETEE: 'REJETEE',
  // Helper to get a displayable libelle if the API sends an object
  getLibelle: function(statutObjOrString) {
    if (typeof statutObjOrString === 'string') return statutObjOrString; // Or map to a prettier string
    return statutObjOrString?.libelle || statutObjOrString?.value || statutObjOrString?.name || 'N/A';
  }
};

const router = useRouter();
const ficheHeureStore = useFicheHeureStore();

const fichesHeures = computed(() => ficheHeureStore.mesFichesHeures);
const isLoading = computed(() => ficheHeureStore.isLoadingMesFichesHeures);

onMounted(() => {
  ficheHeureStore.fetchMesFichesHeures();
});

const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString();
};

// API Platform might send the enum as an object like { name: 'BROUILLON', value: 'Brouillon', libelle: 'Brouillon' }
// or it might send the direct string value 'BROUILLON' depending on serialization groups & config.
// The FicheHeure entity currently has `enumType: FicheHeureStatutEnum::class` for the `statut` property.
// This usually means the *value* of the enum case (e.g., 'Brouillon') is stored in DB and possibly returned.
// Let's assume the API might return the direct string value from the enum (e.g. 'BROUILLON', 'SOUMISE')
// or an object that contains a 'value' or 'name' property representing the enum case.
const getStatutValue = (statutData) => {
  if (typeof statutData === 'string') return statutData;
  return statutData?.value || statutData?.name || statutData; // Fallback to statutData itself if it's already the string
};

const getStatutLibelle = (statutData) => {
  // This function should ideally map the enum *value* (like 'BROUILLON') to a user-friendly libellé.
  // For now, we use the placeholder enum's getLibelle or the value itself.
  // The Tag's :value expects a string.
  const value = getStatutValue(statutData);
  return FicheHeureStatutEnum.getLibelle(value); // Use the helper from placeholder
};


const getStatutSeverity = (statutData) => {
  const value = getStatutValue(statutData);
  if (value === FicheHeureStatutEnum.VALIDEE) return 'success';
  if (value === FicheHeureStatutEnum.SOUMISE) return 'info';
  if (value === FicheHeureStatutEnum.BROUILLON) return 'warning';
  if (value === FicheHeureStatutEnum.REJETEE) return 'danger';
  return 'secondary';
};

const canEdit = (fh) => getStatutValue(fh.statut) === FicheHeureStatutEnum.BROUILLON;
const canSubmit = (fh) => getStatutValue(fh.statut) === FicheHeureStatutEnum.BROUILLON;

const createNewFicheHeure = () => {
  router.push({ name: 'FicheHeureCreate' }); // Assuming route name
};

const viewFicheHeure = (id) => {
  router.push({ name: 'FicheHeureDetail', params: { id } });
};

const editFicheHeure = (id) => {
  router.push({ name: 'FicheHeureEdit', params: { id } });
};

const submitFicheHeureHandler = async (id) => {
  if (confirm('Êtes-vous sûr de vouloir soumettre cette fiche d\'heures ?')) {
    try {
      await ficheHeureStore.submitFicheHeure(id);
      ficheHeureStore.fetchMesFichesHeures(); 
    } catch (error) {
      console.error('Erreur soumission (component):', error);
      // Potentially show a toast notification here if not already handled by apiCall/store
    }
  }
};

// Register tooltip directive locally
const vTooltip = Tooltip;
</script>

<style scoped>
/* Add any specific styles if needed */
.p-button-sm {
    /* PrimeVue might not have p-button-sm by default, adjust if using custom classes or use icon size */
}
</style>
