<script setup>
import EmptyState from './EmptyState.vue';
import { ref, onMounted, computed, watch, useSlots } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import ToggleSwitch from 'primevue/toggleswitch'
import ButtonInfo from '@components/components/Buttons/ButtonInfo.vue'
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue'
import ButtonDelete from '@components/components/Buttons/ButtonDelete.vue'
import { FilterMatchMode } from '@primevue/core/api'
import api from '@helpers/axios.js'
import { normalizeCollectionResponse } from '@helpers/collectionResponse.js'

/**
 * Tableau générique.
 *
 * Deux modes :
 *  - serveur (lazy) : `apiEndpoint` fourni, pagination / tri / recherche délégués à l'API ;
 *  - local : `value` fourni (tableau déjà chargé), pagination / tri / filtres faits par PrimeVue.
 *
 * Colonnes : { field, header, key?, sortable?, style?, type?, bodyClass?, headerClass?, filterField?,
 *              filter?: { type: 'text' | 'select', matchMode?, placeholder?, options?, optionLabel?, optionValue?, param? } }
 * Une cellule peut être personnalisée via le slot `body-<key|field>` ({ data, index }).
 * Expansion de ligne : `expandable` + slot `expansion` ({ data }) + v-model:expandedRows.
 */
const props = defineProps({
  columns: {
    type: Array,
    required: true
  },
  actionAdd: {
    type: Object,
    required: false,
    default: () => ({})
  },
  actions: {
    type: Array,
    required: false,
    default: () => []
  },
  extraActions: {
    type: Array,
    required: false,
    default: () => []
  },
  apiEndpoint: {
    type: String,
    required: false,
    default: null
  },
  value: {
    type: Array,
    required: false,
    default: null
  },
  loading: {
    type: Boolean,
    required: false,
    default: false
  },
  refreshKey: {
    type: Number,
    required: false,
    default: 0
  },
  searchParameter: {
    type: String,
    required: false,
    default: null
  },
  globalFilterFields: {
    type: Array,
    required: false,
    default: null
  },
  filterDisplay: {
    type: String,
    required: false,
    default: null // 'row' | 'menu' : active les filtres par colonne
  },
  expandable: {
    type: Boolean,
    required: false,
    default: false
  },
  showHeader: {
    type: Boolean,
    required: false,
    default: true
  },
  showFooter: {
    type: Boolean,
    required: false,
    default: true
  },
  rowsPerPageOptions: {
    type: Array,
    required: false,
    default: () => [30, 60, 120]
  },
  dataKey: {
    type: String,
    required: false,
    default: 'id'
  },
  tableClass: {
    type: [String, Array, Object],
    required: false,
    default: null
  },
  emptyTitle: {
    type: String,
    required: false,
    default: 'Aucun enregistrement trouvé.'
  }
})

const expandedRows = defineModel('expandedRows', { default: () => ({}) })
const globalFilter = defineModel('globalFilter', { default: null })

const slots = useSlots()
const modeLocal = computed(() => Array.isArray(props.value))

const data = ref([])
const totalRecords = ref(0)
const loadingApi = ref(!modeLocal.value)
const page = ref(0)
const limit = ref(props.rowsPerPageOptions[0])
const offset = computed(() => Number(limit.value * page.value))
const sortField = ref(null)
const sortOrder = ref(null)

const colKey = (col) => col.key ?? col.field
const colFilterField = (col) => col.filterField ?? col.field
const colonnesFiltrables = computed(() => props.filterDisplay ? props.columns.filter(col => col.filter) : [])

const defaultMatchMode = (col) => col.filter.matchMode
  ?? (col.filter.type === 'select' ? FilterMatchMode.EQUALS : FilterMatchMode.CONTAINS)

const construireFiltres = () => {
  const f = { global: { value: globalFilter.value, matchMode: FilterMatchMode.CONTAINS } }
  colonnesFiltrables.value.forEach((col) => {
    f[colFilterField(col)] = { value: null, matchMode: defaultMatchMode(col) }
  })
  return f
}
const filters = ref(construireFiltres())

