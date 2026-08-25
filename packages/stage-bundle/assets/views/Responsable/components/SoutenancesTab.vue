<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '@helpers/axios';

const props = defineProps({
  periodStudents: {
    type: Array,
    required: true
  },
  teachers: {
    type: Array,
    required: true
  },
  selectedPeriodId: {
    type: [Number, String],
    required: true
  },
  activePeriodName: {
    type: String,
    default: ''
  }
});

const toast = useToast();
const slots = ref([]);
const isLoading = ref(false);

// Configuration state
const defaultDuration = ref(30);
const isUpdatingDuration = ref(false);

// Bulk Generator state
const bulkDate = ref('');
const bulkStartTime = ref('09:00');
const bulkEndTime = ref('17:00');
const bulkDuree = ref(30);
const bulkPause = ref(15);
const bulkSalle = ref('');
const isGeneratingBulk = ref(false);

// Conflict tracking per slot ID
const slotConflicts = ref({});

// Defense sessions list
const sessions = ref([]);

// Watch period changes
watch(() => props.selectedPeriodId, async (newVal) => {
  if (newVal) {
    // Set default bulk date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    bulkDate.value = tomorrow.toISOString().split('T')[0];

    await fetchPeriodDetails();
    await fetchSlots();
  }
}, { immediate: true });

async function fetchPeriodDetails() {
  try {
    const response = await api.get(`/api/stage_periodes/${props.selectedPeriodId}`);
    if (response.data) {
      defaultDuration.value = response.data.dureeSoutenance ?? 30;
      bulkDuree.value = defaultDuration.value;
      sessions.value = response.data.periodesSoutenance || [];
    }
  } catch (error) {
    console.error('Erreur lors de la récupération de la période:', error);
  }
}

async function updatePeriodDuration() {
  isUpdatingDuration.value = true;
  try {
    await api.patch(`/api/stage_periodes/${props.selectedPeriodId}`, {
      dureeSoutenance: defaultDuration.value
    }, {
      headers: { 'Content-Type': 'application/merge-patch+json' }
    });
    toast.add({
      severity: 'success',
      summary: 'Configuration enregistrée',
      detail: `La durée par défaut a été configurée à ${defaultDuration.value} minutes.`,
      life: 3000
    });
  } catch (error) {
    console.error('Erreur lors de la mise à jour de la durée:', error);
  } finally {
    isUpdatingDuration.value = false;
  }
}

async function fetchSlots() {
  isLoading.value = true;
  try {
    const response = await api.get('/api/stage_soutenances');
    
    let rawList = [];
    if (response.data) {
      if (Array.isArray(response.data)) {
        rawList = response.data;
      } else if (response.data['hydra:member'] && Array.isArray(response.data['hydra:member'])) {
        rawList = response.data['hydra:member'];
      }
    }
    
    // Filter and map slots for the selected period
    const list = rawList.filter(s => {
      const pId = s.stagePeriode?.id || (typeof s.stagePeriode === 'string' && s.stagePeriode.split('/').pop());
      return String(pId) === String(props.selectedPeriodId);
    });

    // Sort chronologically
    list.sort((a, b) => new Date(a.dateSoutenance) - new Date(b.dateSoutenance));

    // Map to local edit state
    slots.value = list.map(s => {
      const stageEtudiantId = s.stageEtudiant ? (s.stageEtudiant.id || parseInt(s.stageEtudiant['@id']?.split('/').pop(), 10)) : null;
      const enseignantJuryId = s.enseignantJury ? (s.enseignantJury.id || parseInt(s.enseignantJury['@id']?.split('/').pop(), 10)) : null;

      return {
        id: s.id,
        dateSoutenance: s.dateSoutenance,
        duree: s.duree || defaultDuration.value,
        salle: s.salle || '',
        stageEtudiantId: stageEtudiantId,
        enseignantJuryId: enseignantJuryId,
        isEdited: false,
        isSaving: false
      };
    });

    // Run conflict checks on load
    for (const s of slots.value) {
      if (s.stageEtudiantId || s.enseignantJuryId) {
        checkConflictForSlot(s);
      }
    }
  } catch (error) {
    console.error('Erreur lors de la récupération des créneaux:', error);
  } finally {
    isLoading.value = false;
  }
}

