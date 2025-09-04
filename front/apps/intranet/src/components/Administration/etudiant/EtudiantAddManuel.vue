<script setup>
import {onMounted, ref, watch} from "vue";
import {getAnneeSemestresService, getDepartementAnneesService, createEtudiantsService} from "@requests";
import {ErrorView, ListSkeleton} from "@components";
import { useUsersStore } from "@stores";
import { useToast } from "primevue/usetoast";
import { exportCsv } from "@helpers/downloadCsv";
import { useRouter } from "vue-router";

const hasError = ref(false);

const toast = useToast();
const router = useRouter();

const userStore = useUsersStore();

const visible = ref(false);

const totalSize = ref(0);
const totalSizePercent = ref(0);
const files = ref([]);

const annees = ref([]);
const selectedAnnee = ref(null);
const semestres = ref([]);
const selectedSemestre = ref(null);

const isLoadingAnnees = ref(true);
const isLoadingSemestres = ref(false);

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

const getAnnees = async () => {
  try {
    isLoadingAnnees.value = true;
    const departementId = userStore.departementDefaut.id;

    annees.value = await getDepartementAnneesService(departementId, true);
  } catch (error) {
    console.error('Erreur lors du chargement des années :', error);
    hasError.value = true;
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur est survenue lors du chargement des années. Veuillez réessayer plus tard.',
      life: 5000,
    })
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
    hasError.value = true;
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur est survenue lors du chargement des semestres. Veuillez réessayer plus tard.',
      life: 5000,
    })
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

onMounted(async() => {
  await getAnnees();
});

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    toast.add({ severity: 'success', summary: 'Succès', detail: 'Code copié dans le presse-papiers', life: 3000 });
  } catch (err) {
    console.error('Erreur lors de la copie dans le presse-papiers :', err);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de copier le code dans le presse-papiers', life: 3000 });
  }
};

const createEtudiant = async () => {
  try {
    if (files.value.length === 0) {
      toast.add({
        severity: 'warn',
        summary: 'Avertissement',
        detail: 'Veuillez sélectionner un fichier avant de continuer.',
        life: 5000,
      });
      return;
    }

    const file = files.value[0];
    const reader = new FileReader();

    reader.onload = async (event) => {
      const fileContent = event.target.result;

      const data = {
        fileContent, // Contenu du fichier .csv
      };

      try {
        const response = await createEtudiantsService(data, true);

        // Redirect to the result page with the response data
        router.push({
          path: '/administration/etudiant/ajout/result',
          query: {
            importResult: JSON.stringify(response)
          }
        });
      } catch (error) {
        console.error('Erreur lors de l\'import des étudiants :', error);
        toast.add({
          severity: 'error',
          summary: 'Erreur',
          detail: 'Une erreur est survenue lors de l\'import des étudiants. Veuillez réessayer plus tard.',
          life: 5000,
        });
      }
    };

    reader.onerror = () => {
      toast.add({
        severity: 'error',
        summary: 'Erreur',
        detail: 'Impossible de lire le fichier. Veuillez réessayer.',
        life: 5000,
      });
    };

    reader.readAsText(file); // Lire le fichier en tant que texte
  } catch (error) {
    console.error('Erreur lors de l\'import des étudiants :', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur est survenue lors de l\'import des étudiants. Veuillez réessayer plus tard.',
      life: 5000,
    });
  }
};

const downloadCsv = () => {
  const csvContent = `numero_etudiant;numero_ine;nom;prenom;date_naissance;annee_promotion(aaaa);annee_bac(aaaa);specialite_bac;sexe(M/F);telephone;code_semestre;LIB_AD1;LIB_AD2;LIB_AD3;codepostal;ville;\n`;
  const fileName = "import_etudiant.csv";
  exportCsv({ content: csvContent, fileName });
};
</script>

<template>
  <ErrorView v-if="hasError" class="m-4"/>
  <div v-else class="flex flex-col gap-4">
    <div class="text-2xl font-bold text-center">Importer des étudiants via un fichier .csv</div>
    <Message severity="info" icon="pi pi-info-circle" class="mx-auto">
      Fichier csv (séparateur ";"). Télécharger un modèle ici :
      <a class="font-bold underline hover:cursor-pointer" @click.prevent="downloadCsv">Modèle d'import d'une liste d'étudiants</a>
    </Message>
    <Button label="Voir les codes semestres" class="mx-auto" @click="visible = true"/>

    <Dialog v-model:visible="visible" modal header="Afficher les codes semestres à saisir dans le fichier" :style="{ width: '80vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
      <div class="flex flex-col gap-4">
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
              Codes semestres
            </div>
            <ListSkeleton v-if="isLoadingSemestres" class="w-full"/>
            <div v-else-if="semestres.length > 0" v-for="semestre in semestres" :key="semestre.id" class="w-full">
              {{semestre.libelle}} - <Tag class="font-bold hover:cursor-pointer" @click="copyToClipboard(semestre.codeElement)">{{semestre.codeElement}} <i class="pi pi-copy"></i> </Tag>
            </div>
            <div v-else class="flex items-center justify-center h-full">
              <Message severity="warn" icon="pi pi-info-circle">
                Veuillez d'abord sélectionner une année
              </Message>
            </div>
          </div>
        </div>
      </div>
    </Dialog>

    <div class="text-lg font-medium p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2">
      <div class="flex flex-col items-center justify-center h-full">
      </div>
      <Toast />
      <FileUpload
          name="csvUpload"
          :multiple="false"
          accept=".csv"
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
            <p>Glissez-déposez votre fichier .csv ici pour l'importer.</p>
          </div>
        </template>
      </FileUpload>
      <div class="flex items-center justify-center w-full mt-4">
        <Button severity="primary" class="w-full" @click="createEtudiant()">
          Importer les étudiants
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