// Synchronise la recherche globale avec un éventuel v-model:globalFilter du parent.
watch(globalFilter, (v) => { if (filters.value.global.value !== v) filters.value.global.value = v })
watch(() => filters.value.global.value, (v) => { if (globalFilter.value !== v) globalFilter.value = v })

const lignes = computed(() => modeLocal.value ? props.value : data.value)
const nombreTotal = computed(() => modeLocal.value ? props.value.length : totalRecords.value)
const chargementEnCours = computed(() => modeLocal.value ? props.loading : loadingApi.value)
const champsRecherche = computed(() => props.globalFilterFields ?? props.columns.map(col => col.field).filter(Boolean))

const fetchData = async () => {
  if (modeLocal.value || !props.apiEndpoint) return
  loadingApi.value = true
  const endpoint = props.apiEndpoint.startsWith('/') || props.apiEndpoint.startsWith('http')
    ? props.apiEndpoint
    : '/' + props.apiEndpoint

  const params = {
    limit: limit.value,
    offset: offset.value,
    sortField: sortField.value,
    sortOrder: sortOrder.value
  }

  if (props.searchParameter && filters.value.global.value) {
    params[props.searchParameter] = filters.value.global.value
  }

  colonnesFiltrables.value.forEach((col) => {
    const valeur = filters.value[colFilterField(col)]?.value
    if (valeur !== null && valeur !== undefined && valeur !== '') {
      params[col.filter.param ?? colFilterField(col)] = valeur
    }
  })

  try {
    const response = await api.get(endpoint, { params })
    const normalized = normalizeCollectionResponse(response.data)
    totalRecords.value = normalized.totalRecords
    data.value = normalized.items
  } finally {
    loadingApi.value = false
  }
}

onMounted(fetchData)

watch(() => props.refreshKey, async () => {
  await fetchData()
});

let searchTimeout = null
watch(() => filters.value.global.value, () => {
  if (modeLocal.value) return
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(async () => {
    page.value = 0
    await fetchData()
  }, 400)
})

const onPageChange = async (event) => {
  page.value = event.page
  await fetchData()
}

const onSortChange = async (event) => {
  sortField.value = event.sortField
  sortOrder.value = event.sortOrder
  await fetchData()
}

// En mode serveur, ne recharger que si un filtre de colonne a changé (la recherche globale a son propre délai).
let dernierEtatFiltres = ''
const onFilterChange = async () => {
  if (modeLocal.value || !colonnesFiltrables.value.length) return
  const etat = JSON.stringify(colonnesFiltrables.value.map(col => filters.value[colFilterField(col)]?.value ?? null))
  if (etat === dernierEtatFiltres) return
  dernierEtatFiltres = etat
  page.value = 0
  await fetchData()
}

// Lignes telles qu'affichées (filtres et tri appliqués, toutes pages confondues) : utile aux exports.
const table = ref(null)
const lignesAffichees = () => (modeLocal.value ? (table.value?.processedData ?? lignes.value) : lignes.value) ?? []
defineExpose({ lignesAffichees, recharger: fetchData })

function getNestedValue(obj, path) {
  if (!path) return undefined
  return path.split('.').reduce((acc, part) => acc && acc[part], obj);
}
</script>

