<script setup>
import { Card, Alert, LoadingBar } from '@components';
import { showSuccess, showWarn } from '@helpers/toast';
import { computed, reactive, ref, watch } from 'vue';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import EvaluationBloc from './EvaluationBloc.vue';
import { lotService } from '../../requests/evaluationService';
import { messageErreur } from '../../requests/traceService';
import { couleurCompetence, texteSurCompetence } from '../traces/options';

/**
 * Évaluation « tout en un » : tous les points d'une trace ou de l'année d'un étudiant sur une seule page.
 * Rien n'est créé en base tant que rien n'est enregistré ; « Rendre » enregistre puis remet d'un coup
 * tous les points renseignés (tout ou rien).
 *
 * `portee` : { type: 'trace', uuid } ou { type: 'bilans', campagne, etudiant }.
 */
const props = defineProps({
  portee: { type: Object, required: true },
  declarationIa: { type: Object, default: null },
});
const emit = defineEmits(['change', 'charge']);


const lot = ref(null);
const saisies = reactive({});
const ouverts = reactive({});
const chargement = ref(true);
const occupe = ref(false);
const erreurChargement = ref(null);
const erreurs = ref([]);
const visibleEtudiant = ref(true);
const revisionOuverte = ref(false);
const motif = ref('');

const clef = (item) => item.cle;
const grilleDe = (item) => lot.value.grilles[String(item.grilleVersion)] ?? lot.value.grilles[String(lot.value.versionCourante)];

const initialiser = (data) => {
  lot.value = data;
  for (const item of data.items) {
    saisies[item.cle] = {
      reponses: Object.fromEntries((item.evaluation?.reponses ?? []).map((r) => [r.critere, { ...r }])),
      appreciation: item.evaluation?.appreciation ?? '',
      dirty: false,
    };
    ouverts[item.cle] ??= true;
  }
};

const charger = async () => {
  chargement.value = true;
  erreurChargement.value = null;
  try {
    const { data } = await lotService.charger(props.portee);
    initialiser(data);
    emit('charge', data);
  } catch (e) {
    erreurChargement.value = messageErreur(e, 'Impossible de charger l\'évaluation.');
  } finally {
    chargement.value = false;
  }
};
watch(() => props.portee, charger, { immediate: true, deep: true });

// ---------- Etat ----------
const modifiable = (item) => lot.value.peutEvaluer && (!item.evaluation || item.evaluation.modifiable);
const modifiables = computed(() => (lot.value?.items ?? []).filter(modifiable));
const sales = computed(() => modifiables.value.filter((i) => saisies[i.cle]?.dirty));
const contenu = (item) => {
  const s = saisies[item.cle];
  return !!s && (Object.values(s.reponses).some((r) => (r.echelon ?? null) !== null || (r.valeur ?? null) !== null || (r.booleen ?? null) !== null || String(r.texte ?? '').trim() !== '') || s.appreciation.trim() !== '');
};
const nbRenseignes = computed(() => modifiables.value.filter(contenu).length);
const nbRendus = computed(() => (lot.value?.items ?? []).filter((i) => i.evaluation && !i.evaluation.modifiable).length);
const nbEnCours = computed(() => (lot.value?.items ?? []).filter((i) => i.evaluation?.modifiable).length);
const toutOuvert = computed(() => (lot.value?.items ?? []).every((i) => ouverts[i.cle]));

const groupes = computed(() => {
  const map = new Map();
  for (const item of lot.value?.items ?? []) {
    const id = item.competence?.id ?? 0;
    if (!map.has(id)) map.set(id, { competence: item.competence, items: [] });
    map.get(id).items.push(item);
  }
  return [...map.values()];
});

const basculerTout = () => {
  const ouvrir = !toutOuvert.value;
  for (const item of lot.value.items) ouverts[item.cle] = ouvrir;
};

// ---------- Remplir d'un coup ----------
const criteresEchelle = computed(() => (lot.value?.grilles[String(lot.value.versionCourante)]?.criteres ?? []).filter((c) => c.type === 'echelle' && c.source !== 'declaration_ia'));
const echelonsCourants = computed(() => lot.value?.grilles[String(lot.value.versionCourante)]?.echelons ?? []);
const normaliser = (t) => String(t ?? '').trim().toLowerCase();
const remplirPourTous = (critere, echelon) => {
  for (const item of modifiables.value) {
    const grille = grilleDe(item);
    const c = grille.criteres.find((x) => normaliser(x.libelle) === normaliser(critere.libelle));
    const e = grille.echelons.find((x) => normaliser(x.libelle) === normaliser(echelon.libelle));
    if (!c || !e) continue;
    const s = saisies[item.cle];
    s.reponses = { ...s.reponses, [c.id]: { critere: c.id, echelon: e.id } };
    s.dirty = true;
  }
};
const remplissageOuvert = ref(false);

