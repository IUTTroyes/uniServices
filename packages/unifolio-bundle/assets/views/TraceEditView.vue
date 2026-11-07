<script setup>
import { showDanger, showSuccess, showWarn } from '@helpers/toast';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { HeaderComponent, Card, Alert, LoadingBar } from '@components';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import TraceDescription from '../components/traces/TraceDescription.vue';
import TraceDeclarationIa from '../components/traces/TraceDeclarationIa.vue';
import MediaManager from '../components/traces/MediaManager.vue';
import RattachementPicker from '../components/traces/RattachementPicker.vue';
import traceService, { messageErreur } from '../requests/traceService';
import { useTraceReferenceStore } from '../stores/traceReferenceStore';
import {
  CONCEPTION_IA_NON_RENSEIGNE, REALISATION_IA_NON_RENSEIGNE, titreDepuisNomFichier,
} from '../components/traces/options';

const route = useRoute();
const router = useRouter();
const references = useTraceReferenceStore();

const trace = ref(null);
// Sur « nouvelle », la trace peut avoir été créée à la volée par le dépôt d'une preuve.
const uuid = computed(() => route.params.uuid ?? trace.value?.uuid ?? null);
const chargement = ref(false);
const enregistrement = ref(false);
const competences = ref([]);
const granularite = ref(null);
const rattachements = ref([]);
const rattachementsModifies = ref(false);

const form = reactive({
  titre: '',
  scolarite: null,
  dateRealisation: null,
  typeActivite: null,
  contexte: '',
  conceptionIa: null,
  realisationIa: null,
  usageIaDetail: '',
});

const modifiable = computed(() => !trace.value || trace.value.modifiable);

const versDateIso = (d) => (d ? `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}` : null);

const remplir = (t) => {
  trace.value = t;
  form.titre = t.titre;
  // Trace sans année (ancienne saisie) : on propose l'année en cours, enregistrée au prochain « Enregistrer ».
  form.scolarite = t.scolarite?.id ?? (t.modifiable ? references.scolariteActive?.id ?? null : null);
  form.dateRealisation = t.dateRealisation ? new Date(`${t.dateRealisation}T00:00:00`) : null;
  form.typeActivite = t.typeActivite?.value ?? null;
  form.contexte = t.contexte ?? '';
  form.conceptionIa = t.conceptionIa.value === CONCEPTION_IA_NON_RENSEIGNE ? null : t.conceptionIa.value;
  form.realisationIa = t.realisationIa.value === REALISATION_IA_NON_RENSEIGNE ? null : t.realisationIa.value;
  form.usageIaDetail = t.usageIaDetail ?? '';
  rattachements.value = t.rattachements.map((r) => ({ type: r.type, id: r.cibleId, argumentaire: r.argumentaire ?? '', libelle: r.libelle }));
  rattachementsModifies.value = false;
};

const recharger = async () => {
  const { data } = await traceService.lire(uuid.value);
  remplir(data);
};

/** Après un changement de preuves : met à jour la trace sans écraser la saisie en cours. */
const rafraichirPreuves = async () => {
  const { data } = await traceService.lire(uuid.value);
  trace.value = data;
};

/**
 * Crée le brouillon au premier dépôt de preuve, avec ce qui est déjà saisi.
 * Mémorise la promesse : plusieurs dépôts simultanés ne créent qu'une trace.
 */
let creationEnCours = null;
const assurerTrace = async (premierFichier = null) => {
  if (trace.value) return trace.value.uuid;
  if (!form.titre.trim()) {
    form.titre = titreDepuisNomFichier(premierFichier?.name) || 'Nouvelle trace';
  }
  creationEnCours ??= traceService.creer(charge()).then(({ data }) => {
    trace.value = data;
    return data.uuid;
  }).finally(() => { creationEnCours = null; });
  return creationEnCours;
};

watch(() => form.scolarite, async (id, ancien) => {
  if (ancien && id !== ancien && !(trace.value?.nbRattachements > 0)) {
    rattachements.value = [];
  }
  competences.value = id ? await references.referentiel(id).catch(() => []) : [];
  granularite.value = id ? references.granularites[id] ?? null : null;
});

onMounted(async () => {
  chargement.value = true;
  try {
    await references.chargerScolarites();
    if (uuid.value) {
      await recharger();
    } else {
      form.scolarite = references.scolariteActive?.id ?? null;
    }
  } catch (e) {
    showDanger(messageErreur(e), 'Chargement impossible', 6000);
  } finally {
    chargement.value = false;
  }
});

const charge = () => ({
  titre: form.titre,
  scolarite: form.scolarite,
  dateRealisation: versDateIso(form.dateRealisation),
  typeActivite: form.typeActivite,
  contexte: form.contexte,
  conceptionIa: form.conceptionIa ?? CONCEPTION_IA_NON_RENSEIGNE,
  realisationIa: form.realisationIa ?? REALISATION_IA_NON_RENSEIGNE,
  usageIaDetail: form.usageIaDetail,
});

