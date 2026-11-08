<script setup>
/**
 * Avenants d'un dossier de stage, côté étudiant : liste (en attente / validé) et demande d'un nouvel avenant.
 * Une demande n'est possible que pour une convention signée (avant, l'étudiant modifie directement sa demande).
 */
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import api from '@helpers/axios';
import { messageErreurApi } from '@/utils/erreurApi';

const props = defineProps({
  stage: { type: Object, required: true } // { id, etatStage, avenants: [...] } (réponse de l'API)
});
const emit = defineEmits(['cree']);
const toast = useToast();

const TYPES = [
  { value: 'DATES', label: 'Dates du stage' },
  { value: 'HORAIRES', label: 'Durée hebdomadaire' },
  { value: 'GRATIFICATION', label: 'Gratification' },
  { value: 'TUTEUR', label: 'Tuteur en entreprise' },
  { value: 'AUTRE', label: 'Autre modification' }
];
const libelleType = (type) => TYPES.find(t => t.value === type)?.label || type;

const peutDemander = computed(() => props.stage?.etatStage === 'ETAT_STAGE_CONVENTION_RECUE');
const avenants = computed(() => [...(props.stage?.avenants || [])]
  .sort((a, b) => new Date(b.dateCreation) - new Date(a.dateCreation)));
const enAttente = computed(() => avenants.value.some(a => !a.valide));

const formulaireVide = () => ({
  libelle: '',
  typeModification: 'DATES',
  texte: '',
  newDateDebut: '',
  newDateFin: '',
  newDureeHebdomadaire: null,
  newGratificationMontant: null,
  tuteur: { civilite: 'M', prenom: '', nom: '', fonction: '', email: '', telephone: '' }
});
const visible = ref(false);
const envoi = ref(false);
const form = ref(formulaireVide());

const ouvrir = () => {
  form.value = formulaireVide();
  visible.value = true;
};

const dateFr = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR') : '');

const envoyer = async () => {
  const f = form.value;
  const payload = {
    stageEtudiant: `/api/stage_etudiants/${props.stage.id}`,
    libelle: f.libelle.trim(),
    typeModification: f.typeModification,
    texte: f.texte.trim()
  };
  if (f.typeModification === 'DATES') {
    payload.newDateDebut = f.newDateDebut || null;
    payload.newDateFin = f.newDateFin || null;
  } else if (f.typeModification === 'HORAIRES') {
    payload.newDureeHebdomadaire = f.newDureeHebdomadaire !== null ? Number(f.newDureeHebdomadaire) : null;
  } else if (f.typeModification === 'GRATIFICATION') {
    payload.newGratificationMontant = f.newGratificationMontant !== null ? Number(f.newGratificationMontant) : null;
  } else if (f.typeModification === 'TUTEUR' && f.tuteur.nom.trim()) {
    payload.newTuteur = { ...f.tuteur };
  }

  envoi.value = true;
  try {
    await api.post('/api/stage_avenants', payload, { headers: { 'Content-Type': 'application/ld+json' } });
    toast.add({ severity: 'success', summary: 'Demande envoyée', detail: 'Votre demande d\'avenant est transmise au responsable des stages.', life: 4000 });
    visible.value = false;
    emit('cree');
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Demande non envoyée', detail: messageErreurApi(error, 'Impossible d\'envoyer la demande d\'avenant.'), life: 6000 });
  } finally {
    envoi.value = false;
  }
};
</script>

