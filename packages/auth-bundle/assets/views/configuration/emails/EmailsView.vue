<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@helpers/axios'
import { useToast } from 'primevue/usetoast'

const router = useRouter()
const toast = useToast()

const loading = ref(true)
const definitions = ref([])             // [{bundle, items:[{key,label,description,isCustomized,...}]}]
const departements = ref([])
const selectedDepartement = ref(null)
const searchQuery = ref('')

// Charge les départements disponibles
const loadDepartements = async () => {
  try {
    const res = await api.get('/api/email/departements')
    departements.value = res.data
  } catch (e) {
    console.error('Erreur chargement départements', e)
  }
}

// Charge les définitions d'emails (avec statut de personnalisation pour le dépt sélectionné)
const loadDefinitions = async () => {
  loading.value = true
  try {
    const params = selectedDepartement.value ? { departement: selectedDepartement.value.id } : {}
    const res = await api.get('/api/email/definitions', { params })
    definitions.value = res.data
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les définitions d\'emails.', life: 3000 })
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadDepartements()
  await loadDefinitions()
})

// Recharge quand le département change
const onDepartementChange = () => {
  loadDefinitions()
}

// Filtre par recherche texte
const filteredDefinitions = computed(() => {
  if (!searchQuery.value.trim()) return definitions.value
  const q = searchQuery.value.toLowerCase()
  return definitions.value
    .map(group => ({
      ...group,
      items: group.items.filter(item =>
        item.label.toLowerCase().includes(q) ||
        item.key.toLowerCase().includes(q) ||
        item.description?.toLowerCase().includes(q)
      )
    }))
    .filter(group => group.items.length > 0)
})

const totalEmails = computed(() => definitions.value.reduce((acc, g) => acc + g.items.length, 0))
const customizedCount = computed(() => definitions.value.reduce((acc, g) => acc + g.items.filter(i => i.isCustomized).length, 0))

const goToEdit = (item) => {
  const encodedKey = encodeURIComponent(item.key)
  const query = selectedDepartement.value ? { departement: selectedDepartement.value.id } : {}
  router.push({ name: 'email-edit', params: { key: encodedKey }, query })
}

const bundleColor = (bundle) => {
  const colors = {
    Core: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    Questionnaire: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
    Helpdesk: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    Stage: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    Edt: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    Intranet: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    Unifolio: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300',
  }
  return colors[bundle] ?? 'bg-slate-100 text-slate-700'
}
</script>

