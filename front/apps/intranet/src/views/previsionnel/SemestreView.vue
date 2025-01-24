<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useSemestreStore, useAnneeUnivStore, useUsersStore } from '@stores';
import { SimpleSkeleton, ListSkeleton } from '@components';
import { getSemestrePreviService, buildSemestrePreviService, calcTotalHeures } from '@requests';

const usersStore = useUsersStore();
const semestreStore = useSemestreStore();
const anneeUnivStore = useAnneeUnivStore();
const departementId = usersStore.departementDefaut.id;

const semestresList = ref([]);
const selectedSemestre = ref(null);
const semestreDetails = ref(null);

const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingSemestres = ref(false);
const isLoadingAnneesUniv = ref(false);
const isLoadingPrevisionnel = ref(true);

const previSemestre = ref(null);
const previGrouped = ref(null);

const size = ref({ label: 'Normal', value: 'null' });
const sizeOptions = ref([
  { label: 'Petit', value: 'small' },
  { label: 'Normal', value: 'null' },
  { label: 'Large', value: 'large' }
]);

const searchTerm = ref('');
const filters = ref({
  'enseignement.libelle': { value: null, matchMode: 'contains' }
});

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  await semestreStore.getSemestresByDepartement(departementId, true);
  semestresList.value = semestreStore.semestres;
  if (semestresList.value.length > 0) {
    selectedSemestre.value = semestresList.value[0];
  }
  isLoadingSemestres.value = false;
};

const getPrevi = async (semestreId) => {
  if (semestreId) {
    isLoadingPrevisionnel.value = true;
    await semestreStore.getSemestre(semestreId);
    semestreDetails.value = semestreStore.semestre;

    previSemestre.value = await getSemestrePreviService(selectedSemestre.value.id, selectedAnneeUniv.value.id);

    previSemestre.value.forEach((previ) => {
      previ.total = calcTotalHeures(previ.heures);
    });

    previGrouped.value = await buildSemestrePreviService(previSemestre.value);
    isLoadingPrevisionnel.value = false;
  }
};

const getAnneesUniv = async () => {
  isLoadingAnneesUniv.value = true;
  await anneeUnivStore.getAllAnneesUniv();
  anneesUnivList.value = anneeUnivStore.anneesUniv.sort((a, b) => b.id - a.id);
  await anneeUnivStore.getCurrentAnneeUniv();
  selectedAnneeUniv.value = anneeUnivStore.anneeUniv;
  isLoadingAnneesUniv.value = false;
};

onMounted(async () => {
  await getSemestres();
  await getAnneesUniv();
});

watch([selectedSemestre, selectedAnneeUniv], async ([newSemestre, newAnneeUniv]) => {
  if (newSemestre && newAnneeUniv) {
    await getPrevi(newSemestre.id, newAnneeUniv.id);
  }
});

watch(searchTerm, (newTerm) => {
  filters.value['enseignement.libelle'].value = newTerm;
});
</script>

