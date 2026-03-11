<script setup>
import { ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ValidatedInput, validationRules, ErrorView, PermissionGuard, ListSkeleton, Access } from "@components";
import { useAnneeUnivStore } from "@stores";
import { getAnneeUniversitaireService, updateAnneeUniversitaireService, getDiplomesService } from "@requests";

const route = useRoute();
const router = useRouter();

const hasError = ref(false);
const isLoading = ref(true);

const anneeUnivStore = useAnneeUnivStore();
const anneeUnivId = computed(() => route.params.id);
const anneeUniv = ref({
  libelle: "",
  annee: null,
  commentaire: "",
  actif: false,
});
const originalAnneeUniv = ref(null);

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
    if (!grouped[deptId]) {
      grouped[deptId] = {
        id: deptId,
        libelle: deptName,
        diplomes: []
      };
    }
    grouped[deptId].diplomes.push(diplome);
  });
  // Trier les départements par nom
  return Object.values(grouped).sort((a, b) => a.libelle.localeCompare(b.libelle));
});

onMounted(async () => {
  await loadAnneeUniv();
  await getDiplomes();
});

const loadAnneeUniv = async () => {
  try {
    isLoading.value = true;
    const data = await getAnneeUniversitaireService(anneeUnivId.value);
    anneeUniv.value = {
      libelle: data.libelle,
      annee: data.annee,
      commentaire: data.commentaire || "",
      actif: data.actif,
    };
    originalAnneeUniv.value = { ...anneeUniv.value };

    // Récupérer les diplômes déjà associés
    if (data.diplomes && Array.isArray(data.diplomes)) {
      selectedDiplomes.value = data.diplomes.map(d => d.id);
    }
  } catch (error) {
    console.error("Erreur lors de la récupération de l'année universitaire:", error);
    hasError.value = true;
  } finally {
    isLoading.value = false;
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

const updateAnneeUniv = async () => {
  try {
    const data = {
      libelle: anneeUniv.value.libelle,
      annee: anneeUniv.value.annee,
      commentaire: anneeUniv.value.commentaire,
      actif: anneeUniv.value.actif,
      diplomes: selectedDiplomes.value.map(id => `/api/structure_diplomes/${id}`)
    };

    await updateAnneeUniversitaireService(anneeUnivId.value, data, true);

    // Si l'année est devenue active, rafraîchir le store
    if (anneeUniv.value.actif) {
      await anneeUnivStore.getCurrentAnneeUniv();
    }

    await router.push('/super-administration/annees-universitaires');
  } catch (error) {
    console.error("Erreur lors de la mise à jour de l'année universitaire:", error);
    hasError.value = true;
  }
};

const getDiplomes = async () => {
  isLoadingDiplomes.value = true;
  try {
    // Récupérer tous les diplômes via le service (pas seulement ceux du département)
    diplomes.value = await getDiplomesService({});
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des diplômes:", error);
  } finally {
    isLoadingDiplomes.value = false;
  }
};

const cancel = () => {
  router.push('/super-administration/annees-universitaires');
};
</script>

<template>
  <div class="card">
    <div class="card-title mb-8">
      <h1 class="text-2xl font-bold">Modifier l'Année Universitaire</h1>
      <p class="text-muted-color">Modifiez les informations de l'année universitaire.</p>
    </div>

    <ErrorView v-if="hasError"/>
    <ListSkeleton v-else-if="isLoading" :count="5" />
    <template v-else>
      <PermissionGuard permission="isSuperAdmin" :showFallback="true">
        <Message v-if="anneeUniv.actif" severity="warn" class="mb-4 flex items-center flex-col justify-center text-center">
          <i class="pi pi-exclamation-triangle mr-2"></i>
          Cette année universitaire est actuellement <strong>active</strong>.
          La désactiver signifie qu'aucune année universitaire ne sera active.
        </Message>

        <form @submit.prevent="updateAnneeUniv()" class="flex flex-col">
          <div>
            <div class="flex flex-row gap-4 items-center">
              <ValidatedInput
                v-model="anneeUniv.libelle"
                name="libelle"
                label="Libellé"
                type="text"
                :rules="[validationRules.required]"
                @validation="result => handleValidation('libelle', result)"
                help-text="Entrez le libellé de l'année universitaire (ex: 2024-2025)"
                class="w-full"
              />

              <ValidatedInput
                v-model="anneeUniv.annee"
                name="annee"
                label="Année"
                type="number"
                :rules="[validationRules.required]"
                @validation="result => handleValidation('annee', result)"
                help-text="Sélectionnez l'année de début"
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
              <label class="block mb-2 font-medium">Année universitaire active ?</label>
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
              <small class="text-muted-color">Attention : activer cette année désactivera automatiquement l'année actuellement active.</small>
            </div>
          </div>

          <Divider></Divider>

          <div>
            <div class="card-title mb-4">
              <h1 class="text-xl font-bold">Gestion des diplômes</h1>
              <p class="text-muted-color">Modifiez les diplômes associés à cette année universitaire.</p>
            </div>
            <ListSkeleton v-if="isLoadingDiplomes" :count="3" class="mb-4" />
            <template v-else>
              <div v-if="diplomesByDepartement.length === 0" class="text-muted-color text-center py-4">
                Aucun diplôme disponible.
              </div>
              <Tabs v-else v-model:value="activeTabIndex">
                <TabList>
                  <Tab v-for="(dept, index) in diplomesByDepartement" :key="dept.id" :value="index">
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
                      <div class="p-2 border rounded mb-2 flex justify-between items-center">
                        <h3 class="font-semibold">{{ diplome.typeDiplome?.sigle || '' }} - {{ diplome.libelle }}</h3>
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

          <div class="flex justify-center items-center gap-4 mt-4">
            <Button label="Enregistrer les modifications" type="submit" :disabled="!formValid" />
            <Button label="Annuler" severity="secondary" @click="cancel" />
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

