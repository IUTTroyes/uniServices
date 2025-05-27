<template>
  <div class="validation-list">
    <DataTable :value="fichesHeures" :loading="isLoading" :paginator="true" :rows="10" responsiveLayout="scroll">
      <Column field="personnel.display" header="Soumis par" :sortable="true">
        <template #body="slotProps">
          {{ slotProps.data.personnel?.display || slotProps.data.personnel?.username || 'N/A' }}
        </template>
      </Column>
      <Column field="semaineAnnee" header="Période" :sortable="true"></Column>
      <Column field="dateSoumission" header="Date Soumission" :sortable="true">
        <template #body="slotProps">
          {{ formatDate(slotProps.data.dateSoumission) }}
        </template>
      </Column>
      <Column field="statut" header="Statut" :sortable="true">
        <template #body="slotProps">
          <Tag :value="getStatutLibelle(slotProps.data.statut)" :severity="getStatutSeverity(slotProps.data.statut)" />
        </template>
      </Column>
      <Column header="Actions" style="width: 10rem">
        <template #body="slotProps">
          <Button icon="pi pi-search" label="Examiner" class="p-button-sm p-button-info" @click="reviewFicheHeure(slotProps.data.id)" />
        </template>
      </Column>
      <template #empty>
        Aucune fiche d'heures à valider.
      </template>
      <!-- TODO: Add tabs or filters for 'Pending', 'Validated', 'Rejected' -->
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useFicheHeureStore } from '@/stores/ficheHeureStore'; // Adjust path
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useRouter } from 'vue-router';

// Placeholder for FicheHeureStatutEnum
const FicheHeureStatutEnum = { 
  BROUILLON: 'BROUILLON', 
  SOUMISE: 'SOUMISE', 
  VALIDEE: 'VALIDEE', 
  REJETEE: 'REJETEE',
  getLibelle: function(statutObjOrString) {
    const val = (typeof statutObjOrString === 'string') ? statutObjOrString : (statutObjOrString?.value || statutObjOrString?.name);
    const mapping = {
        'BROUILLON': 'Brouillon',
        'SOUMISE': 'Soumise',
        'VALIDEE': 'Validée',
        'REJETEE': 'Rejetée',
    };
    return mapping[val] || val || 'N/A';
  }
};

const router = useRouter();
const ficheHeureStore = useFicheHeureStore();

// Helper to get the core string value of the status
const getStatutValue = (statutData) => {
  if (typeof statutData === 'string') return statutData;
  return statutData?.value || statutData?.name || statutData; // API Platform often uses 'name' for enum string value
};

const fichesHeures = computed(() => 
  ficheHeureStore.fichesHeuresPourValidation.filter(fh => getStatutValue(fh.statut) === FicheHeureStatutEnum.SOUMISE)
);
const isLoading = computed(() => ficheHeureStore.isLoadingFichesHeuresPourValidation);

onMounted(() => {
  // Params might be needed here for backend filtering, e.g., { statut: 'SOUMISE' }
  // Or, the backend API endpoint for getFichesHeuresPourValidation might already be filtered for validators.
  ficheHeureStore.fetchFichesHeuresPourValidation({}); 
});

const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString();
};

const getStatutLibelle = (statutData) => {
  return FicheHeureStatutEnum.getLibelle(statutData);
};

const getStatutSeverity = (statutData) => {
  const val = getStatutValue(statutData);
  if (val === FicheHeureStatutEnum.VALIDEE) return 'success';
  if (val === FicheHeureStatutEnum.SOUMISE) return 'info';
  if (val === FicheHeureStatutEnum.BROUILLON) return 'warning'; 
  if (val === FicheHeureStatutEnum.REJETEE) return 'danger';
  return 'secondary';
};

const reviewFicheHeure = (id) => {
  router.push({ name: 'FicheHeureDetail', params: { id } }); // Navigates to the shared detail page
};
</script>

<style scoped>
/* Add any specific styles if needed */
</style>
