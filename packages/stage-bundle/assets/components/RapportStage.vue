<script setup>
/**
 * Dépôt du rapport de stage par l'étudiant (PDF, 15 Mo au plus) : dépôt par clic ou glisser-déposer avec progression
 * réelle, lien de téléchargement, retrait tant que le stage n'est pas noté. L'état renvoyé par le serveur est remonté
 * par « change ».
 */
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import { Card } from '@components';
import { deposerRapportStageService, retirerRapportStageService, rapportStageUrl } from '@/requests/stage_service';
import { useConfirmation } from '@/composables/useConfirmation';
import { messageErreurApi } from '@/utils/erreurApi';

const props = defineProps({
  stage: { type: Object, required: true } // { id, reportUploaded, reportName, reportDate, grade }
});
const emit = defineEmits(['change']);
const toast = useToast();
const { confirmer } = useConfirmation();

const champFichier = ref(null);
const envoiEnCours = ref(false);
const progression = ref(0);
const survol = ref(false);

const choisirFichier = () => champFichier.value?.click();

const deposer = async (fichier) => {
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

const fichierChoisi = (e) => {
  const fichier = e.target.files[0];
  e.target.value = ''; // permet de redéposer le même fichier
  deposer(fichier);
};

const fichierDepose = (e) => {
  survol.value = false;
  deposer(e.dataTransfer?.files?.[0]);
};

const retirer = async () => {
  if (!props.stage?.id || !await confirmer({ message: 'Retirer le rapport déposé ?', titre: 'Retirer le rapport', libelle: 'Retirer', danger: true })) return;
  try {
    emit('change', await retirerRapportStageService(props.stage.id));
    toast.add({ severity: 'info', summary: 'Rapport retiré', detail: 'Votre rapport de stage a été retiré.', life: 3000 });
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: messageErreurApi(error, 'Impossible de retirer le rapport.'), life: 4000 });
  }
};
</script>

<template>
  <Card title="Rapport de stage" subtitle="Transmettez votre rapport final au format PDF (15 Mo au plus)" icon="pi pi-cloud-upload" color="emerald">
    <!-- Rapport déjà déposé -->
    <div v-if="stage.reportUploaded" class="q-section flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 !space-y-0">
      <div class="flex items-center gap-3 min-w-0">
        <i class="pi pi-file-pdf text-2xl text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
        <div class="min-w-0">
          <a :href="rapportStageUrl(stage.id)" class="text-sm font-semibold text-slate-900 dark:text-white block truncate hover:underline" title="Télécharger le rapport">
            {{ stage.reportName }}
          </a>
          <span class="q-hint !mt-0">Transmis le {{ stage.reportDate || 'récemment' }}</span>
        </div>
      </div>
      <div class="flex items-center gap-2 self-end sm:self-auto">
        <Tag v-if="stage.grade" severity="success" :value="`Note attribuée : ${stage.grade}`" />
        <Button v-else icon="pi pi-trash" label="Retirer" severity="danger" text size="small" aria-label="Retirer le rapport" @click="retirer" />
      </div>
    </div>

    <!-- Transfert en cours -->
    <div v-else-if="envoiEnCours" class="q-section text-center">
      <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Transfert du rapport en cours…</p>
      <ProgressBar :value="progression" class="max-w-xs mx-auto" />
    </div>

    <!-- Zone de dépôt PDF (clic ou glisser-déposer) -->
    <button v-else type="button" @click="choisirFichier"
      @dragover.prevent="survol = true" @dragleave.prevent="survol = false" @drop.prevent="fichierDepose"
      class="w-full border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-colors"
      :class="survol ? 'border-primary-500 bg-primary-50/40 dark:bg-primary-950/20' : 'border-slate-300 dark:border-slate-700 hover:border-primary-500'">
      <i class="pi pi-cloud-upload text-3xl text-primary-600 dark:text-primary-400" aria-hidden="true"></i>
      <span class="block text-sm font-semibold text-slate-800 dark:text-white mt-3">Déposer mon rapport de stage (PDF)</span>
      <span class="q-hint block">Glissez votre fichier ici ou cliquez pour le choisir (15 Mo au plus).</span>
    </button>

    <input ref="champFichier" type="file" accept=".pdf,application/pdf" class="hidden" aria-label="Choisir le rapport (PDF)" @change="fichierChoisi" />
  </Card>
</template>
