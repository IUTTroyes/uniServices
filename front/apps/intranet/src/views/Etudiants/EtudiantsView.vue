<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { FilterMatchMode } from '@primevue/core/api'
import api from '@helpers/axios.js'
import ButtonInfo from '@components/components/ButtonInfo.vue'
import ButtonEdit from '@components/components/ButtonEdit.vue'
import ButtonDelete from '@components/components/ButtonDelete.vue'

import ViewEtudiantDialog from '@/dialogs/etudiants/ViewEtudiantDialog.vue'
import EditEtudiantDialog from '@/dialogs/etudiants/EditEtudiantDialog.vue'
import AccessEtudiantDialog from '@/dialogs/etudiants/AccessEtudiantDialog.vue'

import { getEtudiantsDepartementService } from "@requests";

import {useAnneeUnivStore, useSemestreStore, useUsersStore} from "@stores";
import { SimpleSkeleton } from "@components";
const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();
const semestreStore = useSemestreStore();

const departementId = ref(null);
const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);
const semestresList = ref([]);
const selectedSemestre = ref(null);

const isLoadingAnneesUniv = ref(false);
const isLoadingSemestres = ref(false);

const etudiants = ref([])
const nbEtudiants = ref(0)
const loading = ref(true)
const page = ref(0)
const rowOptions = [30, 60, 120]

const limit = ref(rowOptions[0])
const offset = computed(() => limit.value * page.value)

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  nom: { value: null, matchMode: FilterMatchMode.CONTAINS },
  prenom: { value: null, matchMode: FilterMatchMode.CONTAINS },
  mailUniv: { value: null, matchMode: FilterMatchMode.EQUALS },
  semestre: { value: null, matchMode: FilterMatchMode.EQUALS },
})

const showViewDialog = ref(false)
const showEditDialog = ref(false)
const showAccessEditDialog = ref(false)
const selectedEtudiant = ref(null)

const getAnneesUniv = async () => {
  isLoadingAnneesUniv.value = true;
  await anneeUnivStore.getAllAnneesUniv();
  anneesUnivList.value = anneeUnivStore.anneesUniv.sort((a, b) => b.id - a.id);
  await anneeUnivStore.getCurrentAnneeUniv();
  selectedAnneeUniv.value = anneeUnivStore.anneeUniv;
  isLoadingAnneesUniv.value = false;
};

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  await semestreStore.getSemestresByDepartement(departementId.value, true);
  semestresList.value = Object.entries(
    semestreStore.semestres.reduce((acc, semestre) => {
      const annee = semestre.annee.libelle;
      if (!acc[annee]) {
        acc[annee] = [];
      }
      acc[annee].push({ label: semestre.libelle, value: semestre });
      return acc;
    }, {})
  ).map(([label, items]) => ({ label, items }));
  isLoadingSemestres.value = false;
};

const loadEtudiants = async () => {
  loading.value = true
  const response = await getEtudiantsDepartementService(departementId.value, selectedAnneeUniv.value.id, limit.value, parseInt(page.value) + 1, filters.value)
  etudiants.value = response.member
  nbEtudiants.value = response.totalItems
  loading.value = false

  etudiants.value.forEach(etudiant => {
    etudiant.etudiantScolarite = etudiant.etudiantScolarites.find(es => es.structureAnneeUniversitaire.id === selectedAnneeUniv.value.id)
  })
  etudiants.value.forEach(etudiant => {
    etudiant.semestres = etudiant.etudiantScolarite.scolarite_semestre.map(es => es.structure_semestre)
  })

  console.log(etudiants.value)
}

onMounted(async () => {
  departementId.value = usersStore.departementDefaut.id
    await getAnneesUniv();
    await getSemestres();

})

const onPageChange = async (event) => {
  limit.value = event.rows;
  page.value = event.page;
  await loadEtudiants();
}

const viewEtudiant = (etudiant) => {
  selectedEtudiant.value = etudiant
  showViewDialog.value = true
}

const editEtudiant = (etudiant) => {
  selectedEtudiant.value = etudiant
  showEditDialog.value = true
}

const deleteEtudiant = (etudiant) => {
  console.log(etudiant)
}