// ---------- Actions ----------
const executer = async (fn, succes) => {
  occupe.value = true;
  erreurs.value = [];
  try {
    const { data } = await fn();
    initialiser(data);
    emit('change');
    if (succes) {
      const message = typeof succes === 'function' ? succes(data) : succes;
      showSuccess(undefined, message, 3500);
    }
    return true;
  } catch (e) {
    const liste = e?.response?.data?.erreurs;
    erreurs.value = Array.isArray(liste) && liste.length ? liste : [messageErreur(e, 'Action impossible.')];
    showWarn(erreurs.value.length > 1 ? `${erreurs.value.length} points à corriger` : erreurs.value[0], 'Action impossible', 7000);
    return false;
  } finally {
    occupe.value = false;
  }
};

const payload = () => sales.value.map((item) => ({
  cle: item.cle,
  appreciation: saisies[item.cle].appreciation,
  reponses: Object.values(saisies[item.cle].reponses),
}));

const enregistrer = () => (sales.value.length
  ? executer(() => lotService.enregistrer(props.portee, payload()), 'Évaluation enregistrée')
  : Promise.resolve(true));

const rendre = async () => {
  if (sales.value.length && !(await enregistrer())) return;
  await executer(
    () => lotService.rendre(props.portee, visibleEtudiant.value),
    (d) => `${d.nbRendues} point(s) rendu(s)${visibleEtudiant.value ? ' à l\'étudiant' : ' (non communiqués)'}`,
  );
};

const rouvrirTout = () => executer(() => lotService.rouvrir(props.portee), (d) => `${d.nbRouvertes} point(s) rouvert(s)`);
const rouvrirUn = (item) => executer(() => lotService.rouvrir(props.portee, [item.cle]), 'Point rouvert');

const envoyerRevision = async () => {
  if (await executer(() => lotService.demanderRevision(props.portee, motif.value), 'Révision demandée à l\'étudiant')) {
    revisionOuverte.value = false;
    motif.value = '';
  }
};

const traceARevoir = computed(() => lot.value?.traceStatut?.value === 'a_reviser');
</script>

