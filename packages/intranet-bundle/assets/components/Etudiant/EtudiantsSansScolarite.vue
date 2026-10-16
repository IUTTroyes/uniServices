<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { HeaderComponent, ProfilEtudiant } from '@components';
import ButtonInfo from '@components/components/Buttons/ButtonInfo.vue';
import { useToast } from 'primevue/usetoast';
import {
  getEtudiantsService,
  createEtudiantService,
  getAnneesUniversitairesService,
  getAnneesService,
  getGroupesService,
  getEtudiantScolaritesService,
  getEtudiantScolariteSemestresService,
  updateEtudiantScolariteSemestreService,
} from '@requests';
import { useUsersStore } from '@stores';
import { useEtudiantFilters } from '../../../../../shared/composables/filters/usersFilters/useEtudiantFilters.ts';

const toast = useToast();
const userStore = useUsersStore();
const loading = ref(false);
const hasError = ref(false);
const savingStep = ref(false);
const currentStep = ref(1);

const etudiants = ref([]);
const selectedEtudiants = ref([]);
const selectedStepTwoIds = ref([]);

const anneesUniversitaires = ref([]);
const annees = ref([]);
const selectedAnneeUniversitaire = ref(null);
const selectedAnnee = ref(null);
const isLoadingAnneesUniversitaires = ref(true);
const isLoadingAnnees = ref(true);
const savedDestination = ref(null);

const etudiantScolariteSemestres = ref([]);
const groupesParSemestre = ref({});
const stepThreeSelections = ref({});
const integrationDone = ref(false);

const showViewDialog = ref(false);
const selectedEtudiant = ref(null);

const { filters, watchChanges } = useEtudiantFilters();
const FILTERS_DEBOUNCE_MS = 250;
let filtersDebounceTimeout = null;

const canSaveStepOne = computed(() => !!selectedAnneeUniversitaire.value?.id && !!selectedAnnee.value?.id);
const canSaveStepTwo = computed(() => selectedEtudiants.value.length > 0);

const getStepThreeSelectionKey = (scolariteSemestreId, type) => `${scolariteSemestreId}::${type}`;

const sanitizeFilters = () => ({
  nom: filters.value.nom,
  prenom: filters.value.prenom,
  mailUniv: filters.value.mailUniv,
});

const resetStepThree = () => {
  integrationDone.value = false;
  etudiantScolariteSemestres.value = [];
  groupesParSemestre.value = {};
  stepThreeSelections.value = {};
};

const getEtudiantsSansScolarite = async () => {
  loading.value = true;
  hasError.value = false;
  try {
    const params = {
      filters: sanitizeFilters(),
      sansScolarite: true,
      itemsPerPage: 500,
      page: 1,
    };

    etudiants.value = await getEtudiantsService(params);

    const validIds = new Set(etudiants.value.map(e => e.id));
    selectedEtudiants.value = selectedEtudiants.value.filter(e => validIds.has(e.id));
  } catch (error) {
    console.error('Erreur lors du chargement des étudiants sans scolarité :', error);
    hasError.value = true;
  } finally {
    loading.value = false;
  }
};