let debounceTimeout;
watch([filters, selectedAnneeUniv], async (newFilters, newSelectedAnneeUniv) => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(async () => {
    if (newFilters || newSelectedAnneeUniv) {
      await loadEtudiants();
    }
  }, 300);
})

watch(() => filters.value.semestre.value, async (newSemestre) => {
  if (newSemestre) {
    await loadEtudiants();
  }
});

</script>

      <template>
        <div class="card">
          <h2 class="text-2xl font-bold mb-4">Tous les étudiants du département</h2>

          <DataTable v-model:filters="filters" :value="etudiants"
                     lazy
                     stripedRows
                     paginator
                     :first="offset"
                     :rows="limit"
                     :rowsPerPageOptions="rowOptions"
                     :totalRecords="nbEtudiants"
                     dataKey="id" filterDisplay="row" :loading="loading"
                     @page="onPageChange($event)"
                     @update:rows="limit = $event"
                     :globalFilterFields="['nom', 'prenom']">
            <template #header>
                <SimpleSkeleton v-if="isLoadingAnneesUniv" class="w-1/3" />
                <IftaLabel v-else class="w-1/3">
                  <Select
                      v-model="selectedAnneeUniv"
                      :options="anneesUnivList"
                      optionLabel="libelle"
                      placeholder="Sélectionner une année universitaire"
                      class="w-full"
                  />
                  <label for="anneeUniversitaire">Année universitaire</label>
                </IftaLabel>
            </template>
            <Column field="nom" :showFilterMenu="false" header="Nom" style="min-width: 12rem">
              <template #body="{ data }">
                {{ data.nom }}
              </template>
              <template #filter="{ filterModel, filterCallback }">
                <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par nom"/>
              </template>
            </Column>
            <Column field="prenom" :showFilterMenu="false" header="Prénom" style="min-width: 12rem">
              <template #body="{ data }">
                {{ data.prenom }}
              </template>
              <template #filter="{ filterModel, filterCallback }">
                <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par prénom"/>
              </template>
            </Column>
            <Column field="semestres" :showFilterMenu="false" header="semestres" style="min-width: 12rem">
              <template #body="{ data }">
                <div v-for="semestre in data.semestres">{{semestre.libelle}}</div>
              </template>
             <template #filter="{ filterModel, filterCallback }">
                <SimpleSkeleton v-if="isLoadingSemestres" class="w-1/3" />
                <Select v-else v-model="filters.semestre.value" :options="semestresList" optionLabel="label" optionGroupLabel="label" optionGroupChildren="items" placeholder="Sélectionner un semestre" class="w-full">
                  <template #optiongroup="slotProps">
                    <div class="border-b">Année : {{ slotProps.option.label }}</div>
                  </template>
                </Select>
              </template>
            </Column>
            <Column field="mailUniv" :showFilterMenu="false" header="Email" style="min-width: 12rem">
              <template #body="{ data }">
                {{ data.mailUniv }}
              </template>
              <template #filter="{ filterModel, filterCallback }">
                <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par email"/>
              </template>
            </Column>
            <Column :showFilterMenu="false" style="min-width: 12rem">
              <template #body="slotProps">
                <ButtonInfo tooltip="Voir les détails" @click="viewEtudiant(slotProps.data)"/>
                <ButtonEdit
                    tooltip="Modifier le personnel"
                    @click="editEtudiant(slotProps.data)"/>
                <ButtonDelete
                    tooltip="Supprimer le personnel du département"
                    @confirm-delete="deleteEtudiant(slotProps.data)"/>
              </template>
            </Column>
            <template #footer> {{ nbEtudiants }} résultat(s).</template>

          </DataTable>

          <ViewEtudiantDialog
              :isVisible="showViewDialog"
              :etudiant="selectedEtudiant"
              @update:visible="showViewDialog = $event"/>
          <EditEtudiantDialog
              :isVisible="showEditDialog"
              :etudiant="selectedEtudiant"
              @update:visible="showEditDialog = $event"/>
          <AccessEtudiantDialog
              :isVisible="showAccessEditDialog"
              :etudiant="selectedEtudiant"
              @update:visible="showAccessEditDialog = $event"/>
        </div>
      </template>

      <style scoped>

      </style>