// Generate slots in bulk
async function generateBulkSlots() {
  if (!bulkDate.value || !bulkStartTime.value || !bulkEndTime.value) {
    toast.add({
      severity: 'warn',
      summary: 'Champs requis',
      detail: 'Veuillez renseigner la date, l\'heure de début et l\'heure de fin.',
      life: 3000
    });
    return;
  }

  isGeneratingBulk.value = true;
  try {
    const response = await api.post('/api/stage_soutenances/bulk-generate', {
      stagePeriodeId: props.selectedPeriodId,
      date: bulkDate.value,
      startTime: bulkStartTime.value,
      endTime: bulkEndTime.value,
      duree: bulkDuree.value,
      pauseDuree: bulkPause.value,
      salle: bulkSalle.value
    });

    toast.add({
      severity: 'success',
      summary: 'Grille générée',
      detail: `${response.data.slotsCreated} créneaux ont été ajoutés au planning.`,
      life: 3000
    });

    await fetchSlots();
  } catch (error) {
    console.error('Erreur de génération en lot:', error);
    const errorMsg = error.response?.data?.error || 'Impossible de générer la grille de créneaux.';
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: errorMsg,
      life: 4000
    });
  } finally {
    isGeneratingBulk.value = false;
  }
}

// Students who have valid stage requests
const eligibleStudents = computed(() => {
  if (!props.periodStudents) return [];
  return props.periodStudents.filter(s => s.hasStage);
});

// Students who don't have a defense planned yet
const unscheduledStudents = computed(() => {
  const studentsList = eligibleStudents.value || [];
  const slotsList = slots.value || [];
  return studentsList.filter(s => {
    return !slotsList.some(sout => sout.stageEtudiantId === s.id);
  });
});

// Helper to get student details by mapped id
const getStudentDetails = (id) => {
  if (!id) return null;
  return eligibleStudents.value.find(s => s.id === id) || null;
};

// Available students for selection (excludes already assigned to other slots, but keeps the currently assigned one for the active slot)
const getAvailableStudentsForSlot = (activeSlot) => {
  return eligibleStudents.value.filter(s => {
    // If the student is assigned to this slot, they are available
    if (activeSlot.stageEtudiantId === s.id) return true;
    // If they are assigned to any other slot, they are unavailable
    return !slots.value.some(sout => sout.id !== activeSlot.id && sout.stageEtudiantId === s.id);
  });
};

// Check conflict for a specific slot on change
async function checkConflictForSlot(slot) {
  if (!slot.dateSoutenance) return;
  try {
    const response = await api.post('/api/stage_soutenances/validate-conflict', {
      stageEtudiantId: slot.stageEtudiantId,
      enseignantJuryId: slot.enseignantJuryId,
      dateSoutenance: slot.dateSoutenance,
      duree: slot.duree,
      soutenanceId: slot.id,
      stagePeriodeId: props.selectedPeriodId
    });

    slotConflicts.value[slot.id] = response.data.messages || [];
  } catch (error) {
    console.error('Erreur lors de la vérification de conflit:', error);
  }
}

// Handle field change in slot
const onSlotFieldChange = (slot) => {
  slot.isEdited = true;
  checkConflictForSlot(slot);
};

// Save a slot
async function saveSlot(slot) {
  slot.isSaving = true;
  try {
    await api.post('/api/stage_soutenances/save', {
      id: slot.id,
      stagePeriodeId: props.selectedPeriodId,
      stageEtudiantId: slot.stageEtudiantId,
      enseignantJuryId: slot.enseignantJuryId,
      dateSoutenance: slot.dateSoutenance,
      salle: slot.salle,
      duree: slot.duree
    });

    toast.add({
      severity: 'success',
      summary: 'Créneau mis à jour',
      detail: 'Les affectations ont été enregistrées avec succès.',
      life: 2000
    });

    slot.isEdited = false;
    await fetchSlots();
  } catch (error) {
    console.error('Erreur lors de la sauvegarde du créneau:', error);
    const errorMsg = error.response?.data?.error || 'Erreur lors de l\'enregistrement.';
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: errorMsg,
      life: 4000
    });
  } finally {
    slot.isSaving = false;
  }
}