<template>
  <DataTable
    ref="table"
    v-model:filters="filters"
    v-model:expanded-rows="expandedRows"
    :value="lignes"
    :lazy="!modeLocal"
    striped-rows
    paginator
    :first="modeLocal ? undefined : offset"
    :rows="limit"
    :rows-per-page-options="rowsPerPageOptions"
    :total-records="modeLocal ? undefined : totalRecords"
    :data-key="dataKey"
    :loading="chargementEnCours"
    :filter-display="filterDisplay || undefined"
    :global-filter-fields="champsRecherche"
    :class="tableClass"
    @page="onPageChange"
    @sort="onSortChange"
    @filter="onFilterChange"
    @update:rows="limit = $event"
  >
    <template v-if="showHeader" #header>
      <div class="flex justify-end">
        <Button v-if="actionAdd?.handler" label="Ajouter" icon="pi pi-plus" class="mr-2" @click="actionAdd.handler()" />
        <IconField>
          <InputIcon>
            <i class="pi pi-search" />
          </InputIcon>
          <InputText v-model="filters.global.value" placeholder="Rechercher..." aria-label="Rechercher" />
        </IconField>
      </div>
    </template>
    <template #empty>
      <slot name="empty">
        <EmptyState compact :title="emptyTitle" />
      </slot>
    </template>
    <template #loading> Chargement des données. Patientez.</template>

    <Column v-if="expandable" expander style="width: 3rem" header-class="sr-only" />

    <Column
      v-for="col in columns"
      :key="colKey(col)"
      :field="col.field"
      :filter-field="col.filter ? colFilterField(col) : undefined"
      :header="col.header"
      :style="col.style"
      :sortable="col.sortable"
      :body-class="col.bodyClass"
      :header-class="col.headerClass"
      :show-filter-menu="filterDisplay === 'menu'"
      :show-clear-button="filterDisplay === 'menu'"
    >
      <template #body="slotProps">
        <slot v-if="slots[`body-${colKey(col)}`]" :name="`body-${colKey(col)}`" v-bind="slotProps" />
        <template v-else-if="col.type === 'date'">
          {{ getNestedValue(slotProps.data, col.field) ? new Date(getNestedValue(slotProps.data, col.field)).toLocaleDateString() : '' }}
        </template>
        <ToggleSwitch v-else-if="col.type === 'boolean'" v-model="slotProps.data[col.field]" @change="col.handler(slotProps.data)" />
        <template v-else>
          {{ getNestedValue(slotProps.data, col.field) }}
        </template>
      </template>
      <template v-if="filterDisplay && col.filter" #filter="{ filterModel, filterCallback }">
        <Select
          v-if="col.filter.type === 'select'"
          v-model="filterModel.value"
          :options="col.filter.options"
          :option-label="col.filter.optionLabel ?? 'label'"
          :option-value="col.filter.optionValue ?? 'value'"
          :placeholder="col.filter.placeholder ?? 'Tous'"
          :aria-label="`Filtrer par ${col.header}`"
          show-clear
          size="small"
          class="w-full"
          @change="filterDisplay === 'row' && filterCallback()"
        />
        <InputText
          v-else
          v-model="filterModel.value"
          type="text"
          size="small"
          class="w-full"
          :placeholder="col.filter.placeholder ?? 'Filtrer…'"
          :aria-label="`Filtrer par ${col.header}`"
          @input="filterDisplay === 'row' && filterCallback()"
        />
      </template>
    </Column>
    <Column v-if="actions.length" header="Actions" :style="{ minWidth: '10rem' }">
      <template #body="slotProps">
        <template v-for="action in actions" :key="action.type">
          <ButtonInfo v-if="action.type === 'show'" tooltip="Détails" @click="action.handler(slotProps.data)" />
          <ButtonEdit v-if="action.type === 'edit'" tooltip="Modifier" @click="action.handler(slotProps.data)" />
          <ButtonDelete v-if="action.type === 'delete'" tooltip="Supprimer"
            @confirm-delete="action.handler(slotProps.data)" />
        </template>
      </template>
    </Column>
    <Column v-if="extraActions.length" header="Actions" :style="{ minWidth: '10rem' }">
      <template #body="slotProps">
        <Button v-for="action in extraActions" :key="action.label" :label="action.label" :icon="action.icon"
          @click="action.handler(slotProps.data)" />
      </template>
    </Column>

    <template v-if="expandable && slots.expansion" #expansion="slotProps">
      <slot name="expansion" v-bind="slotProps" />
    </template>
    <template v-if="showFooter" #footer> {{ nombreTotal }} résultat(s).</template>
  </DataTable>
</template>
