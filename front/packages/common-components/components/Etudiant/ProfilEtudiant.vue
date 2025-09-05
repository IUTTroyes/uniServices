<script setup>
import { ref, onMounted, computed } from "vue";
import { getEtudiantScolaritesService } from "@requests";
import { useToast } from "primevue/usetoast";
import { fr } from "date-fns/locale";
import { format, parseISO, differenceInYears } from "date-fns";
import { formatAdresse } from "@helpers/adresse.js";
import { updateEtudiantService } from "@requests";

const toast = useToast();

const props = defineProps({
  isVisible: Boolean,
  etudiantSco: Object,
  etudiantPhoto: String,
});

const loadingScolarites = ref(true);
const etudiantScolarites = ref([]);
const activeTab = ref(null);

const isEditing = ref(false);

const copyToClipboard = (email) => {
  navigator.clipboard.writeText(email).then(() => {
    toast.add({
      severity: "success",
      summary: "Succès",
      detail: "Adresse email copiée",
      life: 5000,
    });
  }).catch(() => alert("Échec de la copie."));
};

const getEtudiantScolarites = async () => {
  loadingScolarites.value = true;
  try {
    etudiantScolarites.value = await getEtudiantScolaritesService(props.etudiantSco.etudiant.id);
    etudiantScolarites.value.sort((a, b) => {
      const anneeA = a.anneeUniversitaire.annee;
      const anneeB = b.anneeUniversitaire.annee;
      return anneeB - anneeA;
    });

    // Définir la première tab comme active
    if (etudiantScolarites.value.length > 0) {
      activeTab.value = etudiantScolarites.value[0].anneeUniversitaire.libelle;
    }

    if (etudiantScolarites.value.length > 0) {
      activeTab.value = etudiantScolarites.value[0].anneeUniversitaire.libelle;
    }
  } catch (error) {
    console.error("Erreur lors de la récupération :", error);
  } finally {
    loadingScolarites.value = false;
  }
};

onMounted(async () => {
  await getEtudiantScolarites();
});

// Formater la date de naissance
const formattedDateNaissance = computed(() => {
  if (!props.etudiantSco.etudiant.date_naissance) return "Non spécifiée";
  return format(parseISO(props.etudiantSco.etudiant.date_naissance), "dd/MM/yyyy", { locale: fr });
});

// Calculer l'âge
const age = computed(() => {
  if (!props.etudiantSco.etudiant.date_naissance) return "Âge inconnu";
  return differenceInYears(new Date(), parseISO(props.etudiantSco.etudiant.date_naissance));
});

const cleanEtudiantObject = (etudiant) => {
  const cleanedEtudiant = { ...etudiant };

  // Supprimer les propriétés inutiles
  delete cleanedEtudiant["@id"];
  delete cleanedEtudiant["@type"];

  // Reformater les sous-objets (adresseEtudiante et adresseParentale)
  if (cleanedEtudiant.adresseEtudiante) {
    cleanedEtudiant.adresseEtudiante = {
      adresse: cleanedEtudiant.adresseEtudiante.adresse || "",
      complement1: cleanedEtudiant.adresseEtudiante.complement1 || "",
      complement2: cleanedEtudiant.adresseEtudiante.complement2 || "",
      ville: cleanedEtudiant.adresseEtudiante.ville || "",
      codePostal: cleanedEtudiant.adresseEtudiante.codePostal || "",
      pays: cleanedEtudiant.adresseEtudiante.pays || "",
    };
  }

  if (cleanedEtudiant.adresseParentale) {
    cleanedEtudiant.adresseParentale = {
      adresse: cleanedEtudiant.adresseParentale.adresse || "",
      complement1: cleanedEtudiant.adresseParentale.complement1 || "",
      complement2: cleanedEtudiant.adresseParentale.complement2 || "",
      ville: cleanedEtudiant.adresseParentale.ville || "",
      codePostal: cleanedEtudiant.adresseParentale.codePostal || "",
      pays: cleanedEtudiant.adresseParentale.pays || "",
    };
  }

  return cleanedEtudiant;
};

