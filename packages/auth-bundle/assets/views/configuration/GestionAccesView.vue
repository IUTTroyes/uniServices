<script setup>
import { ref, onMounted, computed } from 'vue'
import { HeaderComponent, ButtonEdit } from '@components'
import { getPersonnelsService } from '@requests'
import { useEtablissementStore, useAnneeUnivStore } from '@stores'
import api from '@helpers/axios'
import AccessPersonnelDialog from "./components/AccessPersonnelDialog.vue";

const anneeUnivStore = useAnneeUnivStore();
const selectedAnneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);
const etablissementStore = useEtablissementStore()
const etablissement = etablissementStore.etablissement
const personnels = ref([])
const selectedPersonnel = ref(null)
const accessDialogVisible = ref(false)
const permissionCatalog = ref({})
const isLoading = ref(false)
const isSaving = ref(false)
const page = ref(0)
const rowOptions = [30, 60, 120]
const limit = ref(rowOptions[0])
const offset = computed(() => Number(limit.value * page.value))

const flattenUnique = (values = []) => [...new Set((Array.isArray(values) ? values : []).filter(Boolean))]

const getPersonnels = async () => {
  isLoading.value = true
  try {
    const params = {
      anneeUniversitaire: selectedAnneeUniversitaireId.value,
      itemsPerPage: limit.value,
      page: page.value + 1,
    }

    const response = await getPersonnelsService(params, '/config')
    personnels.value = response.map((personnel) => ({
      ...personnel,
      packages: flattenUnique(personnel.packages),
      permissions: flattenUnique(personnel.permissions),
      departements: Array.isArray(personnel.departements)
        ? personnel.departements.map((dept) => ({
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

const formatList = (values = []) => {
  const normalized = flattenUnique(values)
  if (!normalized.length) return 'Aucun'

  return normalized.map((value) => {
    const parts = value.split('_').map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
    return parts.join(' ')
  }).join(', ')
}

const formatBadgeList = (values = []) => {
  const normalized = flattenUnique(values)
  if (!normalized.length) {
    return '<span class="muted-badge">Aucun</span>'
  }

  return normalized.map((value) => `<span class="pill">${value}</span>`).join('')
}

const saveAccess = async () => {
  if (!selectedPersonnel.value || !selectedPersonnel.value.departements?.length) return

  isSaving.value = true

  try {
    await Promise.all(
      selectedPersonnel.value.departements.map(async (department) => {
        if (!department.id) return

        await api.patch(
          `/api/structure_departement_personnels/${department.id}`,
          {
            packages: flattenUnique(department.packages),
            permissions: flattenUnique(department.permissions),
            defaut: !!department.defaut,
            affectation: !!department.affectation,
          },
          { headers: { 'Content-Type': 'application/merge-patch+json' } }
        )
      })
    )

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

onMounted(async () => {
  await getPermissionCatalog()
  await getPersonnels()
})
</script>

<template>
  <HeaderComponent
    icon="pi pi-lock"
    titre="Gestion des accès"
    description="Gérez les affectations, les packages et les permissions d'accès par département."
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
      <DataTable
        :value="personnels"
        lazy
        striped-rows
        class="w-full"
        paginator
        :first="offset"
        :rows="limit"
        :rowsPerPageOptions="rowOptions"
        :totalRecords="personnels.length"
        :loading="isLoading"
        @page="onPageChange($event)"
        @update:rows="limit = $event"
      >
        <Column field="nom" header="Nom" sortable />
        <Column field="prenom" header="Prénom" sortable />
        <Column field="mailUniv" header="Mail Univ" sortable />
        <Column field="roles" header="Rôles globaux" sortable>
          <template #body="slotProps">
            <div class="chip-list">
              <span v-if="!slotProps.data.roles?.length" class="muted-badge">Aucun</span>
              <span v-for="role in flattenUnique(slotProps.data.roles)" :key="role" class="pill success-pill">{{ role }}</span>
            </div>
          </template>
        </Column>
        <Column field="packages" header="Packages" sortable>
          <template #body="slotProps">
            <div class="chip-list">
              <span v-if="!slotProps.data.packages?.length" class="muted-badge">Aucun</span>
              <span v-for="pkg in flattenUnique(slotProps.data.packages)" :key="pkg" class="pill info-pill">{{ pkg }}</span>
            </div>
          </template>
        </Column>
        <Column field="permissions" header="Permissions" sortable>
          <template #body="slotProps">
            <div class="chip-list">
              <span v-if="!slotProps.data.permissions?.length" class="muted-badge">Aucun</span>
              <span v-for="permission in flattenUnique(slotProps.data.permissions)" :key="permission" class="pill warning-pill">{{ permission }}</span>
            </div>
          </template>
        </Column>
        <Column field="departements" header="Départements" sortable>
          <template #body="slotProps">
            <div class="chip-list">
              <span v-if="!slotProps.data.departements?.length" class="muted-badge">Aucun</span>
              <span v-for="dept in slotProps.data.departements" :key="dept.id ?? dept.departementId" class="pill neutral-pill">
                {{ dept.libelle }}
              </span>
            </div>
          </template>
        </Column>
        <Column header="Actions" :style="{ width: '180px' }">
          <template #body="slotProps">
            <ButtonEdit @click="openAccessDialog(slotProps.data)" tooltip="Modifier les accès"/>
          </template>
        </Column>
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
