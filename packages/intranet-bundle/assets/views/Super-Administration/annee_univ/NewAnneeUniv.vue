<script setup>
import {ref, onMounted, computed} from "vue";
import { ValidatedInput, validationRules, ErrorView, PermissionGuard, ListSkeleton, Access } from "@components";
import {useAnneeUnivStore} from "@stores";
import {createAnneeUniversitaireService, getDiplomesService} from "@requests";
import router from "@/router/index.js";

const hasError = ref(false);

const anneeUnivStore = useAnneeUnivStore();
const anneeUniv = ref({
  libelle: "",
  actif: false,
});
const currentAnneeUniv = ref(null);
const formValid = ref(true);
const formErrors = ref(null);
const activeChoice = ref([
  {
    label: "Non",
    value: false
  },
  {
    label: "Oui",
    value: true
  }
]);

const diplomes = ref([]);
const selectedDiplomes = ref([]);
const isLoadingDiplomes = ref(false);
const activeTabIndex = ref(0);

// Grouper les diplômes par département
const diplomesByDepartement = computed(() => {
  const grouped = {};
  diplomes.value.forEach(diplome => {
    const deptName = diplome.departement?.libelle || 'Sans département';
    const deptId = diplome.departement?.id || 'none';
    const deptActif = diplome.departement?.actif || false;
    if (!grouped[deptId]) {
      grouped[deptId] = {
        id: deptId,
        libelle: deptName,
        diplomes: [],
        actif: deptActif,
      };
    }
    grouped[deptId].diplomes.push(diplome);
  });
  // Trier les départements par nom
  return Object.values(grouped).sort((a, b) => a.libelle.localeCompare(b.libelle));
});


onMounted(async () => {
  await getCurrentAnneeUniv();
  await getDiplomes();
});

const getCurrentAnneeUniv = async () => {
  try {
    await anneeUnivStore.getCurrentAnneeUniv();
    currentAnneeUniv.value = await anneeUnivStore.anneeUniv;
    if (currentAnneeUniv.value) {
      anneeUniv.value.annee = currentAnneeUniv.value.annee + 1; // Proposer l'année suivante

      // Calcul du libellé de l'année suivante (ex: "2025-2026" -> "2026-2027")
      const [anneeDebut, anneeFin] = currentAnneeUniv.value.libelle.split('-').map(Number);
      anneeUniv.value.libelle = `${anneeDebut + 1}-${anneeFin + 1}`;
    }
  } catch (error) {
    console.error("Erreur lors de la récupération de l'année universitaire actuelle:", error);
    hasError.value = true;
  } finally {
    handleValidation('libelle', { isValid: !!anneeUniv.value.libelle, errorMessage: "Le libellé est requis." });
    handleValidation('annee', { isValid: !!anneeUniv.value.annee, errorMessage: "L'année est requise." });
  }
};

const handleValidation = (field, result) => {
  formErrors.value = {
    ...formErrors.value,
    [field]: result.isValid ? null : result.errorMessage
  };
  formValid.value = Object.values(formErrors.value).every(error => error === null);
};

const toggleDiplome = (diplomeId) => {
  const index = selectedDiplomes.value.indexOf(diplomeId);
  if (index > -1) {
    selectedDiplomes.value.splice(index, 1);
  } else {
    selectedDiplomes.value.push(diplomeId);
  }
};

const isDiplomeSelected = (diplomeId) => {
  return selectedDiplomes.value.includes(diplomeId);
};

const createAnneeUniv = async () => {
  try {
    const data = {
      libelle: anneeUniv.value.libelle,
      annee: anneeUniv.value.annee,
      commentaire: anneeUniv.value.commentaire,
      actif: anneeUniv.value.actif,
      diplomes: selectedDiplomes.value.map(id => `/api/structure_diplomes/${id}`)
    };
    // Appeler le service pour créer l'année universitaire
    await createAnneeUniversitaireService(data, true)
    console.log("Année universitaire créée:", anneeUniv.value);
  } catch (error) {
    console.error("Erreur lors de la création de l'année universitaire:", error);
  } finally {
    if (anneeUniv.value.actif) {
      await anneeUnivStore.getCurrentAnneeUniv();
    }
    await router.push('/')
  }
};

const getDiplomes = async () => {
  isLoadingDiplomes.value = true;
  try {
    // Récupérer tous les diplômes via le service (pas seulement ceux du département)
    diplomes.value = await getDiplomesService({});
    // Par défaut, aucun diplôme n'est sélectionné
    selectedDiplomes.value = [];
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des diplômes:", error);
  } finally {
    isLoadingDiplomes.value = false;
  }
};
</script>

