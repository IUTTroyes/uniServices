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

import { useAnneeUnivStore, useUsersStore } from "@stores";
import { SimpleSkeleton } from "@components";
const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();

const departementId = ref(null);
const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingAnneesUniv = ref(false);

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
  if (usersStore.departementDefaut) {
    departementId.value = usersStore.departementDefaut.id;
    await getAnneesUniv();
  } else {
    console.error('departementDefaut is not defined');
  }
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

</script>

      <template>
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
          <Column field="mailUniv" :showFilterMenu="false" header="Email" style="min-width: 12rem">
            <template #body="{ data }">
              {{ data.mailUniv }}
            </template>
            <template #filter="{ filterModel, filterCallback }">
              <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par email"/>
            </template>
          </Column>
        </DataTable>
      </template>

      <style scoped>

      </style>
