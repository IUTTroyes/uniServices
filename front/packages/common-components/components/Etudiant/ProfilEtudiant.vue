<script setup>
import { ref, onMounted, computed } from "vue";
import { getEtudiantScolaritesService, getEtudiantScolariteSemestresService, updateEtudiantScolariteSemestreGroupes } from "@requests";
import { useToast } from "primevue/usetoast";
import { fr } from "date-fns/locale";
import { format, parseISO, differenceInYears } from "date-fns";
import { formatAdresse } from "@helpers/adresse.js";
import { updateEtudiantService, getUeService, getEtudiantService, getGroupesService } from "@requests";
import { ValidatedInput, validationRules, ErrorView, PermissionGuard } from "@components";
import { PhotoUser } from "@components";
import { useUsersStore } from "@stores";
import { hasPermission } from "@utils";
import { SimpleSkeleton } from "@components";
import noImage from "@images/photos_etudiants/noimage.png";

const toast = useToast();
const userStore = useUsersStore();

const props = defineProps({
  isVisible: Boolean,
  etudiantId: Number,
});

const isLoadingEtudiant = ref(false);
const etudiant = ref(null);
const etudiantPhoto = ref(noImage);
const isLoadingScolarites = ref(true);
const etudiantScolarites = ref([]);
const currentScolarite = ref(null);
const activeTab = ref(null);
const ueLabels = ref({});

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
    const params = {
      etudiant: props.etudiantId,
      _sort: 'anneeUniversitaire.annee:desc'
    };
    etudiantScolarites.value = await getEtudiantScolaritesService(params);

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

    for (const scolarite of etudiantScolarites.value) {
          // Récupérer les scolariteSemestre depuis le service et les injecter
          try {
            const params = { scolarite: scolarite.id };
            const semestres = await getEtudiantScolariteSemestresService(params);
            scolarite.scolariteSemestre = Array.isArray(semestres) ? semestres : (semestres?.data ?? []);
          } catch (error) {
            console.error("Erreur lors de la récupération des scolariteSemestre :", error);
            scolarite.scolariteSemestre = scolarite.scolariteSemestre ?? [];
          }

          for (const scolariteSemestre of scolarite.scolariteSemestre) {
            try {
              const params = { semestre: scolariteSemestre.semestre.id };
              const groupes = await getGroupesService(params, '/mini');
              scolariteSemestre.semestre.groupes = Array.isArray(groupes) ? groupes : (groupes?.data ?? []);
            } catch (error) {
              console.error("Erreur lors de la récupération des groupes du semestre :", error);
              scolariteSemestre.semestre.groupes = scolariteSemestre.semestre.groupes ?? [];
            }

            for (const groupe of scolariteSemestre.semestre.groupes) {
              if (groupe.type === 'CM') {
                scolariteSemestre.semestre.groupesCM = scolariteSemestre.semestre.groupesCM ?? [];
                scolariteSemestre.semestre.groupesCM.push(groupe);
              } else if (groupe.type === 'TD') {
                scolariteSemestre.semestre.groupesTD = scolariteSemestre.semestre.groupesTD ?? [];
                scolariteSemestre.semestre.groupesTD.push(groupe);
              } else if (groupe.type === 'TP') {
                scolariteSemestre.semestre.groupesTP = scolariteSemestre.semestre.groupesTP ?? [];
                scolariteSemestre.semestre.groupesTP.push(groupe);
              }
            }
          }
        }

  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération :", error);
  } finally {
    isLoadingScolarites.value = false;
    currentScolarite.value = etudiantScolarites.value.find(sco => sco.actif) || null;
    console.log(etudiantScolarites.value);
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

const getUe = (ueId) => {
  if (ueLabels.value[ueId]) {
    return ueLabels.value[ueId];
  }

  ueLabels.value[ueId] = "Chargement...";

  getUeService(ueId)
      .then(ue => {
        ueLabels.value[ueId] = ue.numero;
      })
      .catch(error => {
        console.error("Erreur lors de la récupération de l'UE:", error);
        ueLabels.value[ueId] = "UE non trouvée";
      });

  return ueLabels.value[ueId];
}

const handleGroupSelection = async (scolariteSemestre, groupId, groupType) => {
  try {
    // retrouver le type de groupe sélectionné
    const group = scolariteSemestre.semestre.groupes.find(g => g.id === groupId);
    const groupType = group ? group.type : null;
    await updateEtudiantScolariteSemestreGroupes(scolariteSemestre.id, { id: groupId, type: groupType }, true);
  } catch (error) {
    console.error("Erreur lors de la sélection du groupe :", error);
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

            <!-- Sensitive information only visible to authorized users -->
            <PermissionGuard :permission="['isEtudiant', 'canViewEtudiantDetails']">
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
        <div class="md:px-12 px-4">
          <div class="flex justify-between gap-4 w-full items-center">
            <div>
              <h2 class="text-2xl font-bold">Scolarité</h2>
              <p class="text-sm text-muted-color">Années universitaires dans lesquelles l'étudiant est ou a été inscrit</p>
            </div>

            <div>
            <Button v-if="currentScolarite" label="Plus d'infos sur la scolarité de l'étudiant" />
              <p class="text-sm text-muted-color">Bilans, notes, absences ...</p>
            </div>

          </div>
          <Tabs v-if="etudiantScolarites.length > 0" :value="activeTab">
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
                  <div v-for="scoSemestre in scolarite.scolariteSemestre"
                       class="card mb-0 w-1/2">
                    <div class="font-bold text-lg">
                      {{ scoSemestre.semestre.annee.libelle }} |
                      <span class="text-muted-color font-normal">{{ scoSemestre.semestre.libelle }}</span>
                    </div>
                    <Divider/>
                    <div class="text-lg font-medium opacity-70 mb-4">Groupes</div>
                    <div class="flex justify-between gap-2 w-full">
                      <!-- Groupe CM -->
                      <div class="w-1/3 border p-2 rounded-md flex flex-col gap-2">
                        <Tag severity="info">CM</Tag>
                        <ValidatedInput
                            type="select"
                            v-model="scoSemestre.semestre.selectedGroupCM"
                            name="groupeCM"
                            label=""
                            :options="scoSemestre.semestre.groupesCM?.map(groupe => ({ label: groupe.libelle, value: groupe.id })) ?? []"
                            :modelValue="scoSemestre.groupes.find(g => g.type === 'CM')?.id || scoSemestre.semestre.selectedGroupCM"
                            :placeholder="scoSemestre.groupes?.find(g => g.type === 'CM')?.libelle ?? 'Sélectionner un groupe CM'"
                            @validation="result => handleValidation('groupeCM', result)"
                            @update:modelValue="groupId => handleGroupSelection(scoSemestre, groupId)"
                            class="!m-0"
                        />
                      </div>
                      <!-- Groupe TD -->
                      <div class="w-1/3 border p-2 rounded-md flex flex-col gap-2">
                        <Tag severity="warning">TD</Tag>
                        <ValidatedInput
                            type="select"
                            v-model="scoSemestre.semestre.selectedGroupTD"
                            name="groupeTD"
                            label=""
                            :options="scoSemestre.semestre.groupesTD?.map(groupe => ({ label: groupe.libelle, value: groupe.id })) ?? []"
                            :modelValue="scoSemestre.groupes.find(g => g.type === 'TD')?.id || scoSemestre.semestre.selectedGroupTD"
                            :placeholder="scoSemestre.groupes?.find(g => g.type === 'TD')?.libelle ?? 'Sélectionner un groupe TD'"
                            @validation="result => handleValidation('groupeTD', result)"
                            @update:modelValue="groupId => handleGroupSelection(scoSemestre, groupId)"
                            class="!m-0"
                        />
                      </div>
                      <!-- Groupe TP -->
                      <div class="w-1/3 border p-2 rounded-md flex flex-col gap-2">
                        <Tag severity="success">TP</Tag>
                        <ValidatedInput
                            type="select"
                            v-model="scoSemestre.semestre.selectedGroupTP"
                            name="groupeTP"
                            label=""
                            :options="scoSemestre.semestre.groupesTP?.map(groupe => ({ label: groupe.libelle, value: groupe.id })) ?? []"
                            :modelValue="scoSemestre.groupes.find(g => g.type === 'TP')?.id || scoSemestre.semestre.selectedGroupTP"
                            :placeholder="scoSemestre.groupes?.find(g => g.type === 'TP')?.libelle ?? 'Sélectionner un groupe TP'"
                            @validation="result => handleValidation('groupeTP', result)"
                            @update:modelValue="groupId => handleGroupSelection(scoSemestre, groupId)"
                            class="!m-0"
                        />
                      </div>
                    </div>
                    <Divider></Divider>
                    <div class="text-lg font-medium opacity-70 mb-4">Bilan du semestre</div>
                    <div class="flex">
                      <table class="table-auto w-full">
                        <thead>
                        <tr>
                          <th class="text-left px-2 py-1">UE</th>
                          <th class="text-left px-2 py-1">Moyenne</th>
                          <th class="text-left px-2 py-1">Décision</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(moyenneUe, key) in scoSemestre.moyennesUe" :key="key">
                          <td class="px-2 py-1">UE {{ getUe(key) }}</td>
                          <td class="px-2 py-1">{{ moyenneUe.moyenne || 'Non renseigné' }}</td>
                          <td class="px-2 py-1">
                            <span v-if="moyenneUe.decision === 'V'"><Tag severity="success">V</Tag></span>
                            <span v-else-if="moyenneUe.decision === 'NV'"><Tag severity="danger">NV</Tag></span>
                            <span v-else><Tag severity="warning">En attente</Tag></span>
                          </td>
                        </tr>
                        </tbody>
                      </table>
                      <table class="table-auto w-full">
                        <thead>
                        <tr>
                          <th class="text-left px-2 py-1">Décision semestre</th>
                          <th class="text-left px-2 py-1">Proposition</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                          <td class="px-2 py-1">
                            <span v-if="scoSemestre.decision === true"><Tag severity="success">V</Tag></span>
                            <span v-else-if="scoSemestre.decision === false"><Tag severity="danger">NV</Tag></span>
                            <span v-else><Tag severity="warning">En attente</Tag></span>
                          </td>
                          <td class="px-2 py-1">{{ scoSemestre.proposition?.libelle || 'Non renseigné' }}</td>
                        </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </TabPanel>
            </TabPanels>
          </Tabs>
        </div>
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