// Mettre à jour les informations de l'étudiant
const updateEtudiantData = async () => {
  try {
    const cleanedEtudiant = cleanEtudiantObject(props.etudiantSco.etudiant);
    const response = await updateEtudiantService(cleanedEtudiant);
  } catch (error) {
    console.error("Erreur lors de la mise à jour :", error);
    toast.add({
      severity: "error",
      summary: "Erreur",
      detail: "Échec de la mise à jour des informations",
      life: 5000,
    });
  } finally {
    isEditing.value = false;
    toast.add({
      severity: "success",
      summary: "Succès",
      detail: "Informations mises à jour avec succès",
      life: 5000,
    });
  }
};
</script>

<template>
  <div class="flex items-stretch md:flex-row flex-col gap-6 md:px-12">
    <div class="flex flex-col gap-2 justify-center md:w-1/3 w-full h-auto p-4 mb-0 card scol-profil">
      <div class="w-full flex justify-center">
        <img :src="etudiantPhoto" alt="photo de profil" class="rounded-full w-40 h-auto border-8 border-gray-300 border-opacity-60">
      </div>
      <div class="flex flex-col gap-2 p-4 bg-surface-0 shadow-md dark:bg-surface-800 dark:border-gray-700 rounded-md">
        <div class="text-center font-bold flex justify-center gap-1">
          <div class="first-letter:uppercase lowercase">{{ props.etudiantSco.etudiant.prenom }}</div>
          <div class="uppercase">{{ props.etudiantSco.etudiant.nom }}</div>
        </div>
        <div class="text-center underline hover:cursor-pointer" @click="copyToClipboard(props.etudiantSco.etudiant.mailUniv)">
          {{ props.etudiantSco.etudiant.mailUniv }} <i class="pi pi-copy"></i>
        </div>
        <hr>
      </div>
    </div>
    <div class="flex flex-col gap-2 md:w-2/3 w-full p-4">
      <div>
        <h1 class="text-2xl font-bold mb-4">Informations générales</h1>
        <Message severity="info" class="mb-4" icon="pi pi-info-circle">
          Si vous constatez une erreur dans ces données, contactez le responsable de la formation.
        </Message>
        <div class="flex md:flex-row flex-col gap-6">
          <ul class="md:w-1/3 flex flex-col gap-2">
            <li><span class="font-bold">Prénom :</span> {{props.etudiantSco.etudiant.prenom}}</li>
            <li><span class="font-bold">Nom :</span> {{props.etudiantSco.etudiant.nom}}</li>
            <li><span class="font-bold">Date de naissance :</span> {{ formattedDateNaissance }} ({{ age }} ans)</li>
          </ul>

          <ul class="md:w-1/3 flex flex-col gap-2">
            <li><span class="font-bold">Promotion :</span> {{ props.etudiantSco.etudiant.promotion }}</li>
            <li><span class="font-bold">Numéro étudiant :</span> {{ props.etudiantSco.etudiant.num_etudiant }}</li>
            <li><span class="font-bold">Numéro INE :</span> {{ props.etudiantSco.etudiant.num_ine }}</li>
            <li><span class="font-bold">Login URCA :</span> {{ props.etudiantSco.etudiant.username }}</li>
          </ul>
        </div>
      </div>
      <Divider></Divider>
      <div>
        <h1 class="text-2xl font-bold mb-4 flex gap-4">Données personnelles <Button severity="warn" rounded variant="outlined" aria-label="Editer mes informations personnelles" icon="pi pi-user-edit" @click="isEditing = !isEditing"></Button></h1>
        <Message severity="info" class="mb-4" icon="pi pi-info-circle">
          Ces informations ne sont visibles que de vous et de la direction du département. Merci de maintenir ces informations à jour, elles seront utilisées pour vous faire parvenir vos relevés de notes.
        </Message>
        <div v-if="isEditing === true" class="flex md:flex-row flex-col gap-4 flex-wrap">
          <div class="md:w-1/3 flex flex-col gap-2">
            <IftaLabel>
              <InputText class="w-full" id="mailPerso" v-model="props.etudiantSco.etudiant.mailPerso" />
              <label for="mailPerso">Mail personnel</label>
            </IftaLabel>
            <IftaLabel>
              <InputText class="w-full" id="sitePerso" v-model="props.etudiantSco.etudiant.site_perso" />
              <label for="sitePerso">Site personnel</label>
              <div class="text-sm text-muted-color">Exemple : https://mon-site.com</div>
            </IftaLabel>
          </div>
          <div class="md:w-1/3 flex flex-col gap-2">
            <div class="flex flex-row gap-2 w-full justify-between">
              <IftaLabel>
                <InputText class="w-full" id="tel1" v-model="props.etudiantSco.etudiant.tel1" />
                <label for="tel1">Téléphone 1</label>
              </IftaLabel>
              <IftaLabel>
                <InputText class="w-full" id="tel2" v-model="props.etudiantSco.etudiant.tel2" />
                <label for="tel2">Téléphone 2</label>
              </IftaLabel>
            </div>
          </div>
          <div class="w-full">
            <div>Adresse Etudiante</div>
            <div class="flex gap-2 flex-wrap">
              <IftaLabel v-for="[key, value] in Object.entries(props.etudiantSco.etudiant.adresseEtudiante).slice(2)" :key="key">
                <InputText class="w-full" :id="key" v-model="props.etudiantSco.etudiant.adresseEtudiante[key]" />
                <label :for="key">{{ key }}</label>
              </IftaLabel>
            </div>
          </div>
          <div class="w-full">
            <div>Adresse Parentale</div>
            <div class="flex gap-2 flex-wrap">
              <IftaLabel v-for="[key, value] in Object.entries(props.etudiantSco.etudiant.adresseParentale).slice(2)" :key="key">
                <InputText class="w-full" :id="key" v-model="props.etudiantSco.etudiant.adresseParentale[key]" />
                <label :for="key">{{ key }}</label>
              </IftaLabel>
            </div>
          </div>
          <div class="flex justify-end w-full gap-2">
            <Button severity="secondary" @click="isEditing = false">Annuler</Button>
            <Button severity="primary" @click="updateEtudiantData()">Enregistrer</Button>
          </div>
        </div>
        <div v-else class="flex md:flex-row flex-col gap-4 flex-wrap">
          <ul class="md:w-1/3 flex flex-col gap-2">
            <li><span class="font-bold">Mail personnel :</span> {{props.etudiantSco.etudiant.mailPerso || 'Non renseigné'}}</li>
            <li><span class="font-bold">Site personnel :</span> <span v-if="props.etudiantSco.etudiant.site_perso"><Button as="a" label="Accéder au site" :href="props.etudiantSco.etudiant.site_perso" target="_blank" rel="noopener" icon="pi pi-external-link" icon-pos="right" severity="primary" size="small"/>
