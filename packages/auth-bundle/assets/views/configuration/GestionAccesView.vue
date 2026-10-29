<script setup>
import { ref, onMounted, computed } from 'vue'
import { HeaderComponent, ButtonEdit, PhotoUser } from '@components'
import { getPersonnelsService } from '@requests'
import { useEtablissementStore, useAnneeUnivStore, useUsersStore } from '@stores'
import api from '@helpers/axios'
import { normalizeIutDirectionRoles } from '@utils/roleCatalog'
import AccessPersonnelDialog from "./components/AccessPersonnelDialog.vue";
import { usePersonnelFilters } from '@composables/filters/usersFilters/usePersonnelFilters.ts';

const anneeUnivStore = useAnneeUnivStore();
const selectedAnneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);
const etablissementStore = useEtablissementStore()
const etablissement = etablissementStore.etablissement
const personnels = ref([])
const totalPersonnels = ref(0)
const selectedPersonnel = ref(null)
const accessDialogVisible = ref(false)
const permissionCatalog = ref({})
const isLoading = ref(false)
const isSaving = ref(false)
const page = ref(0)
const rowOptions = [30, 60, 120]
const limit = ref(rowOptions[0])
const offset = computed(() => Number(limit.value * page.value))
const multiSortMeta = ref([])
const expandedRows = ref({})

const flattenUnique = (values = []) => [...new Set((Array.isArray(values) ? values : []).filter(Boolean))]

const getPersonnels = async () => {
  isLoading.value = true
  try {
    const params = {
      anneeUniversitaire: selectedAnneeUniversitaireId.value,
      itemsPerPage: limit.value,
      page: page.value + 1,
      filters: filters.value,
      sort: multiSortMeta.value,
    }

    const response = await getPersonnelsService(params, '/config')
    totalPersonnels.value = response?.totalRecords ?? response?.totalItems ?? response?.length ?? 0
    personnels.value = response.map((personnel, index) => ({
      ...personnel,
      _rowKey: personnel.id ?? personnel.numeroHarpege ?? `${personnel.nom ?? 'personnel'}-${personnel.prenom ?? 'inconnu'}-${index}`,
      departements: Array.isArray(personnel.departements) ? personnel.departements.sort((a, b) => b.defaut - a.defaut).map((dept) => ({
            ...dept,
            packages: flattenUnique(dept.packages),
            permissions: flattenUnique(dept.permissions),
          }))
        : [],
    }))
  } finally {
    isLoading.value = false
  }
}

const getPermissionCatalog = async () => {
  const response = await api.get('/api/security/permissions')
  permissionCatalog.value = response.data ?? {}
}

const openAccessDialog = (personnel) => {
  selectedPersonnel.value = personnel
  accessDialogVisible.value = true
}

const saveAccess = async () => {
  if (!selectedPersonnel.value) return

  isSaving.value = true

  try {
    normalizeIutDirectionRoles(selectedPersonnel.value.departements ?? [])
    await Promise.all(
      selectedPersonnel.value.departements.map(async (department) => {
        if (!department.id) return

        await api.patch(
          `/api/structure_departement_personnels/${department.id}`,
          {
            packages: flattenUnique(department.packages),
            permissions: flattenUnique(department.permissions),
            responsableParcoursDiplomes: department.permissions?.includes('ROLE_RESP_PARCOURS') ? (department.responsableParcoursDiplomes ?? []) : [],
            defaut: !!department.defaut,
            affectation: !!department.affectation,
          },
          { headers: { 'Content-Type': 'application/merge-patch+json' } }
        )
      })
    )

    const userStore = useUsersStore()
    if (String(selectedPersonnel.value.id) === String(userStore.userId)) {
      await userStore.getUser()
    }
    await getPersonnels()
    accessDialogVisible.value = false
  } finally {
    isSaving.value = false
  }
}

const onPageChange = async (event) => {
  limit.value = event.rows
  page.value = event.page
  await getPersonnels()
}

const onSortChange = async (event) => {
  multiSortMeta.value = event.multiSortMeta ?? []
  page.value = 0
  await getPersonnels()
}

onMounted(async () => {
  await getPermissionCatalog()
  await getPersonnels()
})

const FILTERS_DEBOUNCE_MS = 250;
let filtersDebounceTimeout = null;

//watch filters
const {filters, watchChanges} = usePersonnelFilters();
watchChanges(async() => {
  page.value = 0;
  if (filtersDebounceTimeout) {
    clearTimeout(filtersDebounceTimeout);
  }
  filtersDebounceTimeout = setTimeout(() => {
    getPersonnels();
  }, FILTERS_DEBOUNCE_MS);
});
</script>

