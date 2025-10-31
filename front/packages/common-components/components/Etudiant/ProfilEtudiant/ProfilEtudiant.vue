<script setup>
import { ref, onMounted, computed } from "vue";
import { getEtudiantScolaritesService, getEtudiantScolariteSemestresService } from "@requests/index.js";
import { useToast } from "primevue/usetoast";
import { fr } from "date-fns/locale";
import { format, parseISO, differenceInYears } from "date-fns";
import { formatAdresse } from "@helpers/adresse.js";
import { updateEtudiantService, getUeService, getEtudiantService, getGroupesService } from "@requests/index.js";
import { ValidatedInput, validationRules, ErrorView, PermissionGuard } from "@components/index.js";
import { PhotoUser } from "@components/index.js";
import { useUsersStore } from "@stores/index.js";
import { hasPermission } from "@utils";
import { SimpleSkeleton } from "@components/index.js";
import noImage from "@images/photos_etudiants/noimage.png";
import { ScolariteEtudiant } from "@components";

const toast = useToast();
const userStore = useUsersStore();

const props = defineProps({
  isVisible: Boolean,
  etudiantId: Number,
});

const isLoadingEtudiant = ref(false);
const etudiant = ref(null);
const isLoadingScolarites = ref(true);
const etudiantScolarites = ref([]);
const currentScolarite = ref(null);
const activeTab = ref(null);

const isEditing = ref(false);
const formErrors = ref({});
const formValid = ref(true);

const hasError = ref(false);

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };
  formValid.value = Object.values(formErrors.value).every(error => error === null);
};

const initializeAddressObjects = () => {
  formErrors.value = {};
  formValid.value = true;

  // Initialize address objects if they don't exist
  if (!etudiant.value.adresseEtudiante) {
    etudiant.value.adresseEtudiante = {
      adresse: "",
      complement1: "",
      complement2: "",
      ville: "",
      codePostal: "",
      pays: ""
    };
  }

  if (!etudiant.value.adresseParentale) {
    etudiant.value.adresseParentale = {
      adresse: "",
      complement1: "",
      complement2: "",
      ville: "",
      codePostal: "",
      pays: ""
    };
  }
};

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

const getEtudiantDetails = async (etudiantId) => {
  try {
    isLoadingEtudiant.value = true;
    etudiant.value = await getEtudiantService(etudiantId);
  } catch (error) {
    console.error("Erreur lors de la récupération des détails de l'étudiant :", error);
    throw error;
  } finally {
    isLoadingEtudiant.value = false;
  }
};

const getEtudiantScolarites = async () => {
  isLoadingScolarites.value = true;
  try {
    const params = { etudiant: props.etudiantId, _sort: 'anneeUniversitaire.annee:asc' };
    etudiantScolarites.value = await getEtudiantScolaritesService(params);

    // Trier et définir l'onglet actif
    etudiantScolarites.value.sort((a, b) => b.anneeUniversitaire.annee - a.anneeUniversitaire.annee);
    activeTab.value = etudiantScolarites.value[0]?.anneeUniversitaire.libelle || null;

    for (const scolarite of etudiantScolarites.value) {
      try {
        const semestres = await getEtudiantScolariteSemestresService({ scolarite: scolarite.id });
        scolarite.scolariteSemestre = Array.isArray(semestres) ? semestres : (semestres?.data ?? []);
      } catch {
        scolarite.scolariteSemestre = [];
      }

      for (const scolariteSemestre of scolarite.scolariteSemestre) {
        try {
          const groupes = await getGroupesService({ semestre: scolariteSemestre.semestre.id }, '/mini');
          scolariteSemestre.semestre.groupes = Array.isArray(groupes) ? groupes : (groupes?.data ?? []);
        } catch {
          scolariteSemestre.semestre.groupes = [];
        }

        // Organiser les groupes par type
        ['CM', 'TD', 'TP', 'Spécial', 'Autre'].forEach(type => {
          const groupesType = scolariteSemestre.semestre.groupes.filter(g => g.type === type);
          if (groupesType.length > 0) {
            scolariteSemestre.semestre[`groupes${type}`] = groupesType;
          } else {
            scolariteSemestre.semestre[`groupes${type}`] = [];
          }
        });
      }
    }
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération :", error);
  } finally {
    isLoadingScolarites.value = false;
    currentScolarite.value = etudiantScolarites.value.find(sco => sco.actif) || null;
  }
};

onMounted(async () => {
  await getEtudiantDetails(props.etudiantId);
  await getEtudiantScolarites();
});

// Formater la date de naissance
const formattedDateNaissance = computed(() => {
  if (!etudiant.value.date_naissance) return "Non spécifiée";
  return format(parseISO(etudiant.value.date_naissance), "dd/MM/yyyy", { locale: fr });
});