</span><span v-else>Non renseigné</span></li>
            <li><span class="font-bold">Adresse de l'étudiant :</span> {{ formatAdresse(props.etudiantSco.etudiant.adresseEtudiante) || 'Non renseigné' }}</li>
          </ul>

          <ul class="md:w-1/3 flex flex-col gap-2">
            <li><span class="font-bold">Téléphone :</span> {{ props.etudiantSco.etudiant.tel1 || 'Non renseigné' }} <span v-if="props.etudiantSco.etudiant.tel2">ou {{ props.etudiantSco.etudiant.tel2 }}</span></li>
            <li><span class="font-bold">Adresse parentale :</span> {{ formatAdresse(props.etudiantSco.etudiant.adresseParentale) || 'Non renseigné' }}</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <Divider></Divider>
  <div class="md:px-12 px-4">
    <h2 class="text-2xl font-bold">Scolarité</h2>
    <p class="text-sm text-muted-color">Sélectionnez une année universitaire pour afficher les semestres associés.</p>

    <Tabs v-if="etudiantScolarites.length > 0" v-model="activeTab">
      <TabList>
        <Tab v-for="scolarite in etudiantScolarites"
             :key="scolarite.anneeUniversitaire.libelle"
             :value="scolarite.anneeUniversitaire.libelle">
          {{ scolarite.anneeUniversitaire.libelle }}
        </Tab>
      </TabList>
      <TabPanels>
        <TabPanel v-for="scolarite in etudiantScolarites"
                  :key="scolarite.anneeUniversitaire.libelle"
                  :value="scolarite.anneeUniversitaire.libelle">
          <div class="flex md:flex-row flex-col justify-between gap-2 w-full h-full">
            <div v-for="semestre in scolarite.scolariteSemestre"
                 class="card mb-0 w-full h-full">
              <div class="font-bold text-lg">
                {{ semestre.semestre.annee.libelle }} -
                <span class="text-muted-color font-normal">{{ semestre.semestre.libelle }}</span>
              </div>

              {{ scolarite.moyennesUe }}
              <hr>
            </div>
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>

<style scoped>
.scol-profil {
  background-image: url("@/assets/illu/files.svg");
  background-repeat: no-repeat;
  background-size: cover;
  background-position: center;
}
</style>
