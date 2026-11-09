<script setup>
import { ErrorState, EmptyState } from '@components';
import {useRouter} from 'vue-router';
import {useEnseignementPage} from './useEnseignementPage';
import { computed, ref, watch } from 'vue';
import { useUsersStore } from '@stores';
import EnseignementAvancementRepere from './EnseignementAvancementRepere.vue';
import {
  getAvancementsEnseignementService,
  saveAvancementEnseignementService,
} from '@requests';

const props = defineProps({
  visible: Boolean,
  enseignement: { type: Object, default: null },
  anneeUniversitaire: { type: [Number, String], default: null },
});
const vFocus = { mounted: element => element.focus() };
const router = useRouter();
const emit = defineEmits(['update:visible']);
const userStore = useUsersStore();
const groupes = ref([]);
const sequences = ref([]);
const etatsSequence = [
  { label: 'En cours', value: 'en_cours' },
  { label: 'Terminée', value: 'terminee' },
];
const libelleSequence = sequence => `Séquence${sequence.ordre != null ? ` ${sequence.ordre}` : ''} — ${sequence.programme}`;
const optionsSequences = computed(() => sequences.value.map(sequence => ({ ...sequence, libelle: libelleSequence(sequence) })));
const anneeChargee = ref(null);
const anneeLibelle = ref('');
const loading = ref(false);
const error = ref('');
const drafts = ref({});
const saving = ref(null);
const saved = ref(null);
const filtreType = ref('tous');
const historiqueGroupe = ref(null);
const historique = ref([]);
const historiqueAvant = ref(null);
const historiqueLoading = ref(false);
const historiqueErreur = ref('');

const types = computed(() => [...new Set(groupes.value.map(item => item.type))]);
const filtres = computed(() => [{ label: 'Tous les groupes', value: 'tous' }, ...types.value.map(type => ({ label: type, value: type }))]);
const groupesFiltres = computed(() => groupes.value.filter(item => filtreType.value === 'tous' || item.type === filtreType.value));
const contexte = () => JSON.stringify([props.enseignement?.id, props.anneeUniversitaire, userStore.departementDefaut?.id, userStore.userId]);
const dateLibelle = value => value ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '';
const messageErreur = err => err.response?.status === 403
  ? 'Vous ne disposez pas des droits nécessaires pour cet enseignement dans cette année universitaire.'
  : 'Impossible de charger le suivi. Vous pouvez réessayer.';

async function charger() {
  if (!props.visible || !props.enseignement?.id) return;

  loading.value = true;
  error.value = '';
  try {
    const data = await getAvancementsEnseignementService({ enseignement: props.enseignement.id, anneeUniversitaire: props.anneeUniversitaire || undefined });

    groupes.value = data.groupes;
    sequences.value = data.sequences ?? [];
    anneeChargee.value = data.anneeUniversitaire;
    anneeLibelle.value = data.anneeLibelle ?? '';
  } catch (err) {
    error.value = messageErreur(err);
  } finally {
    loading.value = false;
  }
}
watch(() => JSON.stringify([props.visible, contexte()]), () => {


  groupes.value = [];
  sequences.value = [];
  drafts.value = {};
  anneeChargee.value = null;
  anneeLibelle.value = '';
  saving.value = null;
  saved.value = null;
  error.value = '';
  filtreType.value = 'tous';
  historiqueGroupe.value = null;
  if (props.visible && props.enseignement?.id) charger();
}, { immediate: true });