<template>
  <div v-if="peutDemander || avenants.length > 0" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700/50 space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h4 class="text-xs font-extrabold text-slate-800 dark:text-white uppercase tracking-wider">Avenants à la convention</h4>
      <button v-if="peutDemander" type="button" :disabled="enAttente" @click="ouvrir"
        class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs rounded-xl shadow transition-all"
        :title="enAttente ? 'Une demande est déjà en attente de validation' : ''">
        Demander un avenant
      </button>
    </div>

    <ul v-if="avenants.length > 0" class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
      <li v-for="a in avenants" :key="a.id" class="py-2 flex flex-wrap items-center justify-between gap-2">
        <div class="min-w-0">
          <span class="font-bold text-slate-800 dark:text-slate-200">{{ a.libelle }}</span>
          <span class="text-slate-400"> — {{ libelleType(a.typeModification) }}, demandé le {{ dateFr(a.dateCreation) }}</span>
        </div>
        <span v-if="a.valide" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 font-bold text-[10px]">
          Validé le {{ dateFr(a.dateValidation) }}
        </span>
        <span v-else class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 font-bold text-[10px]">
          En attente de validation
        </span>
      </li>
    </ul>
    <p v-else class="text-xs text-slate-400">Aucun avenant pour cette convention.</p>

    <Dialog v-model:visible="visible" modal header="Demander un avenant" :style="{ width: '36rem' }" :breakpoints="{ '640px': '95vw' }">
      <form class="space-y-3 text-xs" @submit.prevent="envoyer">
        <label class="block">
          <span class="font-bold text-slate-600 dark:text-slate-300">Objet</span>
          <input v-model="form.libelle" required maxlength="150" type="text" placeholder="Ex. : prolongation de deux semaines"
            class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" />
        </label>
        <label class="block">
          <span class="font-bold text-slate-600 dark:text-slate-300">Type de modification</span>
          <select v-model="form.typeModification" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900">
            <option v-for="t in TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </label>

        <div v-if="form.typeModification === 'DATES'" class="grid grid-cols-2 gap-3">
          <label class="block"><span class="font-bold text-slate-600 dark:text-slate-300">Nouvelle date de début</span>
            <input v-model="form.newDateDebut" type="date" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" /></label>
          <label class="block"><span class="font-bold text-slate-600 dark:text-slate-300">Nouvelle date de fin</span>
            <input v-model="form.newDateFin" type="date" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" /></label>
        </div>
        <label v-else-if="form.typeModification === 'HORAIRES'" class="block">
          <span class="font-bold text-slate-600 dark:text-slate-300">Nouvelle durée hebdomadaire (heures)</span>
          <input v-model="form.newDureeHebdomadaire" type="number" min="1" max="48" step="0.5" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" />
        </label>
        <label v-else-if="form.typeModification === 'GRATIFICATION'" class="block">
          <span class="font-bold text-slate-600 dark:text-slate-300">Nouvelle gratification (€ / heure)</span>
          <input v-model="form.newGratificationMontant" type="number" min="0" step="0.01" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" />
        </label>
        <div v-else-if="form.typeModification === 'TUTEUR'" class="grid grid-cols-2 gap-3">
          <label class="block"><span class="font-bold text-slate-600 dark:text-slate-300">Prénom</span>
            <input v-model="form.tuteur.prenom" type="text" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" /></label>
          <label class="block"><span class="font-bold text-slate-600 dark:text-slate-300">Nom</span>
            <input v-model="form.tuteur.nom" type="text" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" /></label>
          <label class="block"><span class="font-bold text-slate-600 dark:text-slate-300">Fonction</span>
            <input v-model="form.tuteur.fonction" type="text" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" /></label>
          <label class="block"><span class="font-bold text-slate-600 dark:text-slate-300">E-mail</span>
            <input v-model="form.tuteur.email" type="email" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" /></label>
          <label class="block col-span-2"><span class="font-bold text-slate-600 dark:text-slate-300">Téléphone</span>
            <input v-model="form.tuteur.telephone" type="tel" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900" /></label>
        </div>

        <label class="block">
          <span class="font-bold text-slate-600 dark:text-slate-300">Motif et détail de la demande</span>
          <textarea v-model="form.texte" required rows="4" class="mt-1 w-full p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900"></textarea>
        </label>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="px-3 py-2 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700" @click="visible = false">Annuler</button>
          <button type="submit" :disabled="envoi" class="px-3 py-2 text-xs font-bold rounded-xl bg-violet-600 hover:bg-violet-700 text-white disabled:opacity-50">
            {{ envoi ? 'Envoi…' : 'Envoyer la demande' }}
          </button>
        </div>
      </form>
    </Dialog>
  </div>
</template>