<template>
  <section class="flex flex-col gap-4" aria-label="Évaluation">
    <LoadingBar v-if="chargement" />
    <Alert icon-native v-else-if="erreurChargement" severity="warn" :closable="false">{{ erreurChargement }}</Alert>

    <template v-else-if="lot">
      <!-- Barre d'actions : reste visible pendant la saisie -->
      <Card bare class="p-3 flex flex-col gap-3 sticky top-2 z-10 shadow-sm">
        <div class="flex flex-wrap items-center gap-2 text-sm">
          <strong>{{ lot.items.length }} point(s)</strong>
          <Tag :value="`${nbRenseignes} renseigné(s)`" :severity="nbRenseignes ? 'info' : 'secondary'" />
          <Tag v-if="nbEnCours" :value="`${nbEnCours} en cours`" severity="warn" />
          <Tag v-if="nbRendus" :value="`${nbRendus} rendu(s)`" severity="success" />
          <Tag v-if="sales.length" :value="`${sales.length} à enregistrer`" severity="warn" icon="pi pi-pencil" />
          <Button :label="toutOuvert ? 'Tout replier' : 'Tout déplier'" :icon="toutOuvert ? 'pi pi-angle-double-up' : 'pi pi-angle-double-down'" text size="small" class="ml-auto" @click="basculerTout" />
        </div>

        <Alert icon-native v-if="!lot.peutEvaluer" severity="info" :closable="false">
          {{ lot.campagne ? `La campagne « ${lot.campagne.libelle} » n'accepte plus (ou pas encore) d'évaluations.` : 'Aucune campagne d\'évaluation n\'est définie pour cette année.' }}
        </Alert>
        <Alert icon-native v-if="lot.mode === 'trace' && traceARevoir" severity="warn" :closable="false">Une révision a été demandée à l'étudiant : en attente de son nouveau dépôt.</Alert>
        <Alert icon-native v-if="lot.autoEvaluationRequise && lot.peutEvaluer" severity="secondary" :closable="false">Cette campagne exige l'auto-évaluation de l'étudiant avant l'évaluation.</Alert>

        <Alert icon-native v-if="erreurs.length" severity="error" :closable="true" @close="erreurs = []">
          <ul class="m-0 pl-4 flex flex-col gap-1">
            <li v-for="e in erreurs" :key="e">{{ e }}</li>
          </ul>
        </Alert>

        <div v-if="lot.peutEvaluer" class="flex flex-wrap items-center gap-2">
          <label class="flex items-center gap-2 text-sm cursor-pointer mr-auto">
            <ToggleSwitch v-model="visibleEtudiant" /> Communiquer à l'étudiant
          </label>
          <Button v-if="criteresEchelle.length && modifiables.length > 1" :label="remplissageOuvert ? 'Fermer' : 'Remplir d\'un coup'" icon="pi pi-bolt" severity="secondary" text size="small" @click="remplissageOuvert = !remplissageOuvert" />
          <Button v-if="lot.mode === 'trace' && !traceARevoir" label="Demander une révision" icon="pi pi-replay" severity="warn" outlined size="small" @click="revisionOuverte = !revisionOuverte" />
          <Button v-if="nbRendus" label="Tout rouvrir" icon="pi pi-undo" severity="secondary" outlined size="small" :loading="occupe" @click="rouvrirTout" />
          <Button label="Enregistrer" icon="pi pi-save" outlined size="small" :disabled="!sales.length" :loading="occupe" @click="enregistrer" />
          <Button :label="`Rendre${nbRenseignes ? ` (${nbRenseignes})` : ''}`" icon="pi pi-check-circle" severity="success" size="small" :disabled="!nbRenseignes && !sales.length" :loading="occupe" @click="rendre" />
        </div>

        <div v-if="remplissageOuvert" class="rounded-lg border border-surface-200 p-3 flex flex-col gap-3">
          <p class="m-0 text-sm text-muted-color">Applique le même niveau à tous les points modifiables ; vous ajustez ensuite point par point.</p>
          <div v-for="c in criteresEchelle" :key="c.id" class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium w-56">{{ c.libelle }}</span>
            <button
              v-for="e in echelonsCourants"
              :key="e.id"
              type="button"
              class="px-3 py-1 rounded-lg border-2 text-xs font-medium cursor-pointer bg-transparent"
              :style="{ borderColor: e.couleur || 'var(--p-content-border-color)' }"
              @click="remplirPourTous(c, e)"
            >{{ e.libelle }}</button>
          </div>
        </div>

        <div v-if="revisionOuverte" class="rounded-lg border border-amber-300 bg-amber-50/60 dark:bg-amber-950/20 p-3 flex flex-col gap-2">
          <label for="lot-motif" class="font-medium">Que doit réviser l'étudiant ?</label>
          <Textarea id="lot-motif" v-model="motif" rows="3" auto-resize placeholder="Ce message est envoyé à l'étudiant (un seul e-mail) avec la demande." />
          <div class="flex justify-end gap-2">
            <Button label="Annuler" severity="secondary" text size="small" @click="revisionOuverte = false" />
            <Button label="Envoyer la demande" icon="pi pi-send" severity="warn" size="small" :disabled="!motif.trim()" :loading="occupe" @click="envoyerRevision" />
          </div>
        </div>
      </Card>

      <!-- Points à évaluer, regroupés par compétence -->
      <div v-for="g in groupes" :key="g.competence?.id ?? 0" class="flex flex-col gap-2">
        <div class="flex items-center gap-2">
          <span
            class="text-xs font-semibold px-2 py-1 rounded"
            :style="{ background: couleurCompetence(g.competence?.couleur), color: texteSurCompetence(g.competence?.couleur) }"
          >{{ g.competence?.nomCourt ?? 'Compétence' }}</span>
          <span class="text-sm text-muted-color line-clamp-1">{{ g.competence?.libelle }}</span>
        </div>
        <EvaluationBloc
          v-for="item in g.items"
          :key="clef(item)"
          :item="item"
          :grille="grilleDe(item)"
          :saisie="saisies[item.cle]"
          :modifiable="modifiable(item)"
          :ouvert="!!ouverts[item.cle]"
          :declaration-ia="declarationIa"
          :peut-rouvrir="lot.peutEvaluer && !!item.evaluation && !item.evaluation.modifiable"
          @update:saisie="(v) => (saisies[item.cle] = v)"
          @basculer="ouverts[item.cle] = !ouverts[item.cle]"
          @rouvrir="rouvrirUn(item)"
        />
      </div>

      <p v-if="lot.items.length === 0" class="text-muted-color m-0">Aucun point à évaluer.</p>
    </template>
  </section>
</template>
