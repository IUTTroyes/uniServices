<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { Kpi, HeaderComponent } from '@components';
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
    case 'intégré':
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
  <HeaderComponent
      icon="pi pi-users"
      titre="Ajout d'étudiants"
      description="Ajoutez de nouveaux étudiants à votre département"
  />
  <div class="flex justify-around items-center mb-12 gap-4">
    <div class="card w-full flex items-center justify-center flex-col">
      <Kpi
          label="Étudiants créés"
          :value="created"
          icon="pi pi-user-plus"
          color="green"
      />
    </div>
    <div class="card w-full flex items-center justify-center flex-col">
      <Kpi
          label="Étudiants existants"
          :value="processedLines.filter(line => line.status === 'existant').length"
          icon="pi pi-users"
          color="blue"
      />
    </div>
    <div class="card w-full flex items-center justify-center flex-col">
      <Kpi
          label="Étudiants intégrés"
          :value="processedLines.filter(line => line.status === 'intégré').length"
          icon="pi pi-check-circle"
          color="amber"
      />
    </div>
    <div class="card w-full flex items-center justify-center flex-col">
      <Kpi
          label="Erreurs"
          :value="processedLines.filter(line => line.status === 'erreur').length"
          icon="pi pi-exclamation-triangle"
          color="red"
      />
    </div>
  </div>
  <div class="card">
    <div class="card-header">
      <p class="top-card-header">
        Import étudiants
      </p>
      <div class="flex flex-col items-start">
        <p class="uppercase text-xs font-bold mb-0! text-muted-color">
          résultat
        </p>
        <h2 class="mt-0! mb-0!">
          Résultat de l'import d'étudiants
        </h2>
      </div>
    </div>
    <div class="card-body flex flex-col gap-6">

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

      <div class="summary flex flex-col gap-4">
        <Message severity="info" icon="pi pi-info-circle" class="w-full flex justify-center">{{ message }}</Message>
      </div>

      <div class="results">
        <h3 class="m-0 mb-3">Détails des lignes traitées</h3>
        <Message severity="info" :closable="false" icon="pi pi-info-circle" class="mb-2">
          Maintenez Ctrl ou Cmd et cliquez sur plusieurs colonnes pour trier par plusieurs champs à la fois.
        </Message>
        <DataTable :value="processedLines" stripedRows paginator :rows="10" sortMode="multiple" removable-sort
                   :rowsPerPageOptions="[5, 10, 20, 50]" class="w-full" tableStyle="min-width: 50rem">
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
              <div class="flex gap-2 flex-wrap">
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

                <!-- Button for integrated students -->
                <Button v-if="data.status === 'intégré' && data.etudiantId"
                        icon="pi pi-user"
                        label="Voir profil"
                        size="small"
                        severity="success"
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

      <div v-if="errors.length > 0" class="errors">
        <h3 class="m-0 mb-3">Erreurs détaillées</h3>
        <ul class="list-disc pl-5">
          <li v-for="(error, index) in errors" :key="index" class="text-red-500">{{ error }}</li>
        </ul>
      </div>
      <div class="flex flex-col gap-2 items-center border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-900 p-4 rounded-lg">
        <div class="font-bold text-xl">Prochaine étape</div>
        <router-link to="affectation-groupe">
          <Button label="Affectation des groupes" icon="pi pi-arrow-right" />
        </router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