<template>
  <div class="card">
    <div class="card-title mb-8">
      <h1 class="text-2xl! mb-0! font-bold">Nouvelle année universitaire</h1>
      <p class="text-muted-color">Créez une nouvelle année universitaire.</p>
    </div>

    <ErrorView v-if="hasError"/>
    <template v-else>
      <PermissionGuard permission="isSuperAdmin" :showFallback="true">
        <Message v-if="currentAnneeUniv" severity="info" class="mb-4 flex items-center flex-col justify-center text-center">
          L'année universitaire actuelle est : <strong>{{ currentAnneeUniv.libelle }}</strong>.
          <br>
          Créer une année universitaire va lancer la synchronisation des données pour la nouvelle année, depuis ORéOF. Vous recevrez une notification lorsque la synchronisation sera terminée.
        </Message>

        <form @submit.prevent="createAnneeUniv()" class="flex flex-col">
          <div>
            <div class="flex flex-row gap-4 items-center">
              <ValidatedInput
                  v-model="anneeUniv.libelle"
                  name="libelle"
                  label="Libellé"
                  type="text"
                  :rules="[validationRules.required]"
                  @validation="result => handleValidation('libelle', result)"
                  help-text="Entrez le libellé de l'évaluation (ex: 2024-2025)"
                  class="w-full"
              />

              <ValidatedInput
                  v-model="anneeUniv.annee"
                  name="annee"
                  label="Année"
                  type="number"
                  :rules="[validationRules.required]"
                  @validation="result => handleValidation('annee', result)"
                  help-text="Sélectionnez la date de début de l'année universitaire"
                  class="w-full"
              />
            </div>

            <ValidatedInput
                v-model="anneeUniv.commentaire"
                name="commentaire"
                label="Commentaire"
                type="textarea"
                :rules="[]"
                @validation="result => handleValidation('commentaire', result)"
                help-text="Entrez un commentaire optionnel pour cette année universitaire"
            />

            <div class="mb-4">
              <label class="block mb-2 font-medium">Définir l'année universitaire comme active après sa création ?</label>
              <div class="flex gap-4">
                <div v-for="choice in activeChoice" :key="choice.value" class="flex items-center gap-2">
                  <RadioButton
                      v-model="anneeUniv.actif"
                      :inputId="`actif-${choice.value}`"
                      name="actif"
                      :value="choice.value"
                  />
                  <label :for="`actif-${choice.value}`">{{ choice.label }}</label>
                </div>
              </div>
            </div>
          </div>

          <Divider></Divider>

          <div>
            <div class="card-title mb-4">
              <h1 class="text-xl! mb-0! font-bold">Gestion des diplômes</h1>
              <p class="text-muted-color">Définissez quels diplômes seront associés à la nouvelle année universitaire.</p>
            </div>
            <ListSkeleton v-if="isLoadingDiplomes" :count="3" class="mb-4" />
            <template v-else>
              <div v-if="diplomesByDepartement.length === 0" class="text-muted-color text-center py-4">
                Aucun diplôme disponible.
              </div>
              <Tabs v-else v-model:value="activeTabIndex">
                <TabList>
                  <Tab v-for="(dept, index) in diplomesByDepartement" :key="dept.id" :value="index" :class="!dept.actif ? '!text-red-500' : ''">
                    {{ dept.libelle }}
                    <Badge
                      :value="dept.diplomes.filter(d => isDiplomeSelected(d.id)).length + '/' + dept.diplomes.length"
                      :severity="dept.diplomes.filter(d => isDiplomeSelected(d.id)).length > 0 ? 'success' : 'secondary'"
                      class="ml-2"
                    />
                  </Tab>
                </TabList>
                <TabPanels>
                  <TabPanel v-for="(dept, index) in diplomesByDepartement" :key="dept.id" :value="index">
                    <div v-for="diplome in dept.diplomes" :key="diplome.id">
                      <div class="p-2 border border-neutral-200 dark:border-neutral-700 rounded-md mb-2 flex justify-between items-center">
                        <p class="font-semibold mb-0!">{{ diplome.typeDiplome?.sigle || '' }} - {{ diplome.libelle }}</p>
                        <ToggleButton
                            :modelValue="isDiplomeSelected(diplome.id)"
                            @update:modelValue="toggleDiplome(diplome.id)"
                            onLabel="Associé"
                            offLabel="Non associé"
                            onIcon="pi pi-check"
                            offIcon="pi pi-times"
                        />
                      </div>
                    </div>
                  </TabPanel>
                </TabPanels>
              </Tabs>
            </template>
          </div>

          <div class="flex justify-center items-center gap-4">
            <Button label="Créer l'année universitaire" @click="createAnneeUniv" :disabled="!formValid" />
            <Button label="Annuler" severity="secondary"/>
          </div>
        </form>

        <template #fallback>
          <Access></Access>
        </template>
      </PermissionGuard>
    </template>
  </div>
</template>

<style scoped>

</style>
