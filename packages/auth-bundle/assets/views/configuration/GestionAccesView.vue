<script setup>
import CustomDataTable from '@components/components/CustomDataTable.vue'
import { ref, onMounted, computed, watch } from 'vue'
import api from '@helpers/axios'
import { useToast } from 'primevue/usetoast'
import appsList from '@config/tools.generated.json'

const refreshKey = ref(0)
const toast = useToast()

// Dialog states
const showEditDialog = ref(false)
const showAddDialog = ref(false)
const activeTab = ref(0)
const loading = ref(false)

// Select options
const departmentsList = ref([])

const availablePermissionsByPackage = ref({})

// Current edited Personnel
const selectedPersonnel = ref(null)
const personnelRoles = ref([])
const personnelAffectations = ref([])
const selectedDeptForNewAffectation = ref(null)

// Current selected affectation for app rights editing
const selectedAffectationForRights = ref(null)

// Search Personnel (for adding new)
const searchPersonnelQuery = ref('')
const searchedPersonnelsList = ref([])
const selectedPersonnelToAdd = ref(null)
const selectedDeptToAdd = ref(null)

const statutOptions = [
  { value: 'MCF', label: 'Maître de conférences (MCF)' },
  { value: 'PU', label: 'Professeur des universités (PU)' },
  { value: 'ATER', label: 'Attaché temporaire (ATER)' },
  { value: 'PRAG', label: 'Professeur agrégé (PRAG)' },
  { value: 'IE', label: 'Ingénieur d\'études (IE)' },
  { value: 'ENSAM', label: 'Enseignant associé (ENSAM)' },
  { value: 'DO', label: 'Doctorant (DO)' },
  { value: 'vacataire', label: 'Enseignant Vacataire' },
  { value: 'contractuel', label: 'Contractuel' },
  { value: 'PRCE', label: 'Professeur certifié (PRCE)' },
  { value: 'BIATSS', label: 'Personnel Biatss' },
  { value: 'PRO', label: 'Intervenant Professionnel' },
  { value: 'TEC', label: 'Technicien (TEC)' },
  { value: 'ADM', label: 'Administratif (ADM)' },
  { value: 'ASS', label: 'Assistant (ASS)' },
  { value: 'PR', label: 'Pro (PR)' },
  { value: 'PEPS', label: 'Peps (PEPS)' },
  { value: 'Profession', label: 'Pro (Profession)' },
  { value: 'PRCACDD', label: 'PrcaCdd (PRCACDD)' },
  { value: 'CONTRAC', label: 'Contractuel (CONTRAC)' },
  { value: 'Autre', label: 'Autre' }
]

const isSuperAdminChecked = computed({
  get: () => selectedPersonnel.value?.roles?.includes('ROLE_SUPER_ADMIN') ?? false,
  set: (val) => {
    if (!selectedPersonnel.value.roles) {
      selectedPersonnel.value.roles = []
    }
    if (val) {
      if (!selectedPersonnel.value.roles.includes('ROLE_SUPER_ADMIN')) {
        selectedPersonnel.value.roles.push('ROLE_SUPER_ADMIN')
      }
    } else {
      selectedPersonnel.value.roles = selectedPersonnel.value.roles.filter(r => r !== 'ROLE_SUPER_ADMIN')
    }
  }
})

const selectedDefaultAffId = computed(() => {
  return personnelAffectations.value.find(aff => aff.defaut)?.id || null
})

const toggleAuthorizedApp = (appSlug, checked) => {
  if (!selectedPersonnel.value.applications) {
    selectedPersonnel.value.applications = []
  }
  if (checked) {
    if (!selectedPersonnel.value.applications.includes(appSlug)) {
      selectedPersonnel.value.applications.push(appSlug)
    }
  } else {
    selectedPersonnel.value.applications = selectedPersonnel.value.applications.filter(s => s !== appSlug)
    
    // Auto-cleanup from all affectations if unchecked
    const pkgName = appSlug === 'stage' ? 'stages' : appSlug
    personnelAffectations.value.forEach(aff => {
      if (aff.packages) {
        aff.packages = aff.packages.filter(p => p !== pkgName)
      }
      if (aff.permissions && availablePermissionsByPackage.value[pkgName]) {
        const packageRoleList = availablePermissionsByPackage.value[pkgName].map(p => p.role)
        aff.permissions = aff.permissions.filter(role => !packageRoleList.includes(role))
      }
    })
  }
}

