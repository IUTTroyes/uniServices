<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAnneeUnivStore, useUsersStore } from '@stores'
import { HeaderComponent } from '@components'
import { getDiplomesService, getPlansCoursAdministrationService, getPlanCoursService, initPlanCoursModuleService } from '@requests'
import { showSuccess, showDanger } from '@helpers/toast'
import PlanCoursShowDialog from '@/components/PlanCours/PlanCoursShowDialog.vue'

const router = useRouter()
const anneeUnivStore = useAnneeUnivStore()
const usersStore = useUsersStore()

const isLoadingDiplomes = ref(true)
const isLoadingTable = ref(false)

const diplomes = ref([])
const selectedDiplome = ref(null)
const selectedSemestre = ref(null)
const selectedEtat = ref(null)
const searchQuery = ref('')

const adminData = ref(null)
const enseignements = ref([])
const stats = ref({
  total: 0,
  complet: 0,
  enCours: 0,
  none: 0
})

// Dialog
const showDialogVisible = ref(false)
const selectedPlanCoursDetail = ref(null)
const selectedEnseignementDetail = ref(null)

const etatOptions = [
  { label: 'Tous les états', value: null },
  { label: 'Complet', value: 'COMPLET' },
  { label: 'En cours', value: 'EN_COURS' },
  { label: 'Non commencé', value: 'NONE' }
]

const loadDiplomes = async () => {
  isLoadingDiplomes.value = true
  try {
    await anneeUnivStore.getCurrentAnneeUniv()
    const departementId = usersStore.departementDefaut?.id

    const params = {}
    if (departementId) {
      params.departement = departementId
    }
    const dip = await getDiplomesService(params, '/maquette')
    diplomes.value = dip || []

    if (diplomes.value.length > 0) {
      selectedDiplome.value = diplomes.value[0]
      await loadTableData()
    }
  } catch (error) {
    console.error('Erreur chargement diplômes:', error)
    showDanger('Erreur lors du chargement des diplômes.')
  } finally {
    isLoadingDiplomes.value = false
  }
}

const loadTableData = async () => {
  if (!selectedDiplome.value?.id) return

  isLoadingTable.value = true
  try {
    const activeAnneeId = anneeUnivStore.selectedAnneeUniv?.id || anneeUnivStore.anneeUniv?.id
    const params = {
      diplome: selectedDiplome.value.id
    }
    if (activeAnneeId) {
      params.anneeUniversitaire = activeAnneeId
    }
    if (selectedSemestre.value) {
      params.semestre = selectedSemestre.value
    }
    if (selectedEtat.value) {
      params.etat = selectedEtat.value
    }

    const data = await getPlansCoursAdministrationService(params)
    adminData.value = data
    stats.value = data.stats || { total: 0, complet: 0, enCours: 0, none: 0 }
    enseignements.value = data.enseignements || []
  } catch (error) {
    console.error('Erreur chargement table admin:', error)
    showDanger('Erreur lors du chargement des données.')
  } finally {
    isLoadingTable.value = false
  }
}

onMounted(async () => {
  await loadDiplomes()
})

watch(() => selectedDiplome.value, async () => {
  selectedSemestre.value = null
  await loadTableData()
})

watch(() => [selectedSemestre.value, selectedEtat.value], async () => {
  await loadTableData()
})

const semestreOptions = computed(() => {
  if (!enseignements.value) return []
  const sMap = new Map()
  enseignements.value.forEach(item => {
    if (item.semestre?.id && !sMap.has(item.semestre.id)) {
      sMap.set(item.semestre.id, item.semestre.libelle)
    }
  })

  return [
    { label: 'Tous les semestres', value: null },
    ...Array.from(sMap.entries()).map(([id, label]) => ({ label, value: id }))
  ]
})

const filteredEnseignements = computed(() => {
  if (!searchQuery.value.trim()) return enseignements.value

  const q = searchQuery.value.toLowerCase().trim()
  return enseignements.value.filter(item => {
    const code = (item.enseignement?.codeEnseignement || '').toLowerCase()
    const libelle = (item.enseignement?.libelle || '').toLowerCase()
    const referent = (item.referent?.display || '').toLowerCase()
    return code.includes(q) || libelle.includes(q) || referent.includes(q)
  })
})