const getAnneesUniversitaires = async () => {
  try {
    isLoadingAnneesUniversitaires.value = true;
    const params = {
      since: new Date().getFullYear(),
    };
    anneesUniversitaires.value = await getAnneesUniversitairesService(params, '', false);
    selectedAnneeUniversitaire.value = anneesUniversitaires.value.find(annee => annee.actif) ?? null;
  } catch (error) {
    console.error('Erreur lors du chargement des années universitaires :', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les années universitaires.', life: 3000 });
  } finally {
    isLoadingAnneesUniversitaires.value = false;
  }
};

const getAnnees = async () => {
  try {
    isLoadingAnnees.value = true;
    const params = {
      departement: userStore.departementDefaut.id,
      actif: true,
    };
    annees.value = await getAnneesService(params, '', false);
  } catch (error) {
    console.error('Erreur lors du chargement des années :', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les années.', life: 3000 });
  } finally {
    isLoadingAnnees.value = false;
  }
};

const loadInitData = async () => {
  await Promise.all([getAnneesUniversitaires(), getAnnees()]);
};


const viewEtudiant = etudiant => {
  selectedEtudiant.value = etudiant;
  showViewDialog.value = true;
};

const saveStepOne = async () => {
  if (!canSaveStepOne.value) return;
  savingStep.value = true;
  try {
    savedDestination.value = {
      anneeUniversitaire: selectedAnneeUniversitaire.value,
      annee: selectedAnnee.value,
    };
    currentStep.value = 2;
    toast.add({ severity: 'success', summary: 'Étape sauvegardée', detail: 'Année de destination enregistrée.', life: 2500 });
  } finally {
    savingStep.value = false;
  }
};

const saveStepTwo = async () => {
  if (!canSaveStepTwo.value) return;
  savingStep.value = true;
  try {
    selectedStepTwoIds.value = selectedEtudiants.value.map(e => e.id);
    resetStepThree();
    currentStep.value = 3;
    toast.add({ severity: 'success', summary: 'Étape sauvegardée', detail: 'Sélection des étudiants enregistrée.', life: 2500 });
  } finally {
    savingStep.value = false;
  }
};

const loadStepThreeData = async () => {
  if (!savedDestination.value?.anneeUniversitaire?.id) return;

  const rows = [];
  const semestresSet = new Set();

  for (const etudiantId of selectedStepTwoIds.value) {
    const scolarites = await getEtudiantScolaritesService({
      etudiant: etudiantId,
      anneeUniversitaire: savedDestination.value.anneeUniversitaire.id,
    }, '', false);

    if (!scolarites.length) continue;
    const scolarite = scolarites[0];

    const semestres = await getEtudiantScolariteSemestresService({ scolarite: scolarite.id }, '', false);
    semestres.forEach((sem) => {
      rows.push(sem);
      if (sem.semestre?.id) {
        semestresSet.add(sem.semestre.id);
      }
    });
  }

  etudiantScolariteSemestres.value = rows;

  const groupesBySemestre = {};
  await Promise.all([...semestresSet].map(async (semestreId) => {
    const params = { semestre: semestreId };
    groupesBySemestre[semestreId] = await getGroupesService(params, '', false);
  }));
  groupesParSemestre.value = groupesBySemestre;

  const nextSelections = {};
  rows.forEach((item) => {
    const typeToId = {};
    (item.groupes ?? []).forEach((grp) => {
      if (grp.type) {
        typeToId[grp.type] = grp.id;
      }
    });
    Object.entries(typeToId).forEach(([type, id]) => {
      nextSelections[getStepThreeSelectionKey(item.id, type)] = id;
    });
  });
  stepThreeSelections.value = nextSelections;
};

const createScolarites = async () => {
  if (!savedDestination.value || selectedStepTwoIds.value.length === 0) return;
  savingStep.value = true;
  try {
    for (const etudiantId of selectedStepTwoIds.value) {
      await createEtudiantService({
        anneeUniversitaireId: savedDestination.value.anneeUniversitaire.id,
        anneeId: savedDestination.value.annee.id,
      }, `/${etudiantId}/integrer_scolarite`, false);
    }

    await loadStepThreeData();
    integrationDone.value = true;
    toast.add({ severity: 'success', summary: 'Étape sauvegardée', detail: 'EtudiantScolarite créées avec succès.', life: 3000 });
  } catch (error) {
    console.error('Erreur lors de la création des scolarités :', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de création des scolarités.', life: 3000 });
  } finally {
    savingStep.value = false;
  }
};

const saveGroups = async () => {
  savingStep.value = true;
  try {
    for (const row of etudiantScolariteSemestres.value) {
      const groupesDisponibles = groupesParSemestre.value[row.semestre?.id] ?? [];
      const allTypes = [...new Set(groupesDisponibles.map(g => g.type).filter(Boolean))];
      const selectedIds = allTypes
          .map(type => stepThreeSelections.value[getStepThreeSelectionKey(row.id, type)])
          .filter(Boolean);

      const payloadGroupes = selectedIds.map(id => `/api/structure_groupes/${id}`);
      await updateEtudiantScolariteSemestreService(row.id, { groupes: payloadGroupes }, false);
    }

    toast.add({ severity: 'success', summary: 'Intégration terminée', detail: 'Affectation des groupes sauvegardée.', life: 3000 });
    await getEtudiantsSansScolarite();
  } catch (error) {
    console.error('Erreur lors de la sauvegarde des groupes :', error);
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de la sauvegarde des groupes.', life: 3000 });
  } finally {
    savingStep.value = false;
  }
};

watchChanges(async () => {
  if (filtersDebounceTimeout) clearTimeout(filtersDebounceTimeout);
  filtersDebounceTimeout = setTimeout(() => {
    getEtudiantsSansScolarite();
  }, FILTERS_DEBOUNCE_MS);
});

onMounted(async () => {
  await Promise.all([loadInitData(), getEtudiantsSansScolarite()]);
});

onUnmounted(() => {
  if (filtersDebounceTimeout) clearTimeout(filtersDebounceTimeout);
});
</script>

<template>
  <Message v-if="hasError" severity="error" class="mb-4">
    Une erreur est survenue lors du chargement des données.
  </Message>
  <div v-else class="mt-8">
    <header class="card-header mb-6">
      <p class="top-card-header">Parcours guidé</p>
      <h2 class="mt-0!">Régularisation des étudiants sans scolarité</h2>
    </header>

    <Steps :model="[
      { label: '1. Année de destination' },
      { label: '2. Sélection des étudiants' },
      { label: '3. Création & affectation groupes' }
    ]" :activeStep="currentStep - 1" readonly class="mb-5" />

    <div v-if="currentStep === 1" class="card-body">
      <div class="flex flex-col gap-4 mb-4">
        <div class="text-lg font-medium p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2">
          <div v-if="anneesUniversitaires.length > 1" class="font-medium text-lg">
            Sélectionner une année universitaire
          </div>
          <div v-else class="font-medium text-lg">
            Année universitaire
          </div>
          <ListSkeleton v-if="isLoadingAnneesUniversitaires" class="w-full"/>
          <SelectButton
              v-else
              :options="anneesUniversitaires"
              v-model="selectedAnneeUniversitaire"
              class="w-full justify-center"
              optionLabel="libelle"
          />
        </div>
        <Divider></Divider>
        <div class="text-lg font-medium p-4 w-full text-center mx-auto rounded-md flex flex-col gap-2">
          <div class="font-medium text-lg">
            Sélectionner une année
          </div>
          <ListSkeleton v-if="isLoadingAnnees" class="w-full"/>
          <Button
              v-else
              :severity="selectedAnnee && selectedAnnee.id === annee.id ? 'primary' : 'secondary'"
              v-for="annee in annees"
              :key="annee.id"
              class="w-full"
              @click="selectedAnnee = annee"
          >
            {{ annee.libelle }}
          </Button>
        </div>
      </div>
      <div class="flex justify-end">
        <Button label="Sauvegarder et continuer" :disabled="!canSaveStepOne || savingStep" :loading="savingStep" @click="saveStepOne" />
      </div>
    </div>

    <div v-else-if="currentStep === 2" class="card-body">
      <DataTable
          v-model:selection="selectedEtudiants"
          v-model:filters="filters"
          :value="etudiants"
          stripedRows
          paginator
          :rows="30"
          :rowsPerPageOptions="[30, 60, 120]"
          dataKey="id"
          filterDisplay="row"
          :loading="loading"
          :globalFilterFields="['nom', 'prenom', 'mailUniv']"
      >
        <Column selectionMode="multiple" headerStyle="width: 3rem" />

        <Column field="nom" :showFilterMenu="false" header="Nom" style="min-width: 12rem">
          <template #body="{ data }">
            {{ data.nom }}
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par nom" />
          </template>
        </Column>

        <Column field="prenom" :showFilterMenu="false" header="Prénom" style="min-width: 12rem">
          <template #body="{ data }">
            {{ data.prenom }}
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par prénom" />
          </template>
        </Column>

        <Column field="mailUniv" :showFilterMenu="false" header="Email" style="min-width: 12rem">
          <template #body="{ data }">
            {{ data.mailUniv }}
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par email" />
          </template>
        </Column>

        <Column :showFilterMenu="false" style="min-width: 6rem">
          <template #body="slotProps">
            <ButtonInfo tooltip="Voir les détails de l'étudiant" @click="viewEtudiant(slotProps.data)" />
          </template>
        </Column>

        <template #footer>
          <div class="flex justify-between items-center">
            <span>{{ etudiants.length }} résultat(s).</span>
            <span>{{ selectedEtudiants.length }} sélectionné(s).</span>
          </div>
        </template>
      </DataTable>

      <div class="flex justify-end mt-4">
        <Button label="Sauvegarder et continuer" :disabled="!canSaveStepTwo || savingStep" :loading="savingStep" @click="saveStepTwo" />
      </div>
    </div>

    <div v-else class="card-body">
      <Message severity="info" class="mb-4">
        Destination : {{ savedDestination?.anneeUniversitaire?.libelle }} — {{ savedDestination?.annee?.libelle }}.
      </Message>

      <div class="flex justify-end mb-4" v-if="!integrationDone">
        <Button label="Créer les EtudiantScolarite" :disabled="savingStep" :loading="savingStep" @click="createScolarites" />
      </div>

      <div v-if="integrationDone">
        <div v-for="item in etudiantScolariteSemestres" :key="item.id" class="mb-4 p-3 border-round border-1 surface-border">
          <h4 class="m-0 mb-2">{{ item.scolarite?.etudiant?.nom }} {{ item.scolarite?.etudiant?.prenom }}</h4>
          <p class="m-0 mb-3 text-sm text-color-secondary">{{ item.semestre?.libelle ?? 'Semestre' }}</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div v-for="type in [...new Set((groupesParSemestre[item.semestre?.id] ?? []).map(g => g.type).filter(Boolean))]" :key="`${item.id}-${type}`">
              <label class="block mb-2">{{ type }}</label>
              <Dropdown
                  v-model="stepThreeSelections[getStepThreeSelectionKey(item.id, type)]"
                  :options="(groupesParSemestre[item.semestre?.id] ?? []).filter(g => g.type === type)"
                  optionValue="id"
                  optionLabel="libelle"
                  placeholder="Sélectionner un groupe"
                  class="w-full"
              />
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <Button label="Sauvegarder l'affectation des groupes" :disabled="savingStep" :loading="savingStep" @click="saveGroups" />
        </div>
      </div>

      <Message v-if="integrationDone && !etudiantScolariteSemestres.length" severity="warn">Aucune scolarité semestre disponible pour l'affectation.</Message>
    </div>

      <Dialog header=" " :visible="showViewDialog" modal :style="{ width: '90vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" dismissable-mask :closable="true"
              :isVisible="showViewDialog"
              @update:visible="showViewDialog = $event">
        <ProfilEtudiant :etudiantId="selectedEtudiant?.id" :isVisible="showViewDialog" />
      </Dialog>
  </div>
</template>

<style scoped></style>