<template>
  <Toast />
  <div class="mx-auto space-y-6">

    <!-- Header -->
    <div class="border-b border-slate-100 dark:border-slate-800 pb-4 flex items-start justify-between">
      <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <i class="pi pi-envelope text-teal-600"></i>
          <span>Modèles de mails</span>
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Personnalisez les emails envoyés par chaque application, département par département.
        </p>
      </div>
      <!-- Stats -->
      <div class="flex gap-3">
        <div class="text-center px-4 py-2 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
          <div class="text-2xl font-black text-slate-800 dark:text-white">{{ totalEmails }}</div>
          <div class="text-[10px] text-slate-400">modèles au total</div>
        </div>
        <div class="text-center px-4 py-2 bg-teal-50 dark:bg-teal-900/20 rounded-xl border border-teal-100 dark:border-teal-800/40">
          <div class="text-2xl font-black text-teal-600 dark:text-teal-400">{{ customizedCount }}</div>
          <div class="text-[10px] text-teal-600/70 dark:text-teal-400/70">personnalisés</div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
      <!-- Sélecteur de département -->
      <div class="flex flex-col gap-1 flex-1">
        <label class="text-xs font-bold text-slate-500">Filtrer par département</label>
        <Select
          v-model="selectedDepartement"
          :options="departements"
          optionLabel="libelle"
          placeholder="Tous les départements (défauts)"
          showClear
          class="w-full text-sm"
          @change="onDepartementChange"
        />
      </div>
      <!-- Recherche texte -->
      <div class="flex flex-col gap-1 flex-1">
        <label class="text-xs font-bold text-slate-500">Rechercher un modèle</label>
        <InputText
          v-model="searchQuery"
          placeholder="Nom, clé ou description..."
          class="w-full text-sm"
        />
      </div>
    </div>

    <!-- Context banner département -->
    <div
      v-if="selectedDepartement"
      class="flex items-center gap-2 px-4 py-2.5 bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800/40 rounded-xl text-xs text-teal-700 dark:text-teal-300"
    >
      <i class="pi pi-info-circle"/>
      <span>
        Vous visualisez les personnalisations pour <strong>{{ selectedDepartement.libelle }}</strong>.
        Les modèles sans personnalisation utilisent le template par défaut de l'application.
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <ProgressSpinner strokeWidth="3" class="w-12 h-12" />
    </div>

    <!-- Definitions list -->
    <div v-else class="space-y-6">
      <div v-for="group in filteredDefinitions" :key="group.bundle" class="space-y-3">

        <!-- Bundle header -->
        <div class="flex items-center gap-2">
          <span :class="['px-2.5 py-1 rounded-lg text-[11px] font-bold', bundleColor(group.bundle)]">
            {{ group.bundle }}
          </span>
          <span class="text-xs text-slate-400">{{ group.items.length }} modèle{{ group.items.length > 1 ? 's' : '' }}</span>
          <div class="flex-1 h-px bg-slate-100 dark:bg-slate-800 ml-1"/>
        </div>

        <!-- Email cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
          <div
            v-for="item in group.items"
            :key="item.key"
            class="relative bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl p-4 hover:shadow-md hover:border-slate-200 dark:hover:border-slate-700 transition-all cursor-pointer group"
            @click="goToEdit(item)"
          >
            <!-- Customized badge -->
            <div
              v-if="item.isCustomized"
              class="absolute top-3 right-3 flex items-center gap-1 px-2 py-0.5 bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 rounded-full text-[9px] font-bold"
            >
              <i class="pi pi-check text-[8px]"/>
              Personnalisé
            </div>
            <div
              v-else
              class="absolute top-3 right-3 px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-full text-[9px] font-medium"
            >
              Par défaut
            </div>

            <!-- Icon + title -->
            <div class="flex items-start gap-3 pr-20">
              <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                <i class="pi pi-envelope text-slate-500 dark:text-slate-400 text-sm"/>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 leading-tight">
                  {{ item.label }}
                </h3>
                <p v-if="item.description" class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                  {{ item.description }}
                </p>
              </div>
            </div>

            <!-- Subject preview -->
            <div class="mt-3 pt-3 border-t border-slate-50 dark:border-slate-800">
              <div class="text-[10px] text-slate-400 font-medium mb-0.5">Objet par défaut</div>
              <div class="text-[11px] text-slate-600 dark:text-slate-300 italic truncate">
                {{ item.defaultSubject }}
              </div>
            </div>

            <!-- Variables count + edit arrow -->
            <div class="mt-2 flex items-center justify-between">
              <div class="text-[10px] text-slate-400">
                <i class="pi pi-code mr-1"/>
                {{ Object.keys(item.availableVariables || {}).length }} variable{{ Object.keys(item.availableVariables || {}).length > 1 ? 's' : '' }} disponible{{ Object.keys(item.availableVariables || {}).length > 1 ? 's' : '' }}
              </div>
              <i class="pi pi-arrow-right text-[11px] text-slate-300 dark:text-slate-600 group-hover:text-teal-500 group-hover:translate-x-0.5 transition-all"/>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="filteredDefinitions.length === 0" class="py-16 text-center text-slate-400">
        <i class="pi pi-search text-4xl block mb-3 opacity-30"/>
        <p class="text-sm font-medium">Aucun modèle ne correspond à votre recherche.</p>
      </div>
    </div>

  </div>
</template>

<style scoped></style>