<template>
  <div class="px-4 py-12 flex flex-col gap-6">
    <div class="flex justify-between gap-10">
      <div class="flex gap-6 w-1/2">
        <SimpleSkeleton v-if="isLoadingSemestres" class="w-1/2" />
        <IftaLabel v-else class="w-1/2">
          <Select
              v-model="selectedSemestre"
              :options="semestresList"
              optionLabel="libelle"
              placeholder="Sélectionner un semestre"
              class="w-full"
          />
          <label for="semestre">Semestre</label>
        </IftaLabel>
        <SimpleSkeleton v-if="isLoadingAnneesUniv" class="w-1/2" />
        <IftaLabel v-else class="w-1/2">
          <Select
              v-model="selectedAnneeUniv"
              :options="anneesUnivList"
              optionLabel="libelle"
              placeholder="Sélectionner une année universitaire"
              class="w-full"
          />
          <label for="anneeUniversitaire">Année universitaire</label>
        </IftaLabel>
      </div>
      <Button label="Saisir le prévisionnel" icon="pi pi-plus" />
    </div>
    <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
    <div v-else>
      <div v-if="previGrouped?.length > 0">
        <div class="flex w-full justify-between my-6">
          <SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
          <div class="flex justify-end">
            <IconField>
              <InputIcon>
                <i class="pi pi-search" />
              </InputIcon>
              <InputText v-model="searchTerm" placeholder="Rechercher par matière" />
            </IconField>
          </div>
        </div>
        <DataTable :value="previGrouped" :filters="filters" tableStyle="min-width: 50rem" striped-rows scrollable :size="size.value">
          <ColumnGroup type="header">
            <Row>
              <Column :header="`Prévisionnel du semestre ${selectedSemestre?.libelle}`" :colspan="4" class="text-black text-xl"/>
              <Column header="CM" :colspan="3" class="!bg-purple-400 !bg-opacity-20"/>
              <Column header="TD" :colspan="3" class="!bg-green-400 !bg-opacity-20"/>
              <Column header="TP" :colspan="3" class="!bg-amber-400 !bg-opacity-20"/>
              <Column header="Total" :colspan="3"/>
            </Row>
            <Row>
              <Column header="Code" :colspan="1" sortable field="enseignement.codeEnseignement"/>
              <Column header="Nom" :colspan="1" sortable field="enseignement.libelle"/>
              <Column header="Type" :colspan="1" sortable field="enseignement.type"/>
              <Column header="Nb profs" :colspan="1"/>
              <Column header="Maq." :colspan="1" class="!bg-purple-400 !bg-opacity-20"/>
              <Column header="Prévi." :colspan="1" class="!bg-purple-400 !bg-opacity-20"/>
              <Column header="Diff." :colspan="1" class="!bg-purple-400 !bg-opacity-20" sortable field="heures.CM.Diff"/>
              <Column header="Maq." :colspan="1" class="!bg-green-400 !bg-opacity-20"/>
              <Column header="Prévi." :colspan="1" class="!bg-green-400 !bg-opacity-20"/>
              <Column header="Diff." :colspan="1" class="!bg-green-400 !bg-opacity-20" sortable field="heures.TD.Diff"/>
              <Column header="Maq." :colspan="1" class="!bg-amber-400 !bg-opacity-20"/>
              <Column header="Previ" :colspan="1" class="!bg-amber-400 !bg-opacity-20"/>
              <Column header="Diff" :colspan="1" class="!bg-amber-400 !bg-opacity-20" sortable field="heures.TP.Diff"/>
              <Column header="Maq." :colspan="1"/>
              <Column header="Prévi." :colspan="1"/>
              <Column header="Diff." :colspan="1" sortable field="heures.Total.Diff" class=""/>
            </Row>
          </ColumnGroup>

          <Column field="enseignement.codeEnseignement" header="Code" />
          <Column field="enseignement.libelle" header="Nom" />
          <Column field="enseignement.type" header="Type" />
          <Column field="personnel.length" header="Nb intervenants" />

          <Column class="bg-purple-400 bg-opacity-20" field="heures.CM.Maquette" header="Maquette">
            <template #body="slotProps">
              {{ slotProps.data.heures.CM.Maquette }} h
            </template>
          </Column>
          <Column class="bg-purple-400 bg-opacity-20" field="heures.CM.Previ" header="Previ">
            <template #body="slotProps">
              {{ slotProps.data.heures.CM.Previ }} h
            </template>
          </Column>
          <Column class="bg-purple-400 bg-opacity-20" field="heures.CM.Diff" header="Diff" sortable>
            <template #body="slotProps">
              <Tag
                  class="w-max"
                  :severity="slotProps.data.heures.CM.Diff === 0 ? 'success' : (slotProps.data.heures.CM.Diff < 0 ? 'warn' : 'danger')"
                  :icon="slotProps.data.heures.CM.Diff === 0 ? 'pi pi-check' : (slotProps.data.heures.CM.Diff < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up')"
                  :class="slotProps.data.heures.CM.Diff === 0 ? '!bg-green-400 !text-white' : (slotProps.data.heures.CM.Diff < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white')"
              >
                {{ slotProps.data.heures.CM.Diff ?? 0 }} h
              </Tag>
            </template>
          </Column>

          <Column class="bg-green-400 bg-opacity-20" field="heures.TD.Maquette" header="Maquette">
            <template #body="slotProps">
              {{ slotProps.data.heures.TD.Maquette }} h
            </template>
          </Column>
          <Column class="bg-green-400 bg-opacity-20" field="heures.TD.Previ" header="Previ">
            <template #body="slotProps">
              {{ slotProps.data.heures.TD.Previ }} h
            </template>
          </Column>
          <Column class="bg-green-400 bg-opacity-20" field="heures.TD.Diff" header="Diff">
            <template #body="slotProps">
              <Tag
                  class="w-max"
                  :severity="slotProps.data.heures.TD.Diff === 0 ? 'success' : (slotProps.data.heures.TD.Diff < 0 ? 'warn' : 'danger')"
                  :icon="slotProps.data.heures.TD.Diff === 0 ? 'pi pi-check' : (slotProps.data.heures.TD.Diff < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up')"
                  :class="slotProps.data.heures.TD.Diff === 0 ? '!bg-green-400 !text-white' : (slotProps.data.heures.TD.Diff < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white')"
              >
                {{ slotProps.data.heures.TD.Diff ?? 0 }} h
              </Tag>
            </template>
          </Column>

          <Column class="bg-amber-400 bg-opacity-20" field="heures.TP.Maquette" header="Maquette">
            <template #body="slotProps">
              {{ slotProps.data.heures.TP.Maquette }} h
            </template>
          </Column>
          <Column class="bg-amber-400 bg-opacity-20" field="heures.TP.Previ" header="Previ">
            <template #body="slotProps">
              {{ slotProps.data.heures.TP.Previ }} h
            </template>
          </Column>
          <Column class="bg-amber-400 bg-opacity-20" field="heures.TP.Diff" header="Diff">
            <template #body="slotProps">
              <Tag
                  class="w-max"
                  :severity="slotProps.data.heures.TP.Diff === 0 ? 'success' : (slotProps.data.heures.TP.Diff < 0 ? 'warn' : 'danger')"
                  :icon="slotProps.data.heures.TP.Diff === 0 ? 'pi pi-check' : (slotProps.data.heures.TP.Diff < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up')"
                  :class="slotProps.data.heures.TP.Diff === 0 ? '!bg-green-400 !text-white' : (slotProps.data.heures.TP.Diff < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white')"
              >
                {{ slotProps.data.heures.TP.Diff ?? 0 }} h
              </Tag>
            </template>
          </Column>

          <Column field="heures.Total.Maquette" header="Maquette">
            <template #body="slotProps">
              {{ slotProps.data.heures.Total.Maquette }} h
            </template>
          </Column>
          <Column field="heures.Total.Previ" header="Previ">
            <template #body="slotProps">
              {{ slotProps.data.heures.Total.Previ }} h
            </template>
          </Column>
          <Column field="heures.Total.Diff" header="Diff">
            <template #body="slotProps">
              <Tag
                  class="w-max"
                  :severity="slotProps.data.heures.Total.Diff === 0 ? 'success' : (slotProps.data.heures.Total.Diff < 0 ? 'warn' : 'danger')"
                  :icon="slotProps.data.heures.Total.Diff === 0 ? 'pi pi-check' : (slotProps.data.heures.Total.Diff < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up')"
                  :class="slotProps.data.heures.Total.Diff === 0 ? '!bg-green-400 !text-white' : (slotProps.data.heures.Total.Diff < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white')"
              >
                {{ slotProps.data.heures.Total.Diff ?? 0 }} h
              </Tag>
            </template>
          </Column>
        </DataTable>
      </div>
      <Message v-else severity="error" icon="pi pi-times-circle">
        Aucun prévisionnel pour cette année universitaire et ce semestre
      </Message>
    </div>
  </div>
</template>

<style scoped>
.loader {
  text-align: center;
  font-size: 1.5rem;
  padding: 2rem;
}
</style>
