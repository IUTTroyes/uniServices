<script setup>
/**
 * Avenants d'un dossier de stage, côté étudiant : liste (en attente / validé) et demande d'un nouvel avenant.
 * Une demande n'est possible que pour une convention signée (avant, l'étudiant modifie directement sa demande).
 */
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import { EmptyState } from '@components';
import { createStageAvenantService } from '@/requests/stage_service';
import { messageErreurApi } from '@/utils/erreurApi';
import { dateFr } from '@/utils/stage';

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
    await createStageAvenantService(payload);
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
      <h4 class="text-sm font-bold text-slate-800 dark:text-white">Avenants à la convention</h4>
      <Button v-if="peutDemander" label="Demander un avenant" icon="pi pi-file-edit" size="small" :disabled="enAttente"
        v-tooltip.top="enAttente ? 'Une demande est déjà en attente de validation' : null" @click="ouvrir" />
    </div>

    <ul v-if="avenants.length > 0" class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
      <li v-for="a in avenants" :key="a.id" class="py-2 flex flex-wrap items-center justify-between gap-2">
        <div class="min-w-0">
          <span class="font-semibold text-slate-800 dark:text-slate-200">{{ a.libelle }}</span>
          <span class="text-slate-500 dark:text-slate-400"> — {{ libelleType(a.typeModification) }}, demandé le {{ dateFr(a.dateCreation) }}</span>
        </div>
        <Tag v-if="a.valide" severity="success" :value="`Validé le ${dateFr(a.dateValidation)}`" />
        <Tag v-else severity="warn" value="En attente de validation" />
      </li>
    </ul>
    <EmptyState v-else compact title="Aucun avenant" description="Votre convention n'a fait l'objet d'aucun avenant." icon="pi pi-file" />

    <Dialog v-model:visible="visible" modal header="Demander un avenant" :style="{ width: '36rem' }" :breakpoints="{ '640px': '95vw' }">
      <form class="q-form" @submit.prevent="envoyer">
        <div>
          <label for="avenant-libelle" class="q-label q-label-required">Objet</label>
          <input id="avenant-libelle" v-model="form.libelle" required maxlength="150" type="text" placeholder="Ex. : prolongation de deux semaines" class="q-input" />
        </div>
        <div>
          <label for="avenant-type" class="q-label">Type de modification</label>
          <select id="avenant-type" v-model="form.typeModification" class="q-input">
            <option v-for="t in TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </div>

        <div v-if="form.typeModification === 'DATES'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="avenant-debut" class="q-label">Nouvelle date de début</label>
            <input id="avenant-debut" v-model="form.newDateDebut" type="date" class="q-input" />
          </div>
          <div>
            <label for="avenant-fin" class="q-label">Nouvelle date de fin</label>
            <input id="avenant-fin" v-model="form.newDateFin" type="date" class="q-input" />
          </div>
        </div>
        <div v-else-if="form.typeModification === 'HORAIRES'">
          <label for="avenant-heures" class="q-label">Nouvelle durée hebdomadaire (heures)</label>
          <input id="avenant-heures" v-model="form.newDureeHebdomadaire" type="number" min="1" max="48" step="0.5" class="q-input" />
        </div>
        <div v-else-if="form.typeModification === 'GRATIFICATION'">
          <label for="avenant-gratification" class="q-label">Nouvelle gratification (€ / heure)</label>
          <input id="avenant-gratification" v-model="form.newGratificationMontant" type="number" min="0" step="0.01" class="q-input" />
        </div>
        <div v-else-if="form.typeModification === 'TUTEUR'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="avenant-tuteur-prenom" class="q-label">Prénom</label>
            <input id="avenant-tuteur-prenom" v-model="form.tuteur.prenom" type="text" class="q-input" />
          </div>
          <div>
            <label for="avenant-tuteur-nom" class="q-label">Nom</label>
            <input id="avenant-tuteur-nom" v-model="form.tuteur.nom" type="text" class="q-input" />
          </div>
          <div>
            <label for="avenant-tuteur-fonction" class="q-label">Fonction</label>
            <input id="avenant-tuteur-fonction" v-model="form.tuteur.fonction" type="text" class="q-input" />
          </div>
          <div>
            <label for="avenant-tuteur-email" class="q-label">E-mail</label>
            <input id="avenant-tuteur-email" v-model="form.tuteur.email" type="email" class="q-input" />
          </div>
          <div class="sm:col-span-2">
            <label for="avenant-tuteur-tel" class="q-label">Téléphone</label>
            <input id="avenant-tuteur-tel" v-model="form.tuteur.telephone" type="tel" class="q-input" />
          </div>
        </div>

        <div>
          <label for="avenant-texte" class="q-label q-label-required">Motif et détail de la demande</label>
          <textarea id="avenant-texte" v-model="form.texte" required rows="4" class="q-input"></textarea>
        </div>

        <div class="q-actions">
          <Button type="button" label="Annuler" severity="secondary" text @click="visible = false" />
          <Button type="submit" label="Envoyer la demande" icon="pi pi-send" :loading="envoi" />
        </div>
      </form>
    </Dialog>
  </div>
</template>