// Calculer l'âge
const age = computed(() => {
  if (!etudiant.value.date_naissance) return "Âge inconnu";
  return differenceInYears(new Date(), parseISO(etudiant.value.date_naissance));
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
  // Vérifier si le formulaire est valide avant de soumettre
  if (!formValid.value) {
    toast.add({
      severity: "error",
      summary: "Erreur de validation",
      detail: "Veuillez corriger les erreurs dans le formulaire",
      life: 5000,
    });
    return;
  }

  try {
    const cleanedEtudiant = cleanEtudiantObject(etudiant.value);
    await updateEtudiantService(cleanedEtudiant);
  } catch (error) {
    console.error("Erreur lors de la mise à jour :", error);
  } finally {
    isEditing.value = false;
  }
};
</script>

<template>
  <ErrorView v-if="hasError" message="Une erreur est survenue lors du chargement des données."></ErrorView>
  <SimpleSkeleton v-else-if="isLoadingEtudiant || isLoadingScolarites" />
  <div v-else>
    <div class="flex items-stretch md:flex-row flex-col gap-6 md:px-12">
      <div class="flex flex-col gap-2 justify-center md:w-1/3 w-full h-auto p-4 mb-0 card scol-profil">
        <div class="w-full flex justify-center">
          <PhotoUser :user-photo="etudiant.photoName" class="rounded-full w-40 h-auto border-8 border-gray-300 border-opacity-60"/>
        </div>
        <div class="flex flex-col gap-2 p-4 bg-surface-0 shadow-md dark:bg-surface-800 dark:border-gray-700 rounded-md">
          <div class="text-center font-bold flex justify-center gap-1">
            <div class="first-letter:uppercase lowercase">{{ etudiant.prenom }}</div>
            <div class="uppercase">{{ etudiant.nom }}</div>
          </div>
          <div class="text-center underline hover:cursor-pointer" @click="copyToClipboard(etudiant.mailUniv)">
            {{ etudiant.mailUniv }} <i class="pi pi-copy"></i>
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
              <li><span class="font-bold">Prénom :</span> {{etudiant.prenom}}</li>
              <li><span class="font-bold">Nom :</span> {{etudiant.nom}}</li>
              <li><span class="font-bold">Date de naissance :</span> {{ formattedDateNaissance }} ({{ age }} ans)</li>
            </ul>

            <ul class="md:w-1/3 flex flex-col gap-2">
              <li><span class="font-bold">Promotion :</span> {{ etudiant.promotion }}</li>
              <li><span class="font-bold">Numéro étudiant :</span> {{ etudiant.num_etudiant }}</li>
              <li><span class="font-bold">Numéro INE :</span> {{ etudiant.num_ine }}</li>
              <li><span class="font-bold">Login URCA :</span> {{ etudiant.username }}</li>
            </ul>
          </div>
        </div>
        <Divider></Divider>
        <div>
          <h1 class="text-2xl font-bold mb-4 flex gap-4">
            Données personnelles
            <!-- Edit button only visible to the student themselves or users with edit permissions -->
            <Button
                severity="warn"
                rounded
                variant="outlined"
                aria-label="Editer mes informations personnelles"
                icon="pi pi-user-edit"
                @click="() => { initializeAddressObjects(); isEditing = !isEditing; }">
            </Button>
          </h1>
          <Message severity="info" class="mb-4" icon="pi pi-info-circle">
            <span v-if="userStore.isEtudiant">
              Ces informations ne sont visibles que de vous et de la direction du département. Merci de maintenir ces informations à jour, elles seront utilisées pour vous faire parvenir vos relevés de notes.
            </span>
            <span v-else>
              Ces informations sont confidentielles et ne doivent être utilisées que dans le cadre de vos fonctions.
            </span>
          </Message>
          <div v-if="isEditing === true" class="flex md:flex-row flex-col gap-4 flex-wrap">
            <div class="text-sm font-normal text-red-500 w-full">Les champs marqués d'un * sont obligatoires</div>
            <div class="md:w-1/3 flex flex-col gap-2">
              <ValidatedInput
                  v-model="etudiant.mailPerso"
                  name="mailPerso"
                  label="Mail personnel"
                  :rules="validationRules.email"
                  @validation="result => handleValidation('mailPerso', result)"
              />
              <ValidatedInput
                  v-model="etudiant.site_perso"
                  name="sitePerso"
                  label="Site personnel"
                  :rules="validationRules.url"
                  @validation="result => handleValidation('sitePerso', result)"
                  help-text="Exemple : https://mon-site.com"
              />
            </div>
            <div class="md:w-1/3 flex flex-col gap-2">
              <div class="flex flex-row gap-2 w-full justify-between">
                <div class="w-1/2">
                  <ValidatedInput
                      v-model="etudiant.tel1"
                      name="tel1"
                      label="Téléphone 1"
                      :rules="validationRules.phone"
                      @validation="result => handleValidation('tel1', result)"
                  />
                </div>
                <div class="w-1/2">
                  <ValidatedInput
                      v-model="etudiant.tel2"
                      name="tel2"
                      label="Téléphone 2"
                      :rules="validationRules.phone"
                      @validation="result => handleValidation('tel2', result)"
                  />
                </div>
              </div>
            </div>
            <div class="w-full">
              <div class="font-bold mb-2">Adresse Etudiante</div>
              <div class="flex gap-2 flex-wrap">
                <div v-for="field in ['adresse', 'complement1', 'complement2', 'ville', 'pays']" :key="field" class="md:w-1/4 w-full">
                  <ValidatedInput
                      v-model="etudiant.adresseEtudiante[field]"
                      :name="'etudiant-'+field"
                      :label="field"
                      :rules="field === 'adresse' || field === 'ville' ? ['required'] : null"
                      @validation="result => handleValidation('etudiant-'+field, result)"
                  />
                </div>
                <div class="md:w-1/4 w-full">
                  <ValidatedInput
                      v-model="etudiant.adresseEtudiante.codePostal"
                      name="etudiant-codePostal"
                      label="codePostal"
                      :rules="[validationRules.postalCode, validationRules.required]"
                      @validation="result => handleValidation('etudiant-codePostal', result)"
                  />
                </div>
              </div>
            </div>
            <div class="w-full">
              <div class="font-bold mb-2">Adresse Parentale</div>
              <div class="flex gap-2 flex-wrap">
                <div v-for="field in ['adresse', 'complement1', 'complement2', 'ville', 'pays']" :key="field" class="md:w-1/4 w-full">
                  <ValidatedInput
                      v-model="etudiant.adresseParentale[field]"
                      :name="'parental-'+field"
                      :label="field"
                      @validation="result => handleValidation('parental-'+field, result)"
                  />
                </div>
                <div class="md:w-1/4 w-full">
                  <ValidatedInput
                      v-model="etudiant.adresseParentale.codePostal"
                      name="parental-codePostal"
                      label="codePostal"
                      :rules="validationRules.postalCode"
                      @validation="result => handleValidation('parental-codePostal', result)"
                  />
                </div>
              </div>
            </div>
            <div class="w-full mt-4">
              <Message v-if="!formValid" severity="error">
                Veuillez corriger les erreurs dans le formulaire avant de soumettre
              </Message>
            </div>
            <div class="flex justify-end w-full gap-2 mt-2">
              <Button severity="secondary" @click="isEditing = false">Annuler</Button>
              <Button severity="primary" :disabled="!formValid" @click="updateEtudiantData()">Enregistrer</Button>
            </div>
          </div>
          <div v-else class="flex md:flex-row flex-col gap-4 flex-wrap">
            <ul class="md:w-1/3 flex flex-col gap-2">
              <li><span class="font-bold">Mail personnel :</span> {{etudiant.mailPerso || 'Non renseigné'}}</li>
              <li><span class="font-bold">Site personnel :</span> <span v-if="etudiant.site_perso"><Button as="a" label="Accéder au site" :href="etudiant.site_perso" target="_blank" rel="noopener" icon="pi pi-external-link" icon-pos="right" severity="primary" size="small"/>
</span><span v-else>Non renseigné</span></li>
            </ul>

            <PermissionGuard :permission="userStore.user.id === etudiant.id || ['isEtudiant', 'canViewEtudiantDetails']">
              <div class="flex md:flex-row flex-col gap-4 flex-wrap w-full">
                <ul class="md:w-1/3 flex flex-col gap-2">
                  <li><span class="font-bold">Adresse de l'étudiant :</span> {{ formatAdresse(etudiant.adresseEtudiante) || 'Non renseigné' }}</li>
                </ul>

                <ul class="md:w-1/3 flex flex-col gap-2">
                  <li><span class="font-bold">Téléphone :</span> {{ etudiant.tel1 || 'Non renseigné' }} <span v-if="etudiant.tel2">ou {{ etudiant.tel2 }}</span></li>
                  <li><span class="font-bold">Adresse parentale :</span> {{ formatAdresse(etudiant.adresseParentale) || 'Non renseigné' }}</li>
                </ul>
              </div>

              <template #fallback>
                <div class="w-full text-sm text-muted-color italic">
                  <p>Des informations supplémentaires sont disponibles pour les utilisateurs autorisés.</p>
                </div>
              </template>
            </PermissionGuard>
          </div>
        </div>
      </div>
    </div>
    <Divider></Divider>
    <PermissionGuard :permission="userStore.user.id === etudiant.id">
    <ScolariteEtudiant :etudiantId="props.etudiantId"/>
    </PermissionGuard>
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