const completionPercent = computed(() => {
  if (!stats.value.total) return 0
  return Math.round((stats.value.complet / stats.value.total) * 100)
})

const openShowDialog = async (item) => {
  if (!item.planCours?.id) return
  try {
    const fullPlan = await getPlanCoursService(item.planCours.id)
    selectedPlanCoursDetail.value = fullPlan
    selectedEnseignementDetail.value = item.enseignement
    showDialogVisible.value = true
  } catch (error) {
    showDanger('Impossible d\'ouvrir le plan de cours.')
  }
}

const openEditOrCreate = async (item) => {
  if (item.planCours?.id) {
    router.push(`/intranet/plan-de-cours/edit/${item.planCours.id}`)
  } else {
    try {
      isLoadingTable.value = true
      const activeAnneeId = anneeUnivStore.selectedAnneeUniv?.id || anneeUnivStore.anneeUniv?.id
      const res = await initPlanCoursModuleService(item.enseignement.id, activeAnneeId)
      router.push(`/intranet/plan-de-cours/edit/${res.id}`)
    } catch (e) {
      showDanger('Erreur lors de l\'initialisation du plan de cours.')
      isLoadingTable.value = false
    }
  }
}

const getBadgeSeverity = (etat) => {
  switch (etat) {
    case 'COMPLET': return 'success'
    case 'EN_COURS': return 'warn'
    default: return 'danger'
  }
}
</script>