function modifier(groupe) {
  if (saving.value !== null) return;
  saved.value = null;
  const ancienneSequence = groupe.avancement?.sequence;
  const sequenceDisponible = ancienneSequence && sequences.value.some(item => item.id === ancienneSequence.id);
  const sequenceRetiree = ancienneSequence && !sequenceDisponible;
  drafts.value[groupe.id] = {
    sequenceId: sequenceDisponible ? ancienneSequence.id : null,
    etatSequence: sequenceDisponible ? groupe.avancement.etatSequence : null,
    sequenceRetiree,
    repere: sequenceRetiree
      ? `${libelleSequence(ancienneSequence)}${groupe.avancement.repere ? ` — ${groupe.avancement.repere}` : ''}`
      : groupe.avancement?.repere ?? '',
    prochaineEtape: groupe.avancement?.prochaineEtape ?? '',
    versionId: groupe.avancement?.id ?? 0,
    error: '', conflict: false,
  };
}
function changerSequence(groupe) {
  const draft = drafts.value[groupe.id];
  draft.etatSequence = draft.sequenceId ? 'en_cours' : null;
}
async function enregistrer(groupe) {
  const draft = drafts.value[groupe.id];
  if (!draft || draft.conflict || saving.value !== null) return;
  if (draft.repere.length > 2000 || draft.prochaineEtape.length > 2000) { draft.error = 'Limitez chaque texte à 2 000 caractères. L’ancien repère reste disponible dans l’historique.'; return; }
  if (!draft.sequenceId && !draft.repere.trim()) { draft.error = 'Choisissez une séquence ou indiquez un repère libre.'; return; }

  saving.value = groupe.id;
  draft.error = '';
  try {
    const result = await saveAvancementEnseignementService({
      repere: draft.repere, prochaineEtape: draft.prochaineEtape, versionId: draft.versionId,
      sequenceId: draft.sequenceId, etatSequence: draft.etatSequence,
    }, { enseignement: props.enseignement.id, groupe: groupe.id, anneeUniversitaire: anneeChargee.value });

    groupe.avancement = result;
    delete drafts.value[groupe.id];
    saved.value = groupe.id;
  } catch (err) {

    if (err.response?.status === 409) {
      draft.conflict = true;
      draft.error = 'Un collègue a modifié ce repère. Votre saisie est conservée. Rechargez sa version pour les comparer.';
    } else {
      draft.error = err.response?.status === 403 ? messageErreur(err)
        : [400, 422].includes(err.response?.status) ? (err.response.data?.detail || err.response.data?.message || 'Vérifiez la séquence et le repère saisis.')
          : 'Le repère n’a pas pu être enregistré. Votre saisie est conservée.';
    }
  } finally {
    saving.value = null;
  }
}
async function comparer(groupe) {

  const draft = drafts.value[groupe.id];
  if (!draft || saving.value !== null) return;
  saving.value = groupe.id;
  try {
    const result = await getAvancementsEnseignementService({ enseignement: props.enseignement.id, anneeUniversitaire: anneeChargee.value });

    const actualise = result.groupes.find(item => item.id === groupe.id);
    if (!actualise) { draft.error = 'Ce groupe n’est plus disponible dans votre contexte.'; return; }
    groupe.avancement = actualise.avancement;
    sequences.value = result.sequences ?? [];
    draft.versionActualisee = actualise.avancement?.id ?? 0;
    draft.comparison = true;
    draft.error = '';
  } catch {
    draft.error = 'Impossible de récupérer la version actuelle. Votre saisie est conservée.';
  } finally {
    saving.value = null;
  }
}
function conserverSaisie(groupe) {
  const draft = drafts.value[groupe.id];
  draft.versionId = draft.versionActualisee;
  draft.conflict = false;
  draft.comparison = false;
}
function enregistrerAvecEntree(event, groupe) {
  if (event.isComposing || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) return;
  event.preventDefault();
  enregistrer(groupe);
}
async function ouvrirHistorique(groupe) {

  historiqueGroupe.value = groupe;
  historique.value = [];
  historiqueAvant.value = null;
  await chargerHistorique();
}
async function chargerHistorique() {
  if (historiqueLoading.value && historique.value.length) return;


  const groupeId = historiqueGroupe.value?.id;
  if (!groupeId) return;
  historiqueLoading.value = true;
  historiqueErreur.value = '';
  try {
    const result = await getAvancementsEnseignementService({ enseignement: props.enseignement.id, groupe: groupeId, anneeUniversitaire: anneeChargee.value, avant: historiqueAvant.value || undefined }, '/historique');

    historique.value.push(...result.items);
    historiqueAvant.value = result.avant;
  } catch {
    historiqueErreur.value = 'Impossible de charger l’historique.';
  } finally {
    historiqueLoading.value = false;
  }
}
const {destination, accessible} = useEnseignementPage('avancement', () => props.enseignement);
</script>

