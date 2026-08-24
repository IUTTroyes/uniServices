<script setup>
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '@helpers/axios';

const props = defineProps({
  periodStudents: {
    type: Array,
    required: true
  },
  activePeriodName: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['reload']);

const toast = useToast();
const isValidating = ref({});

// Flatten and extract all avenants from students of this period
const allAvenants = computed(() => {
  const list = [];
  props.periodStudents.forEach(student => {
    if (student.avenants && student.avenants.length > 0) {
      student.avenants.forEach(av => {
        list.push({
          ...av,
          studentName: student.studentName,
          company: student.company
        });
      });
    }
  });
  // Sort by date creation descending
  return list.sort((a, b) => new Date(b.dateCreation) - new Date(a.dateCreation));
});

const validateAvenant = async (av) => {
  isValidating.value[av.id] = true;
  try {
    await api.post(`/api/stage_avenants/${av.id}/validate`);
    toast.add({
      severity: 'success',
      summary: 'Avenant validé',
      detail: `L'avenant pour ${av.studentName} a été validé avec succès.`,
      life: 3000
    });
    // Trigger parent reload
    emit('reload');
  } catch (error) {
    console.error('Erreur lors de la validation de l\'avenant:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de valider l\'avenant.',
      life: 3000
    });
  } finally {
    isValidating.value[av.id] = false;
  }
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-5">
      <div>
        <h2 class="text-lg font-bold text-slate-850 dark:text-white">Gestion des Avenants</h2>
        <p class="text-xs text-slate-400">
          Validez les demandes d'avenants soumises par les étudiants pour la période <strong class="text-violet-600 dark:text-violet-400">{{ activePeriodName }}</strong>.
        </p>
      </div>
    </div>

    <!-- Table of Avenants -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-150 dark:border-slate-800/80 shadow-sm overflow-hidden">
      <div v-if="allAvenants.length > 0" class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-slate-50 dark:bg-slate-800/40 text-slate-400 uppercase font-black tracking-wider border-b border-slate-150 dark:border-slate-800">
              <th class="p-4">Étudiant &amp; Entreprise</th>
              <th class="p-4">Objet</th>
              <th class="p-4">Type</th>
              <th class="p-4">Détails de la modification</th>
              <th class="p-4">Date de demande</th>
              <th class="p-4 text-center">Statut</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
            <tr v-for="av in allAvenants" :key="av.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
              <td class="p-4 font-bold text-slate-850 dark:text-slate-200">
                <div>{{ av.studentName }}</div>
                <div class="text-[10px] text-slate-400 font-medium mt-0.5">{{ av.company }}</div>
              </td>
              <td class="p-4 font-semibold text-slate-700 dark:text-slate-300">
                {{ av.libelle }}
              </td>
              <td class="p-4">
                <span class="px-2 py-0.5 rounded font-mono font-bold text-[9px] bg-slate-100 dark:bg-slate-800 text-slate-500">
                  {{ av.typeModification }}
                </span>
              </td>
              <td class="p-4 text-slate-500 dark:text-slate-400 max-w-[250px] truncate" :title="av.texte">
                {{ av.texte }}
              </td>
              <td class="p-4 text-slate-400 font-semibold">
                {{ new Date(av.dateCreation).toLocaleDateString('fr-FR') }}
              </td>
              <td class="p-4 text-center">
                <span :class="[
                  'px-2 py-1 rounded-xl text-[9px] font-black uppercase tracking-wider',
                  av.valide 
                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' 
                    : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300'
                ]">
                  {{ av.valide ? 'Validé' : 'En attente' }}
                </span>
              </td>
              <td class="p-4 text-right">
                <div class="flex justify-end gap-2">
                  <button 
                    v-if="!av.valide" 
                    @click="validateAvenant(av)" 
                    :disabled="isValidating[av.id]"
                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm border-0 cursor-pointer flex items-center gap-1"
                  >
                    <i v-if="isValidating[av.id]" class="pi pi-spin pi-spinner"></i>
                    <i v-else class="pi pi-check"></i>
                    <span>Valider</span>
                  </button>
                  <a 
                    v-if="av.valide"
                    :href="'/api/stage_avenants/' + av.id + '/pdf'"
                    target="_blank"
                    class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-lg shadow-sm flex items-center gap-1 transition-all decoration-none"
                  >
                    <i class="pi pi-download"></i>
                    <span>Télécharger PDF</span>
                  </a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="p-12 text-center text-slate-400">
        <i class="pi pi-file-excel text-4xl text-slate-350 block mb-3"></i>
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-300">Aucun avenant</h3>
        <p class="text-xs text-slate-400 mt-1">Aucune demande d'avenant n'a été formulée par les étudiants pour cette période.</p>
      </div>
    </div>
  </div>
</template>
