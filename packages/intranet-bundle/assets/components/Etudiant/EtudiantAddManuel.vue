<script setup>
import {onMounted, ref} from "vue";
import {
  getAnneesService,
  createEtudiantService,
  getAnneesUniversitairesService
} from "@requests";
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
const infoZip = ref(false);

const csvTotalSize = ref(0);
const csvTotalSizePercent = ref(0);
const csvFiles = ref([]);

const zipTotalSize = ref(0);
const zipTotalSizePercent = ref(0);
const zipFiles = ref([]);

const annees = ref([]);
const selectedAnnee = ref(null);
const anneesUniv = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingAnnees = ref(true);
const isLoadingAnneesUniv = ref(true);

const onRemoveTemplatingFile = (file, removeFileCallback, index, type) => {
  removeFileCallback(index);
  if (type === 'csv') {
    csvTotalSize.value -= file.size;
    csvTotalSizePercent.value = (csvTotalSize.value / 1000000) * 100;
  } else {
    zipTotalSize.value -= file.size;
    zipTotalSizePercent.value = (zipTotalSize.value / 1000000) * 100;
  }
};

const onSelectedFiles = (event, type) => {
  if (type === 'csv') {
    csvFiles.value = event.files;
    csvTotalSize.value = csvFiles.value.reduce((acc, file) => acc + file.size, 0);
    csvTotalSizePercent.value = (csvTotalSize.value / 1000000) * 100;
  } else {
    zipFiles.value = event.files;
    zipTotalSize.value = zipFiles.value.reduce((acc, file) => acc + file.size, 0);
    zipTotalSizePercent.value = (zipTotalSize.value / 1000000) * 100;
  }
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
    const params = {
      departement: departementId,
      actif: true,
    };
    annees.value = await getAnneesService(params);
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

const getAnneesUniv = async () => {
  try {
    isLoadingAnneesUniv.value = true;
    const params = {
      since: new Date().getFullYear()
    };
    anneesUniv.value = await getAnneesUniversitairesService(params);
  } catch (error) {
    console.error('Erreur lors du chargement des années universitaires :', error);
    hasError.value = true;
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger les années universitaires. Nous faisons notre possible pour résoudre cette erreur au plus vite.',
      life: 5000,
    });
  } finally {
    isLoadingAnneesUniv.value = false;
    selectedAnneeUniv.value = anneesUniv.value.find(annee => annee.actif)?.id ?? null;
  }
};