const isPackageAllowed = (pkgName) => {
  if (pkgName === 'core') return true
  const appSlug = pkgName === 'stages' ? 'stage' : pkgName
  return selectedPersonnel.value?.applications?.includes(appSlug) ?? false
}

const savePersonnelInfo = async () => {
  loading.value = true
  try {
    await api.patch(`/api/personnels/${selectedPersonnel.value.id}`, {
      prenom: selectedPersonnel.value.prenom,
      nom: selectedPersonnel.value.nom,
      mailUniv: selectedPersonnel.value.mailUniv,
      mailPerso: selectedPersonnel.value.mailPerso,
      bureau: selectedPersonnel.value.bureau,
      telBureau: selectedPersonnel.value.telBureau,
      statut: selectedPersonnel.value.statut,
      roles: selectedPersonnel.value.roles,
      applications: selectedPersonnel.value.applications
    }, {
      headers: {
        'Content-Type': 'application/merge-patch+json'
      }
    })
    toast.add({ severity: 'success', summary: 'Sauvegardé', detail: 'Informations et accès globaux mis à jour.', life: 3000 })
    refreshKey.value++
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de la sauvegarde.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const getRawRolesString = (aff, appSlug) => {
  if (!aff || !aff.roles || Array.isArray(aff.roles)) return ''
  const val = aff.roles[appSlug]
  return Array.isArray(val) ? val.join(', ') : ''
}

const setRawRolesString = (aff, appSlug, value) => {
  if (!aff.roles || Array.isArray(aff.roles)) {
    aff.roles = {}
  }
  aff.roles[appSlug] = value.split(',').map(s => s.trim()).filter(Boolean)
}

// Fetch departments at mount
onMounted(async () => {
  try {
    const res = await api.get('/api/structure_departements')
    departmentsList.value = res.data['hydra:member'] ?? res.data.member ?? res.data ?? []

    const resPerm = await api.get('/api/security/permissions')
    availablePermissionsByPackage.value = resPerm.data ?? {}
  } catch (err) {
    console.error('Error loading departments or permissions:', err)
  }
})

// Open Add modal
const openModalNew = () => {
  searchPersonnelQuery.value = ''
  searchedPersonnelsList.value = []
  selectedPersonnelToAdd.value = null
  selectedDeptToAdd.value = null
  showAddDialog.value = true
}

// Search personnels
const searchPersonnels = async () => {
  if (searchPersonnelQuery.value.trim().length < 2) {
    toast.add({ severity: 'warn', summary: 'Recherche', detail: 'Saisissez au moins 2 caractères', life: 3000 })
    return
  }
  try {
    const res = await api.get('/api/personnels', {
      params: {
        nom: searchPersonnelQuery.value
      }
    })
    searchedPersonnelsList.value = res.data['hydra:member'] ?? res.data.member ?? res.data ?? []
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de la recherche', life: 3000 })
  }
}

// Create new affectation
const createAffectation = async () => {
  if (!selectedPersonnelToAdd.value || !selectedDeptToAdd.value) {
    toast.add({ severity: 'warn', summary: 'Champs requis', detail: 'Veuillez sélectionner un personnel et un département.', life: 3000 })
    return
  }
  loading.value = true
  try {
    const payload = {
      personnel: `/api/personnels/${selectedPersonnelToAdd.value.id}`,
      departement: `/api/structure_departements/${selectedDeptToAdd.value.id}`,
      defaut: false,
      affectation: true,
      packages: ['core'],
      permissions: ['ROLE_TEACHER']
    }
    await api.post('/api/structure_departement_personnels', payload, {
      headers: {
        'Content-Type': 'application/ld+json'
      }
    })
    toast.add({ severity: 'success', summary: 'Créé', detail: 'Affectation créée avec succès', life: 3000 })
    showAddDialog.value = false
    refreshKey.value++
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de créer l\'affectation.', life: 3000 })
  } finally {
    loading.value = false
  }
}

