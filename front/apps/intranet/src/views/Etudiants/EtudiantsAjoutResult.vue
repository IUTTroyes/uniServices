<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const processedLines = ref([]);
const message = ref('');
const created = ref(0);
const errors = ref([]);

onMounted(() => {
  // Get the data from the route query parameters
  if (route.query.importResult) {
    const importResult = JSON.parse(route.query.importResult);
    processedLines.value = importResult.processedLines || [];
    message.value = importResult.message || '';
    created.value = importResult.created || 0;
    errors.value = importResult.errors || [];
  }
});

// Function to get the severity class based on status
const getSeverity = (status) => {
  switch (status) {
    case 'créé':
      return 'success';
    case 'existant':
      return 'info';
    case 'erreur':
      return 'danger';
    default:
      return 'warning';
  }
};
</script>

<template>
  <div class="card">
    <h2 class="text-2xl font-bold mb-4">Résultat de l'import d'étudiants</h2>
    <Divider />

    <div class="summary mb-4">
      <Message severity="info" class="mb-2">{{ message }}</Message>
      <div class="flex gap-4 mt-2">
        <div class="stat-card">
          <div class="text-xl font-bold">{{ created }}</div>
          <div>Étudiants créés</div>
        </div>
        <div class="stat-card">
          <div class="text-xl font-bold">{{ processedLines.filter(line => line.status === 'créé').length }}</div>
          <div>Étudiants existants</div>
        </div>
        <div class="stat-card">
          <div class="text-xl font-bold">{{ processedLines.filter(line => line.status === 'erreur').length }}</div>
          <div>Erreurs</div>
        </div>
      </div>
    </div>

    <div class="results">
      <h3 class="text-xl font-bold mb-2">Détails des lignes traitées</h3>
      <DataTable :value="processedLines" stripedRows paginator :rows="10"
                 :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
        <Column field="nom" header="Nom" sortable></Column>
        <Column field="prenom" header="Prénom" sortable></Column>
        <Column field="status" header="Statut" sortable>
          <template #body="{ data }">
            <Tag :severity="getSeverity(data.status)">{{ data.status }}</Tag>
          </template>
        </Column>
        <Column field="message" header="Message"></Column>
      </DataTable>
    </div>

    <div v-if="errors.length > 0" class="errors mt-4">
      <h3 class="text-xl font-bold mb-2">Erreurs détaillées</h3>
      <ul class="list-disc pl-5">
        <li v-for="(error, index) in errors" :key="index" class="text-red-500">{{ error }}</li>
      </ul>
    </div>

    <div class="actions mt-6">
      <Button label="Retour à l'ajout d'étudiants" icon="pi pi-arrow-left"
              @click="$router.push('/administration/etudiant/ajout/manuel')" />
    </div>
  </div>
</template>

<style scoped>
.stat-card {
  background-color: #f8f9fa;
  padding: 1rem;
  border-radius: 0.5rem;
  text-align: center;
  min-width: 120px;
}
</style>
