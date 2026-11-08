<script setup>
/**
 * Évaluation du stagiaire par son tuteur en entreprise, côté établissement (tuteur universitaire, responsables) :
 * état de la demande, envoi (ou renvoi) du lien par e-mail, consultation de l'évaluation rendue.
 */
import { computed, onMounted, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { SimpleSkeleton } from '@components';
import { getEvaluationEntrepriseService, envoyerEvaluationEntrepriseService } from '@/requests/stage_service';
import { useConfirmation } from '@/composables/useConfirmation';
import { messageErreurApi } from '@/utils/erreurApi';
import { dateFr } from '@/utils/stage';

const props = defineProps({
  stageId: { type: [Number, String], required: true }
});
const toast = useToast();
const { confirmer } = useConfirmation();

const etat = ref(null);
const chargement = ref(false);
const envoi = ref(false);

/** Libellé et sévérité de Tag par statut de la demande. */
const STATUTS = {
  non_envoyee: { libelle: 'Non demandée', severite: 'secondary' },
  envoyee: { libelle: 'En attente de réponse', severite: 'info' },
  expiree: { libelle: 'Lien expiré', severite: 'warn' },
  soumise: { libelle: 'Évaluation rendue', severite: 'success' }
};
const statut = computed(() => STATUTS[etat.value?.statut] || STATUTS.non_envoyee);
const detail = computed(() => ({
  envoyee: `Lien envoyé à ${etat.value?.envoyeA} le ${dateFr(etat.value?.envoyeLe)}, valable jusqu'au ${dateFr(etat.value?.expireLe)}.`,
  expiree: `Aucune réponse avant le ${dateFr(etat.value?.expireLe)}.`,
  soumise: `Rendue le ${dateFr(etat.value?.soumisLe)} par ${etat.value?.evaluateurNom}.`
}[etat.value?.statut] || ''));
const empechement = computed(() => {
  if (!etat.value?.destinataire) return 'Aucune adresse e-mail pour le tuteur en entreprise.';
  if (etat.value?.criteres === 0) return 'Aucun critère d\'évaluation défini pour la période.';
  return '';
});

const charger = async () => {
  if (!props.stageId || String(props.stageId).startsWith('sans-stage')) return;
  chargement.value = true;
  try {
    etat.value = await getEvaluationEntrepriseService(props.stageId);
  } catch {
    etat.value = null;
  } finally {
    chargement.value = false;
  }
};

const envoyer = async () => {
  if (etat.value?.statut === 'envoyee' && !await confirmer({ message: 'Renvoyer un nouveau lien ? Le lien précédent ne fonctionnera plus.', libelle: 'Renvoyer' })) return;
  envoi.value = true;
  try {
    etat.value = await envoyerEvaluationEntrepriseService(props.stageId);
    toast.add({ severity: 'success', summary: 'Lien envoyé', detail: `Le formulaire a été envoyé à ${etat.value.envoyeA}.`, life: 4000 });
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Envoi impossible', detail: messageErreurApi(error, 'Le lien n\'a pas pu être envoyé.'), life: 6000 });
  } finally {
    envoi.value = false;
  }
};

onMounted(charger);
watch(() => props.stageId, charger);
</script>

<template>
  <div class="space-y-3 text-sm">
    <SimpleSkeleton v-if="chargement" />
    <template v-else-if="etat">
      <div class="flex flex-wrap items-center gap-2">
        <Tag :value="statut.libelle" :severity="statut.severite" />
        <span v-if="detail" class="text-slate-600 dark:text-slate-300">{{ detail }}</span>
      </div>

      <div v-if="etat.statut !== 'soumise'" class="flex flex-wrap items-center gap-2">
        <Button :label="etat.statut === 'non_envoyee' ? 'Envoyer le formulaire au tuteur' : 'Renvoyer un lien'" icon="pi pi-send" size="small"
          :loading="envoi" :disabled="!!empechement" @click="envoyer" />
        <span v-if="empechement" class="q-callout q-callout-warning !py-1.5">{{ empechement }}</span>
        <span v-else class="q-hint !mt-0">Destinataire : {{ etat.destinataire }}</span>
      </div>

      <div v-else class="space-y-2">
        <table class="w-full">
          <tbody>
            <tr v-for="r in etat.reponses" :key="r.critere" class="border-b border-slate-100 dark:border-slate-800 align-top">
              <td class="py-1.5 pr-2 font-semibold text-slate-700 dark:text-slate-200">{{ r.libelle }}</td>
              <td class="py-1.5 pr-2 whitespace-nowrap">{{ r.niveau }}/4 — {{ etat.niveaux[r.niveau] }}</td>
              <td class="py-1.5 text-slate-500 dark:text-slate-400">{{ r.commentaire }}</td>
            </tr>
          </tbody>
        </table>
        <p v-if="etat.noteProposee !== null"><strong>Note proposée :</strong> {{ etat.noteProposee }} / 20</p>
        <p v-if="etat.appreciation" class="whitespace-pre-line"><strong>Appréciation :</strong> {{ etat.appreciation }}</p>
      </div>
    </template>
  </div>
</template>