// Open Edit modal
const openModalUpdate = async (sdpRecord) => {
  loading.value = true
  activeTab.value = 0
  selectedPersonnel.value = sdpRecord.personnel
  selectedDeptForNewAffectation.value = null
  selectedAffectationForRights.value = null
  
  try {
    // 1. Get detailed personnel for global roles
    const resPers = await api.get(`/api/personnels/${sdpRecord.personnel.id}`)
    selectedPersonnel.value = resPers.data
    personnelRoles.value = resPers.data.roles ?? []

    // 2. Get all affectations of this personnel
    await loadPersonnelAffectations()
    
    showEditDialog.value = true
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur de chargement des données.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const loadPersonnelAffectations = async () => {
  const resAff = await api.get(`/api/structure_departement_personnels`, {
    params: {
      personnel: selectedPersonnel.value.id
    }
  })
  personnelAffectations.value = resAff.data['hydra:member'] ?? resAff.data.member ?? resAff.data ?? []
}

const activePersonnelAffectations = computed(() => {
  return personnelAffectations.value.filter(aff => aff.affectation)
})

watch(activePersonnelAffectations, (newActive) => {
  if (!selectedAffectationForRights.value || !newActive.some(a => a.id === selectedAffectationForRights.value.id)) {
    const defaultAff = newActive.find(aff => aff.defaut)
    selectedAffectationForRights.value = defaultAff || newActive[0] || null
  }
}, { immediate: true })



// Add department affectation to current personnel
const addAffectationToPersonnel = async () => {
  if (!selectedDeptForNewAffectation.value) {
    toast.add({ severity: 'warn', summary: 'Département requis', detail: 'Sélectionnez un département.', life: 3000 })
    return
  }
  loading.value = true
  try {
    const payload = {
      personnel: `/api/personnels/${selectedPersonnel.value.id}`,
      departement: `/api/structure_departements/${selectedDeptForNewAffectation.value.id}`,
      defaut: false,
      affectation: true,
      packages: ['core'],
      permissions: ['ROLE_TEACHER']
    }
    await api.post('/api/structure_departement_personnels', payload, {
      headers: {
        'Content-Type': 'application/ld+json'
      }
    })
    toast.add({ severity: 'success', summary: 'Ajouté', detail: 'Département ajouté avec succès.', life: 3000 })
    selectedDeptForNewAffectation.value = null
    await loadPersonnelAffectations()
    refreshKey.value++
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de l\'ajout du département.', life: 3000 })
  } finally {
    loading.value = false
  }
}

// Delete affectation
const deleteAffectation = async (affectation) => {
  const idToDelete = affectation.id ?? affectation;
  loading.value = true
  try {
    await api.delete(`/api/structure_departement_personnels/${idToDelete}`)
    toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Affectation supprimée.', life: 3000 })
    if (showEditDialog.value) {
      await loadPersonnelAffectations()
    }
    refreshKey.value++
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de la suppression.', life: 3000 })
  } finally {
    loading.value = false
  }
}

// Toggle default status
const toggleDefaultAffectation = async (targetAff) => {
  loading.value = true
  try {
    // 1. Set current to default: true
    await api.patch(`/api/structure_departement_personnels/${targetAff.id}`, {
      defaut: true
    }, {
      headers: {
        'Content-Type': 'application/merge-patch+json'
      }
    })
    
    // 2. Set all other affectations for this personnel to default: false
    for (const aff of personnelAffectations.value) {
      if (aff.id !== targetAff.id && aff.defaut) {
        await api.patch(`/api/structure_departement_personnels/${aff.id}`, {
          defaut: false
        }, {
          headers: {
            'Content-Type': 'application/merge-patch+json'
          }
        })
      }
    }
    
    toast.add({ severity: 'success', summary: 'Sauvegardé', detail: 'Département par défaut mis à jour.', life: 3000 })
    await loadPersonnelAffectations()
    refreshKey.value++
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur de mise à jour.', life: 3000 })
  } finally {
    loading.value = false
  }
}

// Toggle active affectation status
const toggleAffectationActive = async (aff) => {
  loading.value = true
  try {
    await api.patch(`/api/structure_departement_personnels/${aff.id}`, {
      affectation: aff.affectation
    }, {
      headers: {
        'Content-Type': 'application/merge-patch+json'
      }
    })
    toast.add({ severity: 'success', summary: 'Sauvegardé', detail: 'Statut de l\'affectation mis à jour.', life: 3000 })
    refreshKey.value++
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur de mise à jour.', life: 3000 })
  } finally {
    loading.value = false
  }
}

