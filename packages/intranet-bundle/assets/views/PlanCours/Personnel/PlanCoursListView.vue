<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAnneeUnivStore, useUsersStore } from '@stores'
import { HeaderComponent } from '@components'
import { getPlansCoursPersonnelService, initPlanCoursModuleService, getPlanCoursService } from '@requests'
import { showSuccess, showDanger } from '@helpers/toast'
import PlanCoursShowDialog from '@/components/PlanCours/PlanCoursShowDialog.vue'
import PlanCoursRecopieDialog from '@/components/PlanCours/PlanCoursRecopieDialog.vue'

const router = useRouter()
const anneeUnivStore = useAnneeUnivStore()
const usersStore = useUsersStore()

const isLoading = ref(true)
const modules = ref([])
const anneeUniv = ref(null)
const personnel = ref(null)
const searchQuery = ref('')
const selectedStatusFilter = ref('ALL')

// Dialogs state
const showDialogVisible = ref(false)
const selectedPlanCoursDetail = ref(null)
const selectedEnseignementDetail = ref(null)

const recopieDialogVisible = ref(false)
const targetPlanCoursId = ref(null)

const statusOptions = [
  { label: 'Tous les états', value: 'ALL' },
  { label: 'Complets', value: 'COMPLET' },
  { label: 'En cours', value: 'EN_COURS' },
  { label: 'Non commencés', value: 'NONE' }
]

const loadData = async () => {
  isLoading.value = true
  try {
    await anneeUnivStore.getCurrentAnneeUniv()
    const activeAnneeId = anneeUnivStore.selectedAnneeUniv?.id || anneeUnivStore.anneeUniv?.id

    const params = {}
    if (activeAnneeId) {
      params.anneeUniversitaire = activeAnneeId
    }
    if (usersStore.userId) {
      params.personnel = usersStore.userId
    }

    const data = await getPlansCoursPersonnelService(params)
    anneeUniv.value = data.anneeUniversitaire
    personnel.value = data.personnel
    modules.value = data.modules || []
  } catch (error) {
    console.error('Erreur lors du chargement des plans de cours:', error)
    showDanger('Erreur lors du chargement des plans de cours.')
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await loadData()
})

const stats = computed(() => {
  const total = modules.value.length
  const complet = modules.value.filter(m => m.planCours?.etatPlanCours === 'COMPLET').length
  const enCours = modules.value.filter(m => m.planCours?.etatPlanCours === 'EN_COURS').length
  const none = total - complet - enCours

  return { total, complet, enCours, none }
})

const filteredModules = computed(() => {
  return modules.value.filter(m => {
    // Statut
    if (selectedStatusFilter.value !== 'ALL') {
      const currentEtat = m.planCours?.etatPlanCours || 'NONE'
      if (currentEtat !== selectedStatusFilter.value) {
        return false
      }
    }

    // Recherche
    if (searchQuery.value.trim()) {
      const query = searchQuery.value.toLowerCase().trim()
      const code = (m.enseignement.codeEnseignement || '').toLowerCase()
      const libelle = (m.enseignement.libelle || '').toLowerCase()
      const semestre = (m.semestre?.libelle || '').toLowerCase()
      return code.includes(query) || libelle.includes(query) || semestre.includes(query)
    }

    return true
  })
})

const openShowDialog = async (item) => {
  if (!item.planCours?.id) return
  try {
    const fullPlan = await getPlanCoursService(item.planCours.id)
    selectedPlanCoursDetail.value = fullPlan
    selectedEnseignementDetail.value = item.enseignement
    showDialogVisible.value = true
  } catch (error) {
    showDanger('Impossible de charger le détail du plan de cours.')
  }
}

const openRecopieDialog = (item) => {
  if (!item.planCours?.id) return
  targetPlanCoursId.value = item.planCours.id
  recopieDialogVisible.value = true
}

