<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import ProfilEtudiant from '@components/components/Etudiant/ProfilEtudiant/ProfilEtudiant.vue';
import { getEtudiantService } from '@requests';
import { getEtudiantScolaritesService } from '@requests';
import noImage from "@images/photos_etudiants/noimage.png";

const etudiantPhoto = ref(noImage);
const route = useRoute();
const processedLines = ref([]);
const message = ref('');
const created = ref(0);
const errors = ref([]);
const displayEtudiantModal = ref(false);
const selectedEtudiant = ref(null);
const etudiantSco = ref(null);
const isLoading = ref(false);

onMounted(() => {
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

const viewEtudiantProfile = async (etudiantId) => {
  try {
    isLoading.value = true;
    selectedEtudiant.value = etudiantId;

    const etudiant = await getEtudiantService(etudiantId, true);

    const scolarites = await getEtudiantScolaritesService(etudiantId, true);

    if (scolarites && scolarites.length > 0) {
      etudiantSco.value = {
        etudiant: etudiant
      };
      console.log(etudiantSco.value);
      const photoPath = new URL(
          `@common-images/photos_etudiants/${etudiantSco.photoName}`,
          import.meta.url
      ).href;

      if (etudiantSco.photoName) {
        const photoPath = new URL(
            `@common-images/photos_etudiants/${etudiantSco.photoName}`,
            import.meta.url
        ).href;

        fetch(photoPath)
            .then((response) => {
              if (response.ok) {
                etudiantPhoto.value = photoPath;
              }
            })
            .catch(() => {
              etudiantPhoto.value = noImage;
            });
      }

      displayEtudiantModal.value = true;
    } else {
      console.error('No scolarite found for this student');
    }
  } catch (error) {
    console.error('Error fetching student data:', error);
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div class="card">
    <h2 class="text-2xl font-bold mb-4">Résultat de l'import d'étudiants</h2>
    <Divider />

    <!-- Modal for student profile -->
    <Dialog v-model:visible="displayEtudiantModal" modal header="Profil de l'étudiant" :style="{ width: '90vw' }" :breakpoints="{ '1199px': '95vw', '575px': '98vw' }">
      <div v-if="isLoading" class="flex justify-center items-center p-4">
        <ProgressSpinner />
      </div>
      <div v-else-if="etudiantSco" class="p-4">
        <ProfilEtudiant
          :etudiantSco="etudiantSco"
          :etudiantPhoto="etudiantPhoto"
          :isVisible="displayEtudiantModal"
        />
      </div>
    </Dialog>

    <div class="summary mb-4">
      <Message severity="info" class="mb-2">{{ message }}</Message>
      <div class="flex gap-4 mt-2">
        <div class="bg-neutral-300 bg-opacity-20 p-4 rounded-lg">
          <div class="text-xl font-bold">{{ created }}</div>
          <div>Étudiants créés</div>
        </div>
        <div class="bg-neutral-300 bg-opacity-20 p-4 rounded-lg">
          <div class="text-xl font-bold">{{ processedLines.filter(line => line.status === 'créé').length }}</div>
          <div>Étudiants existants</div>
        </div>
        <div class="bg-neutral-300 bg-opacity-20 p-4 rounded-lg">
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
        <Column header="Actions">
          <template #body="{ data }">
            <div class="flex gap-2">
              <!-- Button for created students -->
              <Button v-if="data.status === 'créé' && data.etudiantId"
                      icon="pi pi-user"
                      label="Voir profil"
                      size="small"
                      @click="viewEtudiantProfile(data.etudiantId)" />

              <!-- Button for existing students -->
              <Button v-if="data.status === 'existant' && data.etudiantId"
                      icon="pi pi-user"
                      label="Voir profil"
                      size="small"
                      severity="info"
                      @click="viewEtudiantProfile(data.etudiantId)" />

              <!-- Placeholder for error status - can be customized later -->
              <Button v-if="data.status === 'erreur'"
                      icon="pi pi-exclamation-triangle"
                      label="Détails"
                      size="small"
                      severity="danger"
                      disabled />
            </div>
          </template>
        </Column>
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
</style>