// Helper to check if role exists for app inside affectation
const hasPackage = (aff, packageName) => {
  if (!aff) return false
  if (!aff.packages) {
    aff.packages = []
  }
  return aff.packages.includes(packageName)
}

const togglePackage = (aff, packageName) => {
  if (!aff) return
  if (!aff.packages) {
    aff.packages = []
  }
  const index = aff.packages.indexOf(packageName)
  if (index === -1) {
    aff.packages.push(packageName)
  } else {
    aff.packages.splice(index, 1)
    if (availablePermissionsByPackage.value[packageName]) {
      const packageRoleList = availablePermissionsByPackage.value[packageName].map(p => p.role)
      aff.permissions = (aff.permissions || []).filter(role => !packageRoleList.includes(role))
    }
  }
}

const hasPermission = (aff, permissionRole) => {
  if (!aff) return false
  if (!aff.permissions) {
    aff.permissions = []
  }
  return aff.permissions.includes(permissionRole)
}

const togglePermission = (aff, permissionRole) => {
  if (!aff) return
  if (!aff.permissions) {
    aff.permissions = []
  }
  const index = aff.permissions.indexOf(permissionRole)
  if (index === -1) {
    aff.permissions.push(permissionRole)
  } else {
    aff.permissions.splice(index, 1)
  }
}