const handleEditOrCreate = async (item) => {
  if (item.planCours?.id) {
    router.push(`/intranet/plan-de-cours/edit/${item.planCours.id}`)
  } else {
    // Initialiser le plan de cours pour ce module
    try {
      isLoading.value = true
      const activeAnneeId = anneeUnivStore.selectedAnneeUniv?.id || anneeUnivStore.anneeUniv?.id
      const res = await initPlanCoursModuleService(item.enseignement.id, activeAnneeId)
      showSuccess('Plan de cours initialisé.')
      router.push(`/intranet/plan-de-cours/edit/${res.id}`)
    } catch (error) {
      showDanger('Erreur lors de la création du plan de cours.')
      isLoading.value = false
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

const getEtatLabel = (etat) => {
  switch (etat) {
    case 'COMPLET': return 'Complet'
    case 'EN_COURS': return 'En cours'
    default: return 'Non commencé'
  }
}
</script>

<template>
  <div>
    <HeaderComponent
      icon="pi pi-book"
      titre="Plans de cours"
      :description="`Gestion et rédaction de vos plans de cours pour l'année universitaire ${anneeUniv?.libelle || ''}`"
    />

    <!-- Loader -->
    <div v-if="isLoading" class="flex justify-center p-12">
      <ProgressSpinner />
    </div>

    <div v-else class="flex flex-col gap-6">
      <!-- Cartes statistiques -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-color">Modules assignés</span>
            <div class="text-2xl font-bold text-surface-900 dark:text-surface-0 mt-1">{{ stats.total }}</div>
          </div>
          <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
            <i class="pi pi-th-large text-xl" />
          </div>
        </div>

        <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-color">Plans complets</span>
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.complet }}</div>
          </div>
          <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
            <i class="pi pi-check-circle text-xl" />
          </div>
        </div>

        <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex items-center justify-between">
          <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-muted-color">En cours de saisie</span>
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
            <i class="pi pi-exclamation-circle text-xl" />
          </div>
        </div>
      </div>

      <!-- Barre d'outils et filtres -->
      <div class="card p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="w-full md:w-80">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="searchQuery" placeholder="Rechercher par code ou intitulé..." class="w-full" />
          </IconField>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto">
          <Select
            v-model="selectedStatusFilter"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full md:w-52"
          />
          <Button icon="pi pi-refresh" severity="secondary" outlined @click="loadData" title="Actualiser" />
        </div>
      </div>

      <!-- Liste des modules -->
      <div v-if="filteredModules.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        <div
          v-for="item in filteredModules"
          :key="item.enseignement.id"
          class="card p-5 rounded-2xl border border-surface-200 dark:border-surface-700 hover:shadow-lg transition-all duration-200 flex flex-col justify-between"
        >
          <div>
            <!-- Badge type & état -->
            <div class="flex items-center justify-between gap-2 mb-3">
              <Tag :value="item.enseignement.typeLibelle" :severity="item.enseignement.typeBadge" class="text-xs font-semibold uppercase" />
              <Tag :value="getEtatLabel(item.planCours?.etatPlanCours)" :severity="getBadgeSeverity(item.planCours?.etatPlanCours)" />
            </div>

            <!-- Titre & code -->
            <div class="mb-3">
              <span class="text-xs font-mono font-bold text-primary">{{ item.enseignement.codeEnseignement }}</span>
              <h3 class="text-lg font-bold text-surface-900 dark:text-surface-0 leading-snug mt-0.5 line-clamp-2">
                {{ item.enseignement.libelle }}
              </h3>
              <p v-if="item.semestre" class="text-xs text-muted-color mt-1">
                {{ item.semestre.diplome?.libelle }} - <strong>{{ item.semestre.libelle }}</strong>
              </p>
            </div>

            <!-- Détails métriques -->
            <div class="p-3 rounded-xl bg-surface-50 dark:bg-surface-800/60 border border-surface-100 dark:border-surface-700/60 mb-4 flex items-center justify-between text-xs">
              <div>
                <span class="text-muted-color block">Rôle</span>
                <span class="font-semibold">{{ item.isReferent ? 'Référent' : 'Intervenant' }}</span>
              </div>
              <div v-if="item.planCours">
                <span class="text-muted-color block">Séquences</span>
                <span class="font-semibold">{{ item.planCours.nbSequences }} séance(s)</span>
              </div>
              <div v-if="item.planCours?.updatedAt">
                <span class="text-muted-color block">Mis à jour</span>
                <span class="font-semibold">{{ item.planCours.updatedAt }}</span>
              </div>
            </div>
          </div>

          <!-- Boutons d'action -->
          <div class="flex items-center gap-2 pt-3 border-t border-surface-100 dark:border-surface-800">
            <Button
              :label="item.planCours ? 'Modifier' : 'Rédiger'"
              :icon="item.planCours ? 'pi pi-pencil' : 'pi pi-plus'"
              severity="primary"
              class="flex-1"
              @click="handleEditOrCreate(item)"
            />

            <Button
              v-if="item.planCours"
              icon="pi pi-eye"
              severity="secondary"
              outlined
              @click="openShowDialog(item)"
              title="Consulter le plan de cours"
            />

            <Button
              v-if="item.planCours"
              icon="pi pi-copy"
              severity="secondary"
              outlined
              @click="openRecopieDialog(item)"
              title="Recopier depuis un autre module"
            />
          </div>
        </div>
      </div>

      <div v-else class="card p-12 text-center rounded-2xl border border-surface-200 dark:border-surface-700">
        <i class="pi pi-inbox text-4xl text-muted-color mb-3" />
        <h4 class="text-lg font-bold text-surface-900 dark:text-surface-0">Aucun enseignement trouvé</h4>
        <p class="text-sm text-muted-color max-w-md mx-auto mt-1">
          Aucun module ne correspond à vos critères de recherche pour cette année universitaire.
        </p>
      </div>
    </div>

    <!-- Dialogs -->
    <PlanCoursShowDialog
      v-model:visible="showDialogVisible"
      :planCours="selectedPlanCoursDetail"
      :enseignement="selectedEnseignementDetail"
      @edit="(id) => router.push(`/intranet/plan-de-cours/edit/${id}`)"
    />

    <PlanCoursRecopieDialog
      v-model:visible="recopieDialogVisible"
      :targetPlanCoursId="targetPlanCoursId"
      :availablePlans="modules"
      @recopied="loadData"
    />
  </div>
</template>