<template>
  <HeaderComponent
    icon="pi pi-lock"
    titre="Gestion des accès"
    description="Visualisez et gérez les rôles, permissions et packages par affectation départementale."
  />

  <div class="card">
    <div class="flex flex-col md:flex-row justify-between items-start w-full card-header">
      <div>
        <p class="top-card-header">Droits et affectations</p>
        <div class="flex flex-col items-start">
          <p class="uppercase text-xs font-bold mb-0! text-muted-color">
            {{ etablissement.libelle }}
          </p>
          <h2 class="mt-0!">Tous les personnels de l'établissement</h2>
        </div>
      </div>
    </div>

    <div class="card-body">
      <Message severity="info" :closable="false" icon="pi pi-info-circle" class="mb-2">
      Maintenez Ctrl ou Cmd et cliquez sur plusieurs colonnes pour trier par plusieurs champs à la fois.
      </Message>
      <DataTable
        :value="personnels"
        v-model:expandedRows="expandedRows"
        dataKey="_rowKey"
        lazy
        striped-rows
        class="w-full"
        paginator
        scrollHeight="80vh"
        scrollable
        removableSort
        sortMode="multiple"
        :first="offset"
        :rows="limit"
        :rowsPerPageOptions="rowOptions"
        :totalRecords="totalPersonnels"
        :loading="isLoading"
        v-model:filters="filters"
        :globalFilterFields="['nom', 'prenom', 'username', 'numeroHarpege', 'mailUniv']"
        filterDisplay="row"
        @page="onPageChange($event)"
        @sort="onSortChange($event)"
        @update:rows="limit = $event">
        <Column expander style="width: 3rem" />
        <Column field="photo" :showFilterMenu="false" header="" style="width: 4rem">
          <template #body="{ data }">
            <PhotoUser :user-photo="data.photoName" class="rounded-full w-10 h-10 border border-surface-200 dark:border-surface-700 object-cover mx-auto"/>
          </template>
        </Column>
        <Column field="nom" :showFilterMenu="false" header="Nom" :sortable="true" style="min-width: 9rem">
          <template #body="{ data }">
            <span class="font-bold text-surface-900 dark:text-surface-100">{{ data.nom }}</span>
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText size="small" v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par nom"/>
          </template>
        </Column>
        <Column field="prenom" :showFilterMenu="false" header="Prénom" :sortable="true" style="min-width: 9rem">
          <template #body="{ data }">
            <span>{{ data.prenom }}</span>
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText size="small" v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par prénom"/>
          </template>
        </Column>
        <Column field="username" :showFilterMenu="false" header="Login" :sortable="true" style="min-width: 9rem">
          <template #body="{ data }">
            <span class="font-mono text-xs text-surface-700 dark:text-surface-300 font-semibold">
              {{ data.username || '-' }}
            </span>
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText size="small" v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par login"/>
          </template>
        </Column>
        <Column field="numeroHarpege" :showFilterMenu="false" header="ID RH" :sortable="true" style="min-width: 8rem">
          <template #body="{ data }">
            <span class="font-mono text-xs text-muted-color">
              {{ data.numeroHarpege ?? '-' }}
            </span>
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText size="small" v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par ID RH"/>
          </template>
        </Column>
        <Column field="mailUniv" :showFilterMenu="false" header="Mail Univ" :sortable="true" style="min-width: 14rem">
          <template #body="{ data }">
            <span class="text-xs text-surface-700 dark:text-surface-300">{{ data.mailUniv }}</span>
          </template>
          <template #filter="{ filterModel, filterCallback }">
            <InputText size="small" v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par mail"/>
          </template>
        </Column>
        <Column header="Départements autorisés" style="min-width: 16rem">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1.5 items-center">
              <span v-if="!data.departements?.length" class="text-xs text-muted-color italic">
                Aucun département
              </span>
              <Tag
                v-for="dept in data.departements"
                :key="dept.id ?? dept.departementId"
                :severity="dept.defaut ? 'primary' : 'secondary'"
                :value="dept.libelle"
                :icon="dept.defaut ? 'pi pi-star-fill' : undefined"
                class="text-[11px]"
                :title="dept.defaut ? 'Département par défaut' : 'Département affecté'"
              />
            </div>
          </template>
        </Column>
        <Column header="Actions" :style="{ width: '6rem', textAlign: 'center' }">
          <template #body="slotProps">
            <ButtonEdit @click="openAccessDialog(slotProps.data)" tooltip="Modifier les accès"/>
          </template>
        </Column>

        <template #expansion="slotProps">
          <div class="p-4 bg-surface-50 rounded-xl border border-surface-200">
            <div class="flex items-center justify-between mb-3">
              <h4 class="m-0 text-base font-semibold">Accès par département</h4>
              <span class="text-xs uppercase font-bold text-muted-color">
                {{ slotProps.data.departements?.length ?? 0 }} département(s)
              </span>
            </div>

            <Message
              v-if="!slotProps.data.departements?.length"
              severity="info"
              :closable="false"
              icon="pi pi-info-circle"
            >
              Aucun département affecté.
            </Message>

            <DataTable
              v-else
              :value="slotProps.data.departements"
              striped-rows
              class="w-full"
              responsiveLayout="scroll"
            >
              <Column field="libelle" header="Département" style="min-width: 14rem" />
              <Column field="permissions" header="Rôles par département" style="min-width: 24rem">
                <template #body="{ data }">
                  <div class="flex flex-wrap gap-2">
                    <Tag v-if="!data.permissions?.length" severity="secondary" value="Aucun" />
                    <Tag
                      v-for="role in flattenUnique(data.permissions)"
                      :key="`${data.id ?? data.departementId}-role-${role}`"
                      severity="success"
                      :value="role"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </template>
      </DataTable>
    </div>
  </div>

  <AccessPersonnelDialog
    v-model:isVisible="accessDialogVisible"
    :personnel="selectedPersonnel"
    :permissionCatalog="permissionCatalog"
    @save="saveAccess"
  />
</template>

<style scoped>
</style>