const enregistrer = async () => {
  if (!form.titre.trim()) {
    showWarn('Donnez un titre à votre trace.', 'Titre manquant', 4000);
    return false;
  }
  enregistrement.value = true;
  try {
    let data;
    if (!trace.value) {
      ({ data } = await traceService.creer(charge()));
      trace.value = data;
    } else {
      ({ data } = await traceService.modifier(trace.value.uuid, charge()));
    }
    // L'année est enregistrée avant les rattachements (ils en dépendent).
    if (rattachementsModifies.value) {
      ({ data } = await traceService.definirRattachements(
        data.uuid,
        rattachements.value.map(({ type, id, argumentaire }) => ({ type, id, argumentaire })),
      ));
    }
    remplir(data);
    showSuccess(undefined, 'Trace enregistrée', 3000);
    if (!route.params.uuid) {
      await router.replace(`/unifolio/traces/${data.uuid}/modifier`);
    }
    return true;
  } catch (e) {
    showDanger(messageErreur(e), 'Enregistrement impossible', 7000);
    return false;
  } finally {
    enregistrement.value = false;
  }
};

const deposer = async () => {
  if (!(await enregistrer())) return;
  try {
    const { data } = await traceService.deposer(trace.value.uuid);
    remplir(data);
    showSuccess('Elle pourra être évaluée par vos enseignants.', 'Trace déposée', 4000);
    router.push(`/unifolio/traces/${data.uuid}`);
  } catch (e) {
    showWarn(messageErreur(e), 'Dépôt impossible', 8000);
  }
};

const surRattachements = (valeur) => {
  rattachements.value = valeur;
  rattachementsModifies.value = true;
};

const anneeAffichee = computed(() => references.scolarites.find((sc) => sc.id === form.scolarite)?.annee ?? null);

const changementAnneeBloque = computed(() => !!trace.value && trace.value.nbRattachements > 0);
</script>

<template>
  <HeaderComponent
    icon="pi pi-pencil"
    :titre="route.params.uuid ? 'Modifier la trace' : 'Nouvelle trace'"
    description="Déposez vos preuves, décrivez votre démarche et reliez-les aux compétences de votre année."
    :show-back="true"
    :back-url="route.params.uuid ? `/unifolio/traces/${route.params.uuid}` : '/unifolio/traces'"
    color="emerald"
  >
    <template #actions>
      <Tag v-if="trace" :value="trace.statut.libelle" :severity="trace.statut.badge" />
    </template>
  </HeaderComponent>

  <LoadingBar v-if="chargement" />

  <Alert icon-native :closable="false" v-else-if="trace && !modifiable" severity="info" class="mb-4">
    Cette trace est en cours d'évaluation ou évaluée : elle n'est plus modifiable.
  </Alert>

  <div v-if="!chargement" class="grid gap-4 xl:grid-cols-3">
    <Card bare tag="section" class="p-4 flex flex-col gap-3 xl:col-span-3">
      <div>
        <h3 class="text-base font-semibold m-0">Preuves</h3>
        <p class="text-sm text-muted-color m-0">Commencez par déposer ce que vous avez produit ; vous pourrez compléter la description ensuite.</p>
      </div>
      <MediaManager
        :uuid="uuid"
        :medias="trace?.medias ?? []"
        :modifiable="modifiable"
        :assurer-trace="assurerTrace"
        @change="rafraichirPreuves"
      />
    </Card>

    <div class="xl:col-span-2 flex flex-col gap-4">
      <TraceDescription
        v-model:titre="form.titre"
        v-model:scolarite="form.scolarite"
        v-model:date-realisation="form.dateRealisation"
        v-model:type-activite="form.typeActivite"
        v-model:contexte="form.contexte"
        :scolarites="references.scolarites"
        :modifiable="modifiable"
        :changement-annee-bloque="changementAnneeBloque"
      />

      <Card bare tag="section" class="p-4 flex flex-col gap-3">
        <h3 class="text-base font-semibold m-0">Rattachement au référentiel</h3>
        <p class="text-sm text-muted-color m-0">
          Compétences de votre année{{ anneeAffichee ? ` (${anneeAffichee})` : '' }} : cochez ce que cette trace démontre et expliquez pourquoi.
        </p>
        <Alert icon-native :closable="false" v-if="!form.scolarite" severity="warn">Choisissez l'année de BUT de la trace pour afficher son référentiel.</Alert>
        <RattachementPicker v-else :competences="competences" :granularite="granularite" :model-value="rattachements" :disabled="!modifiable" @update:model-value="surRattachements" />
      </Card>
    </div>

    <div class="flex flex-col gap-4">
      <TraceDeclarationIa v-model:conception="form.conceptionIa" v-model:realisation="form.realisationIa" v-model:detail="form.usageIaDetail" :modifiable="modifiable" />

      <Card bare tag="section" v-if="trace?.erreursDepot?.length && modifiable" class="p-4 flex flex-col gap-2">
        <h3 class="text-base font-semibold m-0">Avant de déposer</h3>
        <ul class="m-0 pl-5 text-sm flex flex-col gap-1">
          <li v-for="e in trace.erreursDepot" :key="e">{{ e }}</li>
        </ul>
      </Card>

      <div v-if="modifiable" class="flex flex-col gap-2">
        <Button label="Enregistrer" icon="pi pi-save" :loading="enregistrement" @click="enregistrer" />
        <Button
          v-if="!trace || ['brouillon', 'a_reviser'].includes(trace.statut.value)"
          label="Enregistrer et déposer"
          icon="pi pi-send"
          severity="success"
          outlined
          :loading="enregistrement"
          @click="deposer"
        />
      </div>
    </div>
  </div>
</template>
