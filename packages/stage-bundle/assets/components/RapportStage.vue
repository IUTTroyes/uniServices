<script setup>
/**
 * Dépôt du rapport de stage par l'étudiant (PDF, 15 Mo au plus) : dépôt avec progression réelle, lien de
 * téléchargement, retrait tant que le stage n'est pas noté. L'état renvoyé par le serveur est remonté par « change ».
 */
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { CloudArrowUpIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { Card } from '@components';
import { deposerRapportStageService, retirerRapportStageService, rapportStageUrl } from '@/requests/stage_service';
import { messageErreurApi } from '@/utils/erreurApi';

const props = defineProps({
  stage: { type: Object, required: true } // { id, reportUploaded, reportName, reportDate, grade }
});
const emit = defineEmits(['change']);
const toast = useToast();

const champFichier = ref(null);
const envoiEnCours = ref(false);
const progression = ref(0);

const choisirFichier = () => champFichier.value?.click();

const deposer = async (e) => {
  const fichier = e.target.files[0];
  e.target.value = ''; // permet de redéposer le même fichier
  if (!fichier || !props.stage?.id) return;
  if (fichier.type && fichier.type !== 'application/pdf') {
    toast.add({ severity: 'warn', summary: 'Format refusé', detail: 'Le rapport doit être un fichier PDF.', life: 4000 });
    return;
  }
  envoiEnCours.value = true;
  progression.value = 0;
  try {
    const etat = await deposerRapportStageService(props.stage.id, fichier, (p) => { progression.value = p; });
    emit('change', etat);
    toast.add({ severity: 'success', summary: 'Rapport déposé', detail: `Le document "${etat.reportName || fichier.name}" a été transmis.`, life: 4000 });
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Dépôt impossible', detail: messageErreurApi(error, 'Le rapport n\'a pas pu être transmis. Réessayez.'), life: 5000 });
  } finally {
    envoiEnCours.value = false;
  }
};

const retirer = async () => {
  if (!props.stage?.id || !confirm('Retirer le rapport déposé ?')) return;
  try {
    emit('change', await retirerRapportStageService(props.stage.id));
    toast.add({ severity: 'info', summary: 'Rapport retiré', detail: 'Votre rapport de stage a été retiré.', life: 3000 });
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, 'Impossible de retirer le rapport.'), life: 4000 });
  }
};
</script>

<template>
  <div>
    <Card
      title="Dépôt &amp; Évaluation du Rapport de Stage"
      subtitle="Transmettez votre mémoire final au format PDF"
      icon="pi pi-cloud-upload"
      color="emerald"
    >
      <div class="space-y-4">
        <!-- Rapport déjà déposé -->
        <div 
          v-if="stage.reportUploaded" 
          class="bg-emerald-50/80 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/50 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm"
        >
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
              <i class="pi pi-file-pdf text-2xl"></i>
            </div>
            <div>
              <a :href="rapportStageUrl(stage.id)"
                class="text-xs font-black text-slate-900 dark:text-white block truncate max-w-[250px] sm:max-w-[350px] hover:underline"
                title="Télécharger le rapport">
                {{ stage.reportName }}
              </a>
              <span class="text-[10px] text-emerald-700 dark:text-emerald-400 font-bold block mt-0.5">
                Transmis le {{ stage.reportDate || 'Récemment' }}
              </span>
            </div>
          </div>

          <div class="flex items-center gap-2 self-end sm:self-auto">
            <span v-if="stage.grade" class="text-xs font-black bg-emerald-600 text-white px-3 py-1.5 rounded-xl shadow-sm">
              Note attribuée : {{ stage.grade }}
            </span>
            <button 
              v-if="!stage.grade"
              @click="retirer"
              class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-100/50 dark:hover:bg-rose-950/40 p-2 rounded-xl transition-all"
              title="Retirer le rapport"
            >
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Transfert en cours -->
        <div 
          v-else-if="envoiEnCours"
          class="border border-slate-200 dark:border-slate-700 rounded-2xl p-6 text-center bg-slate-50 dark:bg-slate-800/50"
        >
          <div class="w-10 h-10 flex items-center justify-center mx-auto text-violet-600">
            <i class="pi pi-spin pi-spinner text-2xl"></i>
          </div>
          <h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-2">Transfert du rapport en cours...</h4>
          <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full mt-3 max-w-xs mx-auto overflow-hidden">
            <div class="bg-violet-600 h-full transition-all duration-300" :style="{ width: progression + '%' }"></div>
          </div>
        </div>

        <!-- Zone de dépôt PDF -->
        <div 
          v-else
          @click="choisirFichier"
          class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-violet-500 rounded-2xl p-8 text-center cursor-pointer hover:bg-violet-50/20 dark:hover:bg-violet-950/10 transition-all duration-300 group"
        >
          <div class="w-12 h-12 bg-violet-100 dark:bg-violet-950/40 text-violet-600 dark:text-violet-400 rounded-2xl flex items-center justify-center mx-auto group-hover:scale-110 transition-transform">
            <CloudArrowUpIcon class="w-7 h-7" />
          </div>
          <h4 class="text-xs font-extrabold text-slate-800 dark:white mt-3">Déposer mon rapport de stage (PDF)</h4>
          <p class="text-[11px] text-slate-400 mt-1">Glissez votre fichier ici ou cliquez pour choisir sur votre ordinateur (max 15 Mo)</p>
        </div>
      </div>
    </Card>
    <input ref="champFichier" type="file" accept=".pdf,application/pdf" class="hidden" aria-label="Choisir le rapport (PDF)" @change="deposer" />
  </div>
</template>