// Clear slot assignment (unassign student & assesseur)
async function clearSlot(slot) {
  slot.stageEtudiantId = null;
  slot.enseignantJuryId = null;
  slot.isEdited = true;
  slotConflicts.value[slot.id] = [];
  await saveSlot(slot);
}

// Delete slot completely
async function deleteSlot(id) {
  if (!confirm('Voulez-vous supprimer définitivement ce créneau de la grille ?')) {
    return;
  }
  try {
    await api.delete(`/api/stage_soutenances/${id}`);
    toast.add({
      severity: 'info',
      summary: 'Créneau supprimé',
      detail: 'Le créneau a été retiré de la grille.',
      life: 3000
    });
    await fetchSlots();
  } catch (error) {
    console.error('Erreur lors de la suppression du créneau:', error);
  }
}

const getSoutenanceEnd = (startStr, durationMin) => {
  const start = new Date(startStr);
  const end = new Date(start.getTime() + durationMin * 60000);
  return end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
  <div class="space-y-6 text-xs">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
      <div>
        <h2 class="text-lg font-bold text-slate-850 dark:text-white">Planning par Grille de Soutenances</h2>
        <p class="text-xs text-slate-400">
          Générez une grille de créneaux et affectez-y directement les étudiants et les jurys pour <strong class="text-violet-600 dark:text-violet-400">{{ activePeriodName }}</strong>.
        </p>
      </div>

      <!-- Duration Config -->
      <div class="bg-slate-50 dark:bg-slate-900/40 p-3 rounded-2xl border border-slate-150 dark:border-slate-800/80 flex items-center gap-3">
        <div class="flex flex-col">
          <label class="text-[9px] font-black uppercase text-slate-400">Durée par soutenance</label>
          <div class="flex items-center gap-1.5 mt-0.5">
            <input 
              v-model.number="defaultDuration"
              type="number"
              min="10"
              max="180"
              class="w-16 p-1 text-xs font-bold border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-950 text-slate-800 dark:text-slate-200"
            />
            <span class="text-slate-500 font-semibold">min</span>
          </div>
        </div>
        <button 
          @click="updatePeriodDuration"
          :disabled="isUpdatingDuration"
          class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl shadow-sm border-0 cursor-pointer flex items-center gap-1"
        >
          <i v-if="isUpdatingDuration" class="pi pi-spin pi-spinner text-[10px]"></i>
          <span>Enregistrer</span>
        </button>
      </div>
    </div>

    <!-- Scheduled Sessions Info -->
    <div v-if="sessions.length > 0" class="p-4 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-3xl space-y-2">
      <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
        <i class="pi pi-info-circle text-violet-600 dark:text-violet-400"></i>
        <span>Sessions de soutenances prévues pour cette période :</span>
      </div>
      <div class="flex flex-wrap gap-2">
        <span v-for="s in sessions" :key="s.id" class="px-3 py-1.5 bg-violet-100 dark:bg-violet-950/40 text-violet-750 dark:text-violet-400 font-bold rounded-xl text-[10px]">
          Du {{ new Date(s.dateDebut).toLocaleDateString('fr-FR') }} au {{ new Date(s.dateFin).toLocaleDateString('fr-FR') }}
          <span class="text-slate-400 dark:text-slate-500 font-medium"> (Rapport avant le {{ new Date(s.dateRenduRapport).toLocaleDateString('fr-FR') }})</span>
        </span>
      </div>
    </div>

    <!-- Bulk Slot Generator Panel -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-150 dark:border-slate-800/80 shadow-sm space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
        <i class="pi pi-calendar-plus text-violet-600 dark:text-violet-400 text-base"></i>
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Générateur de créneaux vides en lot</h3>
      </div>
      
      <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <!-- Date -->
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-400">Date des examens</label>
          <input 
            v-model="bulkDate"
            type="date"
            class="p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200"
          />
        </div>

        <!-- Start Time -->
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-400">Heure de début</label>
          <input 
            v-model="bulkStartTime"
            type="time"
            class="p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200"
          />
        </div>

        <!-- End Time -->
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-400">Heure de fin</label>
          <input 
            v-model="bulkEndTime"
            type="time"
            class="p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200"
          />
        </div>

        <!-- Duration -->
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-400">Durée (minutes)</label>
          <input 
            v-model.number="bulkDuree"
            type="number"
            min="10"
            class="p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200"
          />
        </div>

        <!-- Pause -->
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-400">Pause entre créneaux</label>
          <input 
            v-model.number="bulkPause"
            type="number"
            min="0"
            class="p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200"
          />
        </div>

        <!-- Salle -->
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-400">Salle par défaut</label>
          <input 
            v-model="bulkSalle"
            type="text"
            placeholder="Ex: Salle B05"
            class="p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200"
          />
        </div>
      </div>

      <div class="flex justify-end pt-2 border-t border-slate-50 dark:border-slate-800">
        <button 
          @click="generateBulkSlots"
          :disabled="isGeneratingBulk"
          class="px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl border-0 shadow-sm cursor-pointer flex items-center gap-1.5"
        >
          <i v-if="isGeneratingBulk" class="pi pi-spin pi-spinner text-xs"></i>
          <span>Générer la grille</span>
        </button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800/80 p-4 rounded-3xl shadow-sm flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-2xl bg-violet-50 dark:bg-violet-950/20 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
          <i class="pi pi-calendar text-lg"></i>
        </div>
        <div>
          <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Créneaux dans la grille</span>
          <span class="text-lg font-black text-slate-850 dark:text-white mt-0.5 block">{{ slots.length }}</span>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800/80 p-4 rounded-3xl shadow-sm flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
          <i class="pi pi-users text-lg"></i>
        </div>
        <div>
          <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Créneaux libres</span>
          <span class="text-lg font-black text-slate-850 dark:text-white mt-0.5 block">{{ slots.filter(s => !s.stageEtudiantId).length }}</span>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800/80 p-4 rounded-3xl shadow-sm flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
          <i class="pi pi-user-minus text-lg"></i>
        </div>
        <div>
          <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Étudiants restants à planifier</span>
          <span class="text-lg font-black text-slate-850 dark:text-white mt-0.5 block">{{ unscheduledStudents.length }}</span>
        </div>
      </div>
    </div>

    <!-- Interactive Grid Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-150 dark:border-slate-800/80 shadow-sm overflow-hidden">
      <div v-if="slots.length > 0" class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-400 uppercase font-black tracking-wider text-[10px] border-b border-slate-150 dark:border-slate-800">
              <th class="p-4 w-48">Date &amp; Horaire</th>
              <th class="p-4 w-28">Salle</th>
              <th class="p-4 w-64">Étudiant (Stagiaire)</th>
              <th class="p-4 w-44">Tuteur Universitaire</th>
              <th class="p-4 w-52">Enseignant Assesseur (Candide)</th>
              <th class="p-4 text-center w-28">Statut</th>
              <th class="p-4 text-right w-36">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
            <tr 
              v-for="s in slots" 
              :key="s.id" 
              class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors"
              :class="{'bg-violet-50/10 dark:bg-violet-950/5': s.isEdited}"
            >
              <!-- Time slot -->
              <td class="p-4">
                <div class="font-bold text-slate-800 dark:text-slate-200">
                  {{ new Date(s.dateSoutenance).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }) }}
                </div>
                <div class="text-violet-600 dark:text-violet-400 font-black mt-0.5">
                  {{ new Date(s.dateSoutenance).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
                  -
                  {{ getSoutenanceEnd(s.dateSoutenance, s.duree) }}
                </div>
              </td>

              <!-- Salle -->
              <td class="p-4">
                <input 
                  v-model="s.salle"
                  type="text"
                  @change="onSlotFieldChange(s)"
                  placeholder="Ex: B05"
                  class="w-full p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-950 text-xs text-slate-800 dark:text-slate-200 focus:outline-violet-500 font-bold"
                />
              </td>

              <!-- Student Dropdown -->
              <td class="p-4">
                <select 
                  v-model="s.stageEtudiantId"
                  @change="onSlotFieldChange(s)"
                  class="w-full p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-950 text-xs text-slate-800 dark:text-slate-200 focus:outline-violet-500 font-semibold"
                >
                  <option :value="null">-- Créneau libre (Libre) --</option>
                  <option 
                    v-for="std in getAvailableStudentsForSlot(s)" 
                    :key="std.id" 
                    :value="std.id"
                  >
                    {{ std.studentName }}
                  </option>
                </select>
                <div v-if="s.stageEtudiantId && getStudentDetails(s.stageEtudiantId)" class="text-[10px] text-slate-400 font-medium mt-1 pl-1">
                  {{ getStudentDetails(s.stageEtudiantId).company }}
                </div>
              </td>

              <!-- Tutor (Auto display) -->
              <td class="p-4">
                <span 
                  v-if="s.stageEtudiantId && getStudentDetails(s.stageEtudiantId)"
                  class="px-2 py-1 rounded-lg bg-violet-50 dark:bg-violet-950/20 text-violet-750 dark:text-violet-400 font-bold border border-violet-100 dark:border-violet-900/40 text-[10px] block text-center"
                >
                  {{ getStudentDetails(s.stageEtudiantId).tutor }}
                </span>
                <span v-else class="text-slate-400 italic text-[10px]">Auto-rempli</span>
              </td>

              <!-- Assesseur / Candide Dropdown -->
              <td class="p-4">
                <select 
                  v-model="s.enseignantJuryId"
                  @change="onSlotFieldChange(s)"
                  class="w-full p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-950 text-xs text-slate-800 dark:text-slate-200 focus:outline-violet-500"
                >
                  <option :value="null">Aucun assesseur (Candide)</option>
                  <option 
                    v-for="t in teachers" 
                    :key="t.id" 
                    :value="t.id"
                  >
                    {{ t.prenom }} {{ t.nom }}
                  </option>
                </select>
              </td>

              <!-- Conflict Status Badge -->
              <td class="p-4 text-center">
                <div v-if="slotConflicts[s.id] && slotConflicts[s.id].length > 0" class="inline-flex items-center justify-center gap-1 text-rose-600 font-bold bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/50 px-2.5 py-1 rounded-xl" :title="slotConflicts[s.id].join('\n')">
                  <i class="pi pi-exclamation-triangle"></i>
                  <span>Conflit</span>
                </div>
                <div v-else-if="s.stageEtudiantId" class="inline-flex items-center justify-center gap-1 text-emerald-600 font-bold bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-250 dark:border-emerald-900/50 px-2.5 py-1 rounded-xl">
                  <i class="pi pi-check"></i>
                  <span>Prêt</span>
                </div>
                <span v-else class="text-slate-400 italic">Libre</span>
              </td>

              <!-- Actions -->
              <td class="p-4 text-right">
                <div class="flex justify-end gap-1.5">
                  <!-- Save -->
                  <button 
                    @click="saveSlot(s)"
                    :disabled="s.isSaving || (slotConflicts[s.id] && slotConflicts[s.id].length > 0)"
                    class="p-2 rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 border-0 cursor-pointer transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                    title="Enregistrer les affectations"
                  >
                    <i v-if="s.isSaving" class="pi pi-spin pi-spinner"></i>
                    <i v-else class="pi pi-save"></i>
                  </button>
                  
                  <!-- Free Slot -->
                  <button 
                    @click="clearSlot(s)"
                    v-if="s.stageEtudiantId"
                    class="p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-500 hover:text-amber-600 hover:border-amber-300 transition-all cursor-pointer"
                    title="Libérer le créneau (Désassigner)"
                  >
                    <i class="pi pi-user-minus"></i>
                  </button>

                  <!-- Delete Slot completely -->
                  <button 
                    @click="deleteSlot(s.id)"
                    class="p-2 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-500 hover:text-rose-600 hover:border-rose-300 transition-all cursor-pointer"
                    title="Supprimer le créneau de la grille"
                  >
                    <i class="pi pi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="p-12 text-center text-slate-400">
        <i class="pi pi-calendar-times text-4xl text-slate-300 block mb-3"></i>
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-350">Grille vide</h3>
        <p class="text-xs text-slate-400 mt-1">Utilisez le générateur ci-dessus pour ajouter des créneaux horaires.</p>
      </div>
    </div>
  </div>
</template>
