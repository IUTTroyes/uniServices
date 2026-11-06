<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { HeaderComponent } from '@components';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Message from 'primevue/message';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import MediaManager from '../components/traces/MediaManager.vue';
import RattachementPicker from '../components/traces/RattachementPicker.vue';
import traceService, { messageErreur } from '../requests/traceService';
import { useTraceReferenceStore } from '../stores/traceReferenceStore';
import {
  TYPES_ACTIVITE, CONCEPTION_IA, REALISATION_IA, CONCEPTION_IA_NON_RENSEIGNE, REALISATION_IA_NON_RENSEIGNE, titreDepuisNomFichier,
} from '../components/traces/options';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const references = useTraceReferenceStore();

const trace = ref(null);
// Sur « nouvelle », la trace peut avoir été créée à la volée par le dépôt d'une preuve.
const uuid = computed(() => route.params.uuid ?? trace.value?.uuid ?? null);
const chargement = ref(false);
const enregistrement = ref(false);
const competences = ref([]);
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
const libelleScolarite = (s) => [s.annee, s.anneeUniversitaire].filter(Boolean).join(' — ');

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
    toast.add({ severity: 'error', summary: 'Chargement impossible', detail: messageErreur(e), life: 6000 });
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
    toast.add({ severity: 'warn', summary: 'Titre manquant', detail: 'Donnez un titre à votre trace.', life: 4000 });
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
    toast.add({ severity: 'success', summary: 'Trace enregistrée', life: 3000 });
    if (!route.params.uuid) {
      await router.replace(`/unifolio/traces/${data.uuid}/modifier`);
    }
    return true;
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Enregistrement impossible', detail: messageErreur(e), life: 7000 });
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
    toast.add({ severity: 'success', summary: 'Trace déposée', detail: 'Elle pourra être évaluée par vos enseignants.', life: 4000 });
    router.push(`/unifolio/traces/${data.uuid}`);
  } catch (e) {
    toast.add({ severity: 'warn', summary: 'Dépôt impossible', detail: messageErreur(e), life: 8000 });
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

  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />

  <Message v-else-if="trace && !modifiable" severity="info" class="mb-4">
    Cette trace est en cours d'évaluation ou évaluée : elle n'est plus modifiable.
  </Message>

  <div v-if="!chargement" class="grid gap-4 xl:grid-cols-3">
    <section class="card p-4 flex flex-col gap-3 xl:col-span-3">
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
    </section>

    <div class="xl:col-span-2 flex flex-col gap-4">
      <section class="card p-4 flex flex-col gap-4">
        <h3 class="text-base font-semibold m-0">Description</h3>
        <div class="flex flex-col gap-2">
          <label for="trace-titre" class="font-medium">Titre *</label>
          <InputText id="trace-titre" v-model="form.titre" maxlength="255" :disabled="!modifiable" />
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
          <div class="flex flex-col gap-2">
            <label for="trace-annee" class="font-medium">Année de BUT</label>
            <span v-if="references.scolarites.length === 1 && form.scolarite" id="trace-annee" class="py-2 font-medium">
              {{ libelleScolarite(references.scolarites[0]) }}
            </span>
            <Select
              v-else
              id="trace-annee"
              v-model="form.scolarite"
              :options="references.scolarites"
              :option-label="libelleScolarite"
              option-value="id"
              :disabled="!modifiable || changementAnneeBloque"
              placeholder="Choisir"
            />
            <small v-if="changementAnneeBloque" class="text-muted-color">Retirez les rattachements pour changer d'année.</small>
          </div>
          <div class="flex flex-col gap-2">
            <label for="trace-date" class="font-medium">Date de réalisation</label>
            <DatePicker id="trace-date" v-model="form.dateRealisation" date-format="dd/mm/yy" show-icon :disabled="!modifiable" />
          </div>
          <div class="flex flex-col gap-2">
            <label for="trace-type" class="font-medium">Type d'activité</label>
            <Select id="trace-type" v-model="form.typeActivite" :options="TYPES_ACTIVITE" option-label="label" option-value="value" show-clear placeholder="Choisir" :disabled="!modifiable" />
          </div>
        </div>
        <div class="flex flex-col gap-2">
          <label for="trace-contexte" class="font-medium">Contexte et démarche</label>
          <Textarea id="trace-contexte" v-model="form.contexte" rows="6" auto-resize :disabled="!modifiable" placeholder="Situation, objectifs, votre rôle, ce que vous avez appris…" />
        </div>
      </section>

      <section class="card p-4 flex flex-col gap-3">
        <h3 class="text-base font-semibold m-0">Rattachement au référentiel</h3>
        <p class="text-sm text-muted-color m-0">
          Compétences de votre année{{ anneeAffichee ? ` (${anneeAffichee})` : '' }} : cochez ce que cette trace démontre et expliquez pourquoi.
        </p>
        <Message v-if="!form.scolarite" severity="warn">Choisissez l'année de BUT de la trace pour afficher son référentiel.</Message>
        <RattachementPicker v-else :competences="competences" :model-value="rattachements" :disabled="!modifiable" @update:model-value="surRattachements" />
      </section>
    </div>

    <div class="flex flex-col gap-4">
      <section class="card p-4 flex flex-col gap-4">
        <h3 class="text-base font-semibold m-0">Usage de l'IA</h3>
        <p class="text-sm text-muted-color m-0">Déclaration obligatoire pour déposer la trace.</p>
        <div class="flex flex-col gap-2">
          <label for="trace-conception-ia" class="font-medium">Dans la conception</label>
          <Select id="trace-conception-ia" v-model="form.conceptionIa" :options="CONCEPTION_IA" option-label="label" option-value="value" placeholder="À renseigner" :disabled="!modifiable" />
        </div>
        <div class="flex flex-col gap-2">
          <label for="trace-realisation-ia" class="font-medium">Dans la réalisation</label>
          <Select id="trace-realisation-ia" v-model="form.realisationIa" :options="REALISATION_IA" option-label="label" option-value="value" placeholder="À renseigner" :disabled="!modifiable" />
        </div>
        <div class="flex flex-col gap-2">
          <label for="trace-ia-detail" class="font-medium">Précisions</label>
          <Textarea id="trace-ia-detail" v-model="form.usageIaDetail" rows="3" auto-resize placeholder="Outils utilisés et pour quoi faire" :disabled="!modifiable" />
        </div>
      </section>

      <section v-if="trace?.erreursDepot?.length && modifiable" class="card p-4 flex flex-col gap-2">
        <h3 class="text-base font-semibold m-0">Avant de déposer</h3>
        <ul class="m-0 pl-5 text-sm flex flex-col gap-1">
          <li v-for="e in trace.erreursDepot" :key="e">{{ e }}</li>
        </ul>
      </section>

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
