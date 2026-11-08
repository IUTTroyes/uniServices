<script setup>
/**
 * Évaluation du stagiaire par son tuteur en entreprise, côté établissement (tuteur universitaire, responsables) :
 * état de la demande, envoi (ou renvoi) du lien par e-mail, consultation de l'évaluation rendue.
 */
import { computed, onMounted, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '@helpers/axios';
import { messageErreurApi } from '@/utils/erreurApi';

const props = defineProps({
  stageId: { type: [Number, String], required: true }
});
const toast = useToast();

const etat = ref(null);
const chargement = ref(false);
const envoi = ref(false);

const dateFr = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR') : '');
const libelleStatut = computed(() => ({
  non_envoyee: 'Pas encore demandée',
  envoyee: `Lien envoyé à ${etat.value?.envoyeA} le ${dateFr(etat.value?.envoyeLe)} (valable jusqu'au ${dateFr(etat.value?.expireLe)})`,
  expiree: `Lien expiré le ${dateFr(etat.value?.expireLe)} sans réponse`,
  soumise: `Évaluation rendue le ${dateFr(etat.value?.soumisLe)} par ${etat.value?.evaluateurNom}`
}[etat.value?.statut] || ''));

const charger = async () => {
  if (!props.stageId || String(props.stageId).startsWith('sans-stage')) return;
  chargement.value = true;
  try {
    etat.value = (await api.get(`/api/stage_etudiants/${props.stageId}/evaluation-entreprise`)).data;
  } catch (error) {
    etat.value = null;
  } finally {
    chargement.value = false;
  }
};

const envoyer = async () => {
  const renvoi = etat.value?.statut === 'envoyee';
  if (renvoi && !confirm('Renvoyer un nouveau lien ? Le lien précédent ne fonctionnera plus.')) return;
  envoi.value = true;
  try {
    etat.value = (await api.post(`/api/stage_etudiants/${props.stageId}/evaluation-entreprise/envoyer`)).data;
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
  <div class="space-y-3 text-xs">
    <p v-if="chargement" class="text-slate-400">Chargement…</p>
    <template v-else-if="etat">
      <p class="text-slate-600 dark:text-slate-300">{{ libelleStatut }}</p>

      <div v-if="etat.statut !== 'soumise'" class="flex flex-wrap items-center gap-2">
        <button type="button" :disabled="envoi || !etat.destinataire || etat.criteres === 0" @click="envoyer"
          class="px-3 py-1.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold disabled:opacity-50 disabled:cursor-not-allowed">
          {{ envoi ? 'Envoi…' : (etat.statut === 'non_envoyee' ? 'Envoyer le formulaire au tuteur' : 'Renvoyer un lien') }}
        </button>
        <span v-if="!etat.destinataire" class="text-amber-700 dark:text-amber-300">Aucune adresse e-mail pour le tuteur en entreprise.</span>
        <span v-else-if="etat.criteres === 0" class="text-amber-700 dark:text-amber-300">Aucun critère défini pour la période.</span>
        <span v-else class="text-slate-400">Destinataire : {{ etat.destinataire }}</span>
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
