<script setup>
import {onMounted, ref, watch} from 'vue';
import { getDepartementAnneesService, getAnneeSemestresService, getAllAnneesUniversitairesService } from "@requests";
import {ErrorView, ListSkeleton} from "@components";
import { useUsersStore, useSemestreStore } from "@stores";
import { useToast } from "primevue/usetoast";
import { usePrimeVue } from 'primevue/config';

const userStore = useUsersStore();
const annees = ref([]);
const selectedAnnee = ref(null);
const semestres = ref([]);
const selectedSemestre = ref(null);
const anneesUniv = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingAnnees = ref(true);
const isLoadingSemestres = ref(false);
const isLoadingAnneesUniv = ref(true);

const toast = useToast();

const totalSize = ref(0);
const totalSizePercent = ref(0);
const files = ref([]);

const onRemoveTemplatingFile = (file, removeFileCallback, index) => {
  removeFileCallback(index);
  totalSize.value -= file.size;
  totalSizePercent.value = (totalSize.value / 1000000) * 100;
};

const onSelectedFiles = (event) => {
  files.value = event.files;
  totalSize.value = files.value.reduce((acc, file) => acc + file.size, 0);
  totalSizePercent.value = (totalSize.value / 1000000) * 100;
};

const formatSize = (bytes) => {
  const k = 1024;
  const dm = 2;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];

  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
};

const getAnneesUniv = async () => {
  try {
    isLoadingAnneesUniv.value = true;
    anneesUniv.value = await getAllAnneesUniversitairesService();
    if (anneesUniv.value.length > 0) {
      selectedAnneeUniv.value = anneesUniv.value[0];
    }
  } catch (error) {
    console.error('Erreur lors du chargement des années universitaires :', error);
  } finally {
    isLoadingAnneesUniv.value = false;
  }
};

const getAnnees = async () => {
  try {
    isLoadingAnnees.value = true;
    const departementId = userStore.departementDefaut.id;

    annees.value = await getDepartementAnneesService(departementId, true);
  } catch (error) {
    console.error('Erreur lors du chargement des années :', error);
  } finally {
    console.log(annees.value)
    isLoadingAnnees.value = false;
  }
};

const getSemestresSelectedAnnee = async () => {
  try {
    semestres.value = [];
    isLoadingSemestres.value = true;
    semestres.value = await getAnneeSemestresService(selectedAnnee.value.id);
  } catch (error) {
    console.error('Erreur lors du chargement des semestres :', error);
  } finally {
    isLoadingSemestres.value = false;
    console.log(semestres.value)
  }
};

watch(selectedAnnee, async (newValue) => {
  if (newValue) {
    await getSemestresSelectedAnnee()
  }
})

watch(selectedAnneeUniv, async (newValue) => {
  if (newValue) {
    console.log(newValue)
  }
})

onMounted(async() => {
  await getAnneesUniv();
  await getAnnees();
});

</script>

<template>
  <div class="flex flex-col gap-4">
      <em class="text-lg font-medium text-muted-color">
        Importer les étudiants depuis Apogée
      </em>

    <div class="text-lg font-medium border p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2">
      <div class="font-medium text-lg">
        Sélectionner une année universitaire
      </div>
      <ListSkeleton v-if="isLoadingAnneesUniv" class="w-full"/>
      <SelectButton v-else :options="anneesUniv" v-model="selectedAnneeUniv" class="w-full justify-center" optionLabel="libelle" optionValue="id"/>
    </div>
    <div class="w-full flex gap-2">
      <div class="text-lg font-medium border p-4 w-1/2 text-center mx-auto rounded-md flex flex-col gap-2">
        <div class="font-medium text-lg">
          Sélectionner une année
        </div>
        <ListSkeleton v-if="isLoadingAnnees" class="w-full"/>
        <Button v-else :severity="selectedAnnee && selectedAnnee.id === annee.id ? 'primary' : 'secondary'" v-for="annee in annees" :key="annee.id" class="w-full" @click="selectedAnnee = annee">
          {{annee.libelle}}
        </Button>
      </div>
      <div class="text-lg font-medium border p-4 w-1/2 text-center mx-auto rounded-md flex flex-col gap-2">
        <div class="font-medium text-lg">
          Sélectionner un semestre
        </div>
        <ListSkeleton v-if="isLoadingSemestres" class="w-full"/>
        <Button v-else-if="semestres.length > 0" :severity="selectedSemestre && selectedSemestre.id === semestre.id ? 'primary' : 'secondary'" v-for="semestre in semestres" :key="semestre.id" class="w-full" @click="selectedSemestre = semestre">
          {{semestre.libelle}}
        </Button>
        <div v-else class="flex items-center justify-center h-full">
          <Message severity="warn" icon="pi pi-info-circle">
            Veuillez d'abord sélectionner une année
          </Message>
        </div>
      </div>
    </div>
    <div class="text-lg font-medium border p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2">
      <div class="flex flex-col items-center justify-center h-full">
        <div class="font-medium text-lg">
          Importer les photos des étudiants
        </div>
        <Message severity="info" icon="pi pi-info-circle" class="mx-auto">
          Récupérez les photos (format .zip) sur le bureau virtuel de l'URCA (enseignement > trombinoscope) et importez les ici.
        </Message>
      </div>
      <Toast />
      <FileUpload
          name="zipUpload"
          :multiple="false"
          accept=".zip"
          :maxFileSize="1000000"
          :auto="false"
          :showUploadButton="false"
          :showCancelButton="false"
          @select="onSelectedFiles"
      >
        <template #header="{ chooseCallback, clearCallback, files }">
          <div class="flex flex-wrap justify-between items-center flex-1 gap-4">
            <div class="flex gap-2">
              <Button @click="chooseCallback()" icon="pi pi-plus" label="Choisir"></Button>
            </div>
            <div class="w-full">
              <div class="flex text-muted-color">Taille du fichier : {{ totalSize }}B / 1Mb</div>
              <ProgressBar :value="totalSizePercent" :showValue="false" class="h-1 w-full md:ml-auto">
              </ProgressBar>
            </div>
          </div>
        </template>
        <template #content="{ files, removeFileCallback }">
          <div class="flex flex-col gap-8 pt-4">
            <div v-if="files.length > 0">
              <div class="flex flex-wrap gap-4">
                <div v-for="(file, index) of files" :key="file.name + file.type + file.size" class="p-4 rounded-border flex flex-col border border-surface items-center gap-2 w-full">
                  <div>
                    <span class="font-semibold text-ellipsis whitespace-nowrap overflow-hidden text-sm">{{ file.name }}</span>
                    <div>{{ formatSize(file.size) }}</div>
                  </div>
                  <Button icon="pi pi-times" @click="onRemoveTemplatingFile(file, removeFileCallback, index)" variant="outlined" rounded severity="danger" />
                </div>
              </div>
            </div>
          </div>
        </template>
        <template #empty>
          <div class="flex items-center justify-center flex-col gap-2">
            <i class="pi pi-folder-open !text-2xl !text-muted-color" />
            <p>Glissez-déposez votre fichier .zip ici pour l'importer.</p>
          </div>
        </template>
      </FileUpload>
    </div>
    <div class="text-lg font-medium p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2">
      <div class="flex items-center justify-center h-full">
        <Button severity="primary" class="w-full" :disabled="!selectedAnnee || !selectedSemestre">
          Importer les étudiants
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