<template>
  <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" modal
          :header="`Avancement · ${enseignement?.codeEnseignement} ${enseignement?.libelle}`" :aria-labelledby="`avancement-${enseignement?.id}`"
          :closable="saving === null" :close-on-escape="saving === null" class="w-[90vw] md:w-[75vw] lg:w-[75vw]">
    <p class="mt-0! mb-1! font-semibold">{{ enseignement?.libelle }}</p>
    <p class="mt-0! text-sm text-muted-color">Un repère partagé par groupe, visible et modifiable par les enseignants de cet enseignement. Entrée pour enregistrer, Maj + Entrée pour revenir à la ligne.</p>
    <p v-if="loading" role="status" class="text-muted-color">Chargement des groupes et de leurs repères…</p>
    <div v-else-if="error" role="alert" class="flex flex-col items-start gap-3">
      <p class="m-0!">{{ error }}</p><Button label="Réessayer" icon="pi pi-refresh" severity="secondary" @click="charger" />
    </div>
    <template v-else>
      <p v-if="anneeLibelle" class="text-xs text-muted-color">Année universitaire {{ anneeLibelle }}</p>
      <SelectButton v-if="types.length > 1" v-model="filtreType" :options="filtres" option-label="label" option-value="value" :allow-empty="false" aria-label="Type de groupe" size="small" class="mb-4 flex-wrap" />
      <div v-if="groupes.length" class="flex flex-col gap-3">
        <article v-for="groupe in groupesFiltres" :key="groupe.id" class="p-4 border border-surface-200 dark:border-surface-700 rounded-xl">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <h3 class="m-0! text-base!">{{ groupe.libelle }} <span class="font-normal text-sm text-muted-color">· {{ groupe.type }}</span></h3>
            <Button v-if="groupe.avancement" label="Historique" icon="pi pi-history" text severity="secondary" size="small" @click="ouvrirHistorique(groupe)" />
          </div>
          <template v-if="drafts[groupe.id]">
            <form @submit.prevent="enregistrer(groupe)" class="flex flex-col gap-3">
              <div v-if="sequences.length" class="flex flex-col gap-2">
                <label :for="`sequence-${groupe.id}`" class="text-sm font-medium">Séquence du plan de cours <span class="text-muted-color font-normal">(facultative)</span></label>
                <Select :input-id="`sequence-${groupe.id}`" v-model="drafts[groupe.id].sequenceId" :options="optionsSequences" option-label="libelle" option-value="id" show-clear filter placeholder="Repère libre / choisir une séquence" :disabled="saving === groupe.id" @update:model-value="changerSequence(groupe)">
                  <template #option="{ option }"><span class="whitespace-normal max-w-xl text-sm">{{ option.libelle }}</span></template>
                </Select>
                <SelectButton v-if="drafts[groupe.id].sequenceId" v-model="drafts[groupe.id].etatSequence" :options="etatsSequence" option-label="label" option-value="value" :allow-empty="false" :disabled="saving === groupe.id" :aria-label="`État de la séquence pour ${groupe.libelle}`" size="small" />
              </div>
              <p v-else class="m-0! text-xs text-muted-color">Aucune séquence renseignée dans le plan de cours de cette année : utilisez un repère libre.</p>
              <p v-if="drafts[groupe.id].sequenceRetiree" class="m-0! text-xs text-muted-color">L’ancienne séquence n’est plus disponible. Son intitulé a été repris dans le repère libre ; l’historique est conservé.</p>
              <div class="flex flex-col gap-1">
                <label :for="`repere-${groupe.id}`" class="text-sm font-medium">{{ drafts[groupe.id].sequenceId ? 'Précision' : 'Où j’en suis' }} <span v-if="!drafts[groupe.id].sequenceId" aria-hidden="true">*</span><span v-else class="text-muted-color font-normal">(facultative)</span></label>
                <Textarea v-focus :id="`repere-${groupe.id}`" v-model="drafts[groupe.id].repere" auto-resize :rows="2" :maxlength="2000" :required="!drafts[groupe.id].sequenceId" :disabled="saving === groupe.id" placeholder="Ex. Chapitre 3 terminé, reprendre à l’exercice 7." @keydown.enter="enregistrerAvecEntree($event, groupe)" />
              </div>
              <div class="flex flex-col gap-1">
                <label :for="`etape-${groupe.id}`" class="text-sm font-medium">Pour la prochaine séance <span class="text-muted-color font-normal">(facultatif)</span></label>
                <Textarea :id="`etape-${groupe.id}`" v-model="drafts[groupe.id].prochaineEtape" auto-resize :rows="1" :maxlength="2000" :disabled="saving === groupe.id" placeholder="Ex. Corriger l’exercice 7 et présenter le projet." @keydown.enter="enregistrerAvecEntree($event, groupe)" />
              </div>
              <p v-if="drafts[groupe.id].error" role="alert" class="m-0! text-sm text-orange-600 dark:text-orange-300">{{ drafts[groupe.id].error }}</p>
              <div v-if="drafts[groupe.id].comparison" class="rounded-lg p-3 bg-surface-100 dark:bg-surface-800">
                <p class="m-0! text-sm font-semibold">Version actuelle · {{ groupe.avancement?.auteur }} · {{ dateLibelle(groupe.avancement?.date) }}</p>
                <EnseignementAvancementRepere :avancement="groupe.avancement" class="mt-2" />
                <p v-if="groupe.avancement?.prochaineEtape" class="mt-1! mb-0! text-sm whitespace-pre-wrap">Prochaine séance : {{ groupe.avancement.prochaineEtape }}</p>
                <Button label="Conserver ma saisie pour la prochaine mise à jour" text size="small" @click="conserverSaisie(groupe)" />
              </div>
              <div class="flex flex-wrap gap-2">
                <Button v-if="drafts[groupe.id].conflict" type="button" label="Comparer avec la version actuelle" icon="pi pi-refresh" size="small" :loading="saving === groupe.id" @click="comparer(groupe)" />
                <Button v-else type="submit" label="Enregistrer" icon="pi pi-check" size="small" :loading="saving === groupe.id" :disabled="saving !== null && saving !== groupe.id" />
                <Button type="button" label="Annuler" severity="secondary" text size="small" :disabled="saving === groupe.id" @click="delete drafts[groupe.id]" />
              </div>
            </form>
          </template>
          <template v-else>
            <button type="button" class="w-full text-left rounded-lg p-3 bg-surface-50 dark:bg-surface-800 hover:bg-surface-100 dark:hover:bg-surface-700 focus-visible:outline-2 focus-visible:outline-primary" :disabled="saving !== null" :aria-label="`Modifier le repère du groupe ${groupe.libelle}`" @click="modifier(groupe)">
              <EnseignementAvancementRepere :avancement="groupe.avancement" />
              <span v-if="groupe.avancement?.prochaineEtape" class="block mt-2 text-sm text-muted-color whitespace-pre-wrap"><span class="font-medium">Prochaine séance :</span> {{ groupe.avancement.prochaineEtape }}</span>
              <span class="block mt-2 text-xs text-primary"><i class="pi pi-pencil mr-1" aria-hidden="true" />{{ groupe.avancement ? 'Mettre à jour' : 'Renseigner l’avancement' }}</span>
            </button>
          </template>
          <p v-if="groupe.avancement" class="mt-2! mb-0! text-xs text-muted-color">{{ groupe.avancement.auteur }} · {{ dateLibelle(groupe.avancement.date) }}</p>
          <p v-if="saved === groupe.id" role="status" class="mt-2! mb-0! text-xs text-primary">Repère enregistré et partagé avec vos collègues.</p>
        </article>
      </div>
      <EmptyState v-else compact title="Aucun groupe n’est rattaché aux semestres des UE de cet enseignement pour cette année." />
    </template>
    <template #footer>
      <div class="w-full border-t border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800/50 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3 text-left">
          <span class="flex items-center justify-center shrink-0 w-10 h-10 rounded-lg bg-primary/10 text-primary" aria-hidden="true"><i class="pi pi-map-marker" /></span>
          <div>
            <p class="m-0! text-sm font-semibold text-color">Mon espace pédagogique</p>
            <p class="m-0! mt-1! text-xs text-muted-color">Retrouvez vos enseignements et leur suivi.</p>
          </div>
        </div>
        <Button v-if="accessible" label="Voir mes enseignements" icon="pi pi-arrow-right" icon-pos="right" class="w-full sm:w-auto shrink-0" severity="primary" @click="router.push(destination)" />
      </div>
    </template>
  </Dialog>

  <Dialog :visible="!!historiqueGroupe" @update:visible="value => { if (!value) { historiqueGroupe = null; historiqueLoading = false; } }" modal :header="`Historique · ${historiqueGroupe?.libelle ?? ''}`" class="w-full max-w-2xl mx-4">
    <ol class="list-none p-0! m-0! flex flex-col gap-3">
      <li v-for="version in historique" :key="version.id" class="p-4 rounded-xl border border-surface-200 dark:border-surface-700">
        <p class="mt-0! text-xs text-muted-color">{{ version.auteur }} · {{ dateLibelle(version.date) }}</p>
        <EnseignementAvancementRepere :avancement="version" />
        <p v-if="version.prochaineEtape" class="mb-0! text-sm whitespace-pre-wrap"><span class="font-semibold">Prochaine séance :</span> {{ version.prochaineEtape }}</p>
      </li>
    </ol>
    <ErrorState v-if="historiqueErreur" class="text-sm" compact :show-navigation="false" :message="historiqueErreur" />
    <EmptyState v-if="!historiqueLoading && !historique.length && !historiqueErreur" compact title="Aucune modification enregistrée." />
    <Button v-if="historiqueAvant || historiqueErreur" :label="historiqueErreur ? 'Réessayer' : 'Voir les modifications précédentes'" severity="secondary" size="small" :loading="historiqueLoading" class="mt-4" @click="chargerHistorique" />
    <p v-else-if="historiqueLoading" role="status" class="text-sm text-muted-color">Chargement…</p>
  </Dialog>
</template>