onMounted(async() => {
  await getAnnees();
  await getAnneesUniv();
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
    if (!selectedAnneeUniv.value) {
      toast.add({
        severity: 'warn',
        summary: 'Attention',
        detail: 'Veuillez sélectionner une année universitaire avant d\'importer des étudiants.',
        life: 5000,
      });
      return;
    }
    if (csvFiles.value.length === 0) {
      toast.add({
        severity: 'warn',
        summary: 'Avertissement',
        detail: 'Veuillez sélectionner un fichier avant d\'importer des étudiants.',
        life: 5000,
      });
      return;
    }

    const file = csvFiles.value[0];
    const reader = new FileReader();

    reader.onload = async (event) => {
      const fileContent = event.target.result;

      const data = {
        fileContent: fileContent, // Contenu du fichier .csv
        anneeUniversitaireId: selectedAnneeUniv.value // ID de l'année universitaire sélectionnée
      };

      try {
        const response = await createEtudiantService(data, '/new', true);

        // Redirect to the result page with the response data
        router.push({
          path: '/intranet/administration/etudiant/ajout/result',
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
  const csvContent = `numero_etudiant;numero_ine;nom;prenom;date_naissance;annee_promotion(aaaa);annee_bac(aaaa);specialite_bac;sexe(M/F);telephone;annee_code_etape;LIB_AD1;LIB_AD2;LIB_AD3;codepostal;ville;\n`;
  const fileName = "import_etudiant.csv";
  exportCsv({ content: csvContent, fileName });
};
</script>

<template>
  <ErrorView v-if="hasError" class="m-4"/>
  <div v-else class="mt-8">
    <header class="card-header mb-6">
      <p class="top-card-header">En une seule étape</p>
      <h2 class="mt-0!">Importer un fichier d'étudiants</h2>
    </header>
    <div class="flex flex-col gap-4">
      <div class="text-lg font-medium p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2">
        <div class="font-medium text-lg">
          Sélectionner une année universitaire
        </div>
        <ListSkeleton v-if="isLoadingAnneesUniv" class="w-full"/>
        <SelectButton v-else :options="anneesUniv" v-model="selectedAnneeUniv" class="w-full justify-center" optionLabel="libelle" optionValue="id"/>
      </div>
      <Divider></Divider>

      <Dialog v-model:visible="visible" modal header="Afficher les codes étape à saisir dans le fichier" :style="{ width: '50vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <div class="flex flex-col gap-4">
          <div class="w-full flex gap-2">
            <div class="text-lg font-medium p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2">
              <ListSkeleton v-if="isLoadingAnnees" class="w-full"/>
              <Button v-else :severity="selectedAnnee && selectedAnnee.id === annee.id ? 'primary' : 'secondary'" v-for="annee in annees" :key="annee.id" class="w-full" @click="copyToClipboard(annee.apogeeCodeEtape)">
                {{annee.libelle}} - <span class="font-bold">{{annee.apogeeCodeEtape}}</span> <i class="pi pi-copy"></i>
              </Button>
            </div>
          </div>
        </div>
      </Dialog>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="text-lg font-medium p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2 border border-surface">
          <div class="flex flex-col items-center justify-center h-full gap-2">
            <div class="font-medium text-lg">
              Importer les étudiants <span class="text-red-500">(obligatoire)</span>
            </div>
            <Message severity="info" icon="pi pi-info-circle" class="mx-auto">
              Fichier csv (séparateur ";"). Télécharger un modèle ici :
              <a class="font-bold underline! hover:cursor-pointer" @click.prevent="downloadCsv">Modèle d'import d'une liste d'étudiants</a>
            </Message>
            <Button label="Voir les codes étape" class="mx-auto" @click="visible = true"/>
          </div>
          <Toast />
          <!--    todo: définir la taille max du fichier      -->
          <FileUpload
              name="csvUpload"
              :multiple="false"
              accept=".csv"
              :maxFileSize="1000000"
              :auto="false"
              :showUploadButton="false"
              :showCancelButton="false"
              @select="(event) => onSelectedFiles(event, 'csv')"
          >
            <template #header="{ chooseCallback }">
              <div class="flex flex-wrap justify-between items-center flex-1 gap-4">
                <div class="flex gap-2">
                  <Button @click="chooseCallback()" icon="pi pi-plus" label="Choisir"></Button>
                </div>
                <div class="w-full">
                  <div class="flex text-muted-color">Taille du fichier : {{ csvTotalSize }}B / 1Mb</div>
                  <ProgressBar :value="csvTotalSizePercent" :showValue="false" class="h-1 w-full md:ml-auto">
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
                      <Button icon="pi pi-times" @click="onRemoveTemplatingFile(file, removeFileCallback, index, 'csv')" variant="outlined" rounded severity="danger" />
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
        </div>

        <div class="text-lg font-medium p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2 border border-surface">
          <div class="flex flex-col items-center justify-center h-full gap-2">
            <div class="font-medium text-lg">
              Importer les photos des étudiants <span class="text-muted-color">(facultatif)</span>
            </div>
            <div class="text-muted-color text-sm">
              L'import des photos est également possible à n'importe quel moment depuis l'Administration ou le profil d'un étudiant.
            </div>
            <Message severity="info" icon="pi pi-info-circle" class="mx-auto">
              Créer un fichier .zip contenant les photos des étudiants. <span @click="infoZip = !infoZip" class="font-bold underline hover:cursor-pointer">Plus d'info</span>
              <ul v-if="infoZip" class="text-left text-sm text-muted-color">
                <li>Le nom de chaque photo doit correspondre au numéro étudiant de l'étudiant.</li>
                <li>Les photos doivent être au format .jpg ou .png et peser moins de 10Mo.</li>
                <li>Le fichier .zip ne doit pas dépasser 10Mo.</li>
              </ul>
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
              @select="(event) => onSelectedFiles(event, 'zip')"
          >
            <template #header="{ chooseCallback }">
              <div class="flex flex-wrap justify-between items-center flex-1 gap-4">
                <div class="flex gap-2">
                  <Button @click="chooseCallback()" icon="pi pi-plus" label="Choisir"></Button>
                </div>
                <div class="w-full">
                  <div class="flex text-muted-color">Taille du fichier : {{ zipTotalSize }}B / 1Mb</div>
                  <ProgressBar :value="zipTotalSizePercent" :showValue="false" class="h-1 w-full md:ml-auto">
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
                      <Button icon="pi pi-times" @click="onRemoveTemplatingFile(file, removeFileCallback, index, 'zip')" variant="outlined" rounded severity="danger" />
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

        <div class="flex items-center justify-center w-full mt-2 lg:col-span-2">
          <Button severity="primary" class="w-full" @click="createEtudiant()">
            Importer les étudiants
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