const saveAppRights = async () => {
  if (!selectedAffectationForRights.value) return
  loading.value = true
  try {
    await api.patch(`/api/structure_departement_personnels/${selectedAffectationForRights.value.id}`, {
      packages: selectedAffectationForRights.value.packages || [],
      permissions: selectedAffectationForRights.value.permissions || []
    }, {
      headers: {
        'Content-Type': 'application/merge-patch+json'
      }
    })
    toast.add({ severity: 'success', summary: 'Sauvegardé', detail: 'Packages et permissions par département mis à jour.', life: 3000 })
    await loadPersonnelAffectations()
    refreshKey.value++
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de la sauvegarde des droits.', life: 3000 })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Toast />
  <div class="mx-auto space-y-6">
    <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
      <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
        <i class="pi pi-lock text-violet-600"></i>
        <span>Gestion des Accès</span>
      </h1>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
        Gérez les affectations des personnels aux départements, leurs rôles globaux et leurs droits d'applications.
      </p>
    </div>

    <CustomDataTable
        :columns="[
        { field: 'personnel.nom', header: 'Nom', sortable: true },
        { field: 'personnel.prenom', header: 'Prénom', sortable: true },
        { field: 'personnel.mailUniv', header: 'Email', sortable: true },
        { field: 'personnel.statut', header: 'Statut', sortable: true },
        { field: 'departement.libelle', header: 'Département d\'Affectation', sortable: true },
        { field: 'defaut', header: 'Par Défaut', type: 'boolean', handler: toggleDefaultAffectation },
        { field: 'affectation', header: 'Active', type: 'boolean', handler: toggleAffectationActive },
      ]"
        :actions="[
        { type: 'edit', handler: openModalUpdate },
        { type: 'delete', handler: deleteAffectation },
      ]"
        :actionAdd="{ handler: openModalNew }"
        apiEndpoint="api/structure_departement_personnels"
        searchParameter="personnel.nom"
        :refreshKey="refreshKey"
    />

    <!-- Modal : Ajouter un personnel à un département -->
    <Dialog v-model:visible="showAddDialog" modal header="Affecter un Personnel" :style="{ width: '500px' }" class="dark:bg-slate-800 dark:text-white">
      <div class="space-y-4 py-4 text-xs">
        <div class="flex flex-col gap-1.5">
          <label class="font-bold text-slate-500">Rechercher un personnel (par Nom)</label>
          <div class="flex gap-2">
            <InputText v-model="searchPersonnelQuery" placeholder="Ex: ANNEBICQUE" class="flex-1 p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 text-xs" @keydown.enter="searchPersonnels" />
            <Button label="Chercher" icon="pi pi-search" class="p-button-sm bg-violet-600 border-none rounded-lg text-white" @click="searchPersonnels" />
          </div>
        </div>

        <!-- Search Results -->
        <div v-if="searchedPersonnelsList.length > 0" class="max-h-[150px] overflow-y-auto border border-slate-100 dark:border-slate-800 rounded-xl p-2 bg-slate-50/50 dark:bg-slate-900/50 space-y-1">
          <div 
            v-for="p in searchedPersonnelsList" 
            :key="p.id" 
            @click="selectedPersonnelToAdd = p"
            :class="['p-2 rounded-lg cursor-pointer transition-all flex justify-between items-center', selectedPersonnelToAdd?.id === p.id ? 'bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-bold' : 'hover:bg-slate-100 dark:hover:bg-slate-800']"
          >
            <span>{{ p.prenom }} {{ p.nom }}</span>
            <span class="text-[9px] text-slate-400 font-normal">{{ p.mailUniv }}</span>
          </div>
        </div>

        <div v-if="selectedPersonnelToAdd" class="p-3 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-300 rounded-xl border border-emerald-100/40">
          <strong>Personnel sélectionné :</strong> {{ selectedPersonnelToAdd.prenom }} {{ selectedPersonnelToAdd.nom }}
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="font-bold text-slate-500">Département d'affectation</label>
          <Select 
            v-model="selectedDeptToAdd" 
            :options="departmentsList" 
            optionLabel="libelle" 
            placeholder="Sélectionnez un département" 
            class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs"
          />
        </div>

        <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-700/60">
          <Button label="Annuler" class="p-button-text text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 border-none" @click="showAddDialog = false" />
          <Button label="Créer l'affectation" icon="pi pi-check" class="bg-violet-600 border-none rounded-lg text-white" :loading="loading" @click="createAffectation" />
        </div>
      </div>
    </Dialog>

    <!-- Modal : Modifier un personnel & ses accès -->
    <Dialog v-model:visible="showEditDialog" modal header="Gestion Individuelle des Accès" :style="{ width: '800px' }" class="dark:bg-slate-800 dark:text-white">
      <div v-if="selectedPersonnel" class="space-y-4 py-2 text-xs">
        
        <!-- Header Info -->
        <div class="flex items-center gap-4 bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800 p-4 rounded-2xl">
          <div class="w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 flex items-center justify-center font-bold text-sm uppercase shrink-0">
            {{ selectedPersonnel.prenom?.[0] }}{{ selectedPersonnel.nom?.[0] }}
          </div>
          <div>
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">
              {{ selectedPersonnel.prenom }} {{ selectedPersonnel.nom }}
            </h2>
            <span class="text-[10px] text-slate-400 block">{{ selectedPersonnel.mailUniv }}</span>
          </div>
        </div>

        <!-- Custom Tabs Navigation -->
        <div class="flex gap-2 border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
          <button 
            v-for="(tab, index) in ['Informations & Applications', 'Affectations Départements', 'Droits par Département']" 
            :key="index"
            @click="activeTab = index"
            :class="['px-4 py-2 text-xs font-bold rounded-lg transition-all', activeTab === index ? 'bg-violet-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300']"
          >
            {{ tab }}
          </button>
        </div>

        <!-- Tab 0: Informations & Applications -->
        <div v-if="activeTab === 0" class="space-y-4">
          <div class="p-4 bg-slate-50/50 dark:bg-slate-900/30 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-4">
            
            <div>
              <h3 class="font-bold text-slate-800 dark:text-slate-200 mb-3">
                Informations du Personnel
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500">Prénom</label>
                  <InputText v-model="selectedPersonnel.prenom" placeholder="Prénom" class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-xs" />
                </div>
                
                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500">Nom</label>
                  <InputText v-model="selectedPersonnel.nom" placeholder="Nom" class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-xs" />
                </div>
                
                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500">Nom d'utilisateur (Identifiant de connexion)</label>
                  <InputText v-model="selectedPersonnel.username" disabled class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs text-slate-400" />
                </div>
                
                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500">Email Universitaire</label>
                  <InputText v-model="selectedPersonnel.mailUniv" placeholder="email@univ-reims.fr" class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-xs" />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500">Email Personnel</label>
                  <InputText v-model="selectedPersonnel.mailPerso" placeholder="email.perso@gmail.com" class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-xs" />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500">Téléphone Bureau</label>
                  <InputText v-model="selectedPersonnel.telBureau" placeholder="03 26 XX XX XX" class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-xs" />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500">Bureau</label>
                  <InputText v-model="selectedPersonnel.bureau" placeholder="Ex: B102" class="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-xs" />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500">Statut Professionnel</label>
                  <Select 
                    v-model="selectedPersonnel.statut" 
                    :options="statutOptions" 
                    optionValue="value"
                    optionLabel="label" 
                    placeholder="Sélectionnez un statut" 
                    class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs"
                  />
                </div>
              </div>
            </div>

            <Divider />

            <div>
              <h3 class="font-bold text-slate-800 dark:text-slate-200 mb-1">
                Rôles Globaux
              </h3>
              <p class="text-[10px] text-slate-400 mb-3">
                Ces rôles s'appliquent globalement à l'ensemble du portail.
              </p>
              <div class="flex items-center gap-2 py-1">
                <Checkbox v-model="isSuperAdminChecked" :binary="true" inputId="is-super-admin-cb" />
                <label for="is-super-admin-cb" class="cursor-pointer text-[11px] font-bold text-slate-700 dark:text-slate-300">
                  Super Administrateur (Accès global complet)
                </label>
              </div>
            </div>

            <Divider />

            <div>
              <h3 class="font-bold text-slate-800 dark:text-slate-200 mb-1">
                Applications Autorisées
              </h3>
              <p class="text-[10px] text-slate-400 mb-3">
                Cochez les applications auxquelles ce personnel a le droit d'accéder.
              </p>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-for="app in appsList" :key="app.urlSlug" class="flex items-start gap-2.5 p-2.5 bg-white dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800/80 rounded-xl">
                  <Checkbox 
                    :binary="true"
                    :modelValue="selectedPersonnel.applications?.includes(app.urlSlug) ?? false" 
                    @update:modelValue="(checked) => toggleAuthorizedApp(app.urlSlug, checked)"
                    :inputId="`app-${app.urlSlug}`"
                    class="mt-0.5"
                  />
                  <label :for="`app-${app.urlSlug}`" class="cursor-pointer text-[11px] text-slate-700 dark:text-slate-300 flex-1">
                    <span class="font-bold block text-slate-800 dark:text-slate-100">{{ app.name }}</span>
                    <span class="text-[9px] text-slate-400 leading-tight block mt-0.5">{{ app.description }}</span>
                  </label>
                </div>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
              <Button label="Sauvegarder les Informations & Accès" icon="pi pi-save" class="bg-violet-600 border-none rounded-lg p-button-sm text-white" :loading="loading" @click="savePersonnelInfo" />
            </div>
          </div>
        </div>

        <!-- Tab 1: Department Affectations -->
        <div v-if="activeTab === 1" class="space-y-4">
          
          <!-- Add New Department Form -->
          <div class="p-4 bg-slate-50/50 dark:bg-slate-900/30 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-end gap-4">
            <div class="flex-1 flex flex-col gap-1.5">
              <label class="font-bold text-slate-500">Ajouter une affectation de département</label>
              <Select 
                v-model="selectedDeptForNewAffectation" 
                :options="departmentsList" 
                optionLabel="libelle" 
                placeholder="Sélectionnez un département à affecter" 
                class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-lg text-xs"
              />
            </div>
            <Button label="Ajouter" icon="pi pi-plus" class="bg-violet-600 border-none rounded-lg p-button-sm h-max text-white" :loading="loading" @click="addAffectationToPersonnel" />
          </div>

          <!-- Existing Affectations Table -->
          <div class="border border-slate-100 dark:border-slate-800 rounded-2xl overflow-hidden">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-100 dark:border-slate-800">
                  <th class="p-3 font-bold text-slate-600 dark:text-slate-300">Département</th>
                  <th class="p-3 font-bold text-slate-600 dark:text-slate-300 text-center">Par Défaut</th>
                  <th class="p-3 font-bold text-slate-600 dark:text-slate-300 text-center">Active</th>
                  <th class="p-3 font-bold text-slate-600 dark:text-slate-300 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="aff in personnelAffectations" :key="aff.id" class="border-b border-slate-50 dark:border-slate-800/40 hover:bg-slate-50/50 dark:hover:bg-slate-900/20">
                  <td class="p-3 font-bold text-slate-800 dark:text-slate-200">
                    {{ aff.departement?.libelle || 'Inconnu' }}
                  </td>
                  <td class="p-3 text-center">
                    <RadioButton :modelValue="selectedDefaultAffId" :value="aff.id" name="default_department" @update:modelValue="toggleDefaultAffectation(aff)" />
                  </td>
                  <td class="p-3 text-center">
                    <ToggleSwitch v-model="aff.affectation" @change="toggleAffectationActive(aff)" />
                  </td>
                  <td class="p-3 text-right">
                    <Button icon="pi pi-trash" severity="danger" class="p-button-rounded p-button-text p-button-sm border-none text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/20" @click="deleteAffectation(aff)" />
                  </td>
                </tr>
                <tr v-if="personnelAffectations.length === 0">
                  <td colspan="4" class="p-4 text-center text-slate-400">Aucun département affecté.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab 2: Application Rights by Department -->
        <div v-if="activeTab === 2" class="space-y-4">
          <div v-if="activePersonnelAffectations.length === 0" class="p-6 text-center text-slate-400 border border-slate-100 dark:border-slate-800 rounded-2xl">
            Affectez d'abord le personnel activement à un département pour pouvoir y paramétrer ses droits d'applications.
          </div>
          <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- Left col: Department selection list -->
            <div class="space-y-2">
              <span class="font-bold text-slate-400 block pb-1">1. Choisissez le département</span>
              <div 
                v-for="aff in activePersonnelAffectations" 
                :key="aff.id"
                @click="selectedAffectationForRights = aff"
                :class="['p-3 rounded-xl border cursor-pointer transition-all flex justify-between items-center', selectedAffectationForRights?.id === aff.id ? 'bg-violet-50 dark:bg-violet-950/30 border-violet-200 dark:border-violet-800 text-violet-700 dark:text-violet-400 font-bold' : 'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 text-slate-700 dark:text-slate-300']"
              >
                <span>{{ aff.departement?.libelle }}</span>
              </div>
            </div>

            <!-- Right col: Rights checkbox list -->
            <div v-if="selectedAffectationForRights" class="col-span-2 space-y-4 border border-slate-100 dark:border-slate-800 rounded-2xl p-4 bg-slate-50/50 dark:bg-slate-900/30 max-h-[350px] overflow-y-auto">
              <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <span class="font-bold text-slate-800 dark:text-slate-200">
                  2. Packages et Droits sur {{ selectedAffectationForRights.departement?.libelle }}
                </span>
                <Button label="Sauvegarder ces Droits" icon="pi pi-save" class="bg-violet-600 border-none rounded-lg p-button-sm text-[10px] text-white" :loading="loading" @click="saveAppRights" />
              </div>

              <!-- Loop over each package to show toggles and permissions -->
              <template v-for="(perms, pkgName) in availablePermissionsByPackage" :key="pkgName">
                <div v-if="isPackageAllowed(pkgName)" class="space-y-2 bg-white dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800/80 rounded-xl p-3">
                  <div class="flex items-center justify-between border-b border-slate-50 dark:border-slate-800 pb-1.5">
                    <span class="font-bold text-slate-700 dark:text-slate-300 text-[11px] capitalize flex items-center gap-1.5">
                      <i class="pi pi-box text-violet-500"></i>
                      <span>{{ pkgName }}</span>
                    </span>
                    <div class="flex items-center gap-1.5">
                      <label class="text-[9px] text-slate-400">Activer le package</label>
                      <ToggleSwitch 
                        :modelValue="hasPackage(selectedAffectationForRights, pkgName)"
                        @update:modelValue="togglePackage(selectedAffectationForRights, pkgName)"
                      />
                    </div>
                  </div>

                  <!-- Display checkable permissions if package is active -->
                  <div v-if="hasPackage(selectedAffectationForRights, pkgName)" class="grid grid-cols-2 gap-2 pt-1">
                    <div v-for="perm in perms" :key="perm.role" class="flex items-center gap-1.5">
                      <Checkbox 
                        :binary="true"
                        :modelValue="hasPermission(selectedAffectationForRights, perm.role)"
                        @update:modelValue="togglePermission(selectedAffectationForRights, perm.role)" 
                        :inputId="`${pkgName}-${perm.role}`"
                      />
                      <label :for="`${pkgName}-${perm.role}`" class="cursor-pointer text-[10px] text-slate-600 dark:text-slate-400">
                        {{ perm.label }} <span class="text-[8px] text-slate-400 font-mono">({{ perm.role }})</span>
                      </label>
                    </div>
                  </div>
                  <div v-else class="text-[10px] text-slate-400 italic py-1">
                    Activez ce package pour configurer ses permissions.
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>

      </div>
    </Dialog>
  </div>
</template>

<style scoped>
</style>