<template>
  <div>
    <HeaderComponent
      icon="pi pi-file"
      titre="Administration des plans de cours"
      description="Suivi pédagogique, avancement et consultation des plans de cours par diplôme et semestre."
    />

    <!-- Loader initial -->
    <div v-if="isLoadingDiplomes" class="flex justify-center p-12">
      <ProgressSpinner />
    </div>

    <div v-else class="flex flex-col gap-6">
      <!-- Sélecteur de diplôme & Filtres -->
      <div class="card p-5 rounded-2xl border border-surface-200 dark:border-surface-700 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3 w-full md:w-auto">
          <label for="diplomeSelect" class="font-bold text-sm text-surface-900 dark:text-surface-0 whitespace-nowrap">Diplôme :</label>
          <Select
            id="diplomeSelect"
            v-model="selectedDiplome"
            :options="diplomes"
            optionLabel="libelle"
            placeholder="Sélectionner un diplôme"
            class="w-full md:w-80"
          />
        </div>

        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <Select
            v-model="selectedSemestre"
            :options="semestreOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Semestre"
            class="w-full sm:w-44"
          />

          <Select
            v-model="selectedEtat"
            :options="etatOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="État"
            class="w-full sm:w-40"
          />

          <Button icon="pi pi-refresh" severity="secondary" outlined @click="loadTableData" title="Actualiser" />
        </div>
      </div>

      <!-- Métriques d'avancement -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Taux de complétion -->
        <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex flex-col justify-between">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-color">Avancement global</span>
            <span class="text-base font-bold text-primary">{{ completionPercent }}%</span>
          </div>
          <ProgressBar :value="completionPercent" :showValue="false" style="height: 8px" />
          <span class="text-xs text-muted-color mt-2">{{ stats.complet }} sur {{ stats.total }} modules complétés</span>
        </div>

        <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-color">Plans complets</span>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.complet }}</div>
          </div>
          <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
            <i class="pi pi-check text-xl" />
          </div>
        </div>

        <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-color">En cours de rédaction</span>
            <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ stats.enCours }}</div>
          </div>
          <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
            <i class="pi pi-clock text-xl" />
          </div>
        </div>

        <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-color">Non commencés</span>
            <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ stats.none }}</div>
          </div>
          <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-950/50 flex items-center justify-center text-rose-600 dark:text-rose-400">
            <i class="pi pi-exclamation-triangle text-xl" />
          </div>
        </div>
      </div>

      <!-- Tableau principal -->
      <div class="card p-5 rounded-2xl border border-surface-200 dark:border-surface-700">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-4">
          <h3 class="text-base font-bold text-surface-900 dark:text-surface-0">
            Enseignements du diplôme ({{ filteredEnseignements.length }})
          </h3>

          <div class="w-full sm:w-72">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="searchQuery" placeholder="Filtrer les enseignements..." class="w-full" />
            </IconField>
          </div>
        </div>

        <DataTable
          :value="filteredEnseignements"
          :loading="isLoadingTable"
          stripedRows
          paginator
          :rows="15"
          :rowsPerPageOptions="[15, 30, 50]"
          class="text-sm"
          responsiveLayout="stack"
          breakpoint="960px"
        >
          <Column field="enseignement.codeEnseignement" header="Code" style="width: 110px">
            <template #body="{ data }">
              <span class="font-mono font-bold text-primary">{{ data.enseignement.codeEnseignement }}</span>
            </template>
          </Column>

          <Column field="enseignement.libelle" header="Intitulé de l'enseignement">
            <template #body="{ data }">
              <div class="font-medium text-surface-900 dark:text-surface-0">{{ data.enseignement.libelle }}</div>
              <div class="flex items-center gap-2 mt-1">
                <Tag :value="data.enseignement.typeLibelle" :severity="data.enseignement.typeBadge" class="text-xs uppercase" />
                <span v-if="data.semestre" class="text-xs text-muted-color">{{ data.semestre.libelle }}</span>
              </div>
            </template>
          </Column>

          <Column field="referent.display" header="Référent / Enseignant" style="width: 220px">
            <template #body="{ data }">
              <div v-if="data.referent || data.planCours?.responsable" class="flex items-center gap-2">
                <i class="pi pi-user text-muted-color text-xs" />
                <span>{{ data.referent?.display || data.planCours?.responsable?.display }}</span>
              </div>
              <span v-else class="text-muted-color italic text-xs">Non défini</span>
            </template>
          </Column>

          <Column field="planCours.etatPlanCours" header="État du plan" style="width: 160px">
            <template #body="{ data }">
              <Tag
                :value="data.planCours?.etatLibelle || 'Non commencé'"
                :severity="getBadgeSeverity(data.planCours?.etatPlanCours)"
                class="font-semibold"
              />
            </template>
          </Column>

          <Column header="Heures" style="width: 140px">
            <template #body="{ data }">
              <div v-if="data.planCours?.id" class="text-xs">
                <div>Prévu : <strong>{{ (data.planCours.cmPrevu || 0) + (data.planCours.tdPrevu || 0) + (data.planCours.tpPrevu || 0) }}h</strong></div>
                <div v-if="data.planCours.cmRealise !== null" class="text-muted-color">
                  Réalisé : {{ (data.planCours.cmRealise || 0) + (data.planCours.tdRealise || 0) + (data.planCours.tpRealise || 0) }}h
                </div>
              </div>
              <span v-else class="text-xs text-muted-color">-</span>
            </template>
          </Column>

          <Column header="Actions" style="width: 120px" class="text-right">
            <template #body="{ data }">
              <div class="flex items-center justify-end gap-1">
                <Button
                  v-if="data.planCours?.id"
                  icon="pi pi-eye"
                  severity="secondary"
                  text
                  @click="openShowDialog(data)"
                  title="Consulter"
                />
                <Button
                  :icon="data.planCours?.id ? 'pi pi-pencil' : 'pi pi-plus'"
                  :severity="data.planCours?.id ? 'primary' : 'success'"
                  text
                  @click="openEditOrCreate(data)"
                  :title="data.planCours?.id ? 'Modifier' : 'Créer le plan'"
                />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="p-8 text-center text-muted-color">
              Aucun enseignement pour ce diplôme et ces filtres.
            </div>
          </template>
        </DataTable>
      </div>
    </div>

    <!-- Dialog de visualisation Show -->
    <PlanCoursShowDialog
      v-model:visible="showDialogVisible"
      :planCours="selectedPlanCoursDetail"
      :enseignement="selectedEnseignementDetail"
      @edit="(id) => router.push(`/intranet/plan-de-cours/edit/${id}`)"
    />
  </div>
</template>
