<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import traceService, { messageErreur, urlFichier } from '../requests/traceService';
import { useTraceReferenceStore } from '../stores/traceReferenceStore';
import { STATUTS, couleurCompetence, texteSurCompetence } from '../components/traces/options';

const router = useRouter();
const references = useTraceReferenceStore();

const traces = ref([]);
const competences = ref([]);
const chargement = ref(false);
const erreur = ref(null);
const filtres = reactive({ scolarite: null, statut: null, competence: null, q: '' });

const charger = async () => {
  chargement.value = true;
  erreur.value = null;
  try {
    const { data } = await traceService.lister(filtres);
    traces.value = data;
  } catch (e) {
    erreur.value = messageErreur(e, 'Impossible de charger vos traces.');
  } finally {
    chargement.value = false;
  }
};

const chargerCompetences = async () => {
  const id = filtres.scolarite ?? references.scolariteActive?.id;
  competences.value = id ? await references.referentiel(id) : [];
};

let minuterie = null;
watch(() => filtres.q, () => {
  clearTimeout(minuterie);
  minuterie = setTimeout(charger, 300);
});
watch(() => [filtres.scolarite, filtres.statut, filtres.competence], charger);
watch(() => filtres.scolarite, () => {
  filtres.competence = null;
  chargerCompetences();
});

onMounted(async () => {
  try {
    await references.chargerScolarites();
    await chargerCompetences();
  } catch {
    // Les filtres restent vides ; la liste s'affiche quand même.
  }
  await charger();
});

const libelleScolarite = (s) => [s.annee, s.anneeUniversitaire].filter(Boolean).join(' — ');
const formatDate = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) : null);
</script>

<template>
  <HeaderComponent
    icon="pi pi-images"
    titre="Mes traces"
    description="Votre bibliothèque de preuves, rattachées au référentiel de compétences du BUT."
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  >
    <template #actions>
      <Button label="Nouvelle trace" icon="pi pi-plus" @click="router.push('/unifolio/traces/nouvelle')" />
    </template>
  </HeaderComponent>

  <div class="card p-4 flex flex-col gap-4">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <InputText v-model="filtres.q" placeholder="Rechercher…" aria-label="Rechercher une trace" />
      <Select
        v-model="filtres.scolarite"
        :options="references.scolarites"
        :option-label="libelleScolarite"
        option-value="id"
        placeholder="Toutes les années"
        show-clear
        aria-label="Filtrer par année"
      />
      <Select
        v-model="filtres.competence"
        :options="competences"
        :option-label="(c) => c.nomCourt || c.libelle"
        option-value="id"
        placeholder="Toutes les compétences"
        show-clear
        aria-label="Filtrer par compétence"
      />
      <Select v-model="filtres.statut" :options="STATUTS" option-label="label" option-value="value" placeholder="Tous les statuts" show-clear aria-label="Filtrer par statut" />
    </div>

    <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />

    <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Erreur" :description="erreur" />

    <EmptyState
      v-else-if="traces.length === 0"
      icon="pi pi-images"
      color="emerald"
      title="Aucune trace"
      description="Déposez une première preuve de vos apprentissages : production de SAÉ, rapport, maquette, code…"
      action-label="Créer une trace"
      action-icon="pi pi-plus"
      @action="router.push('/unifolio/traces/nouvelle')"
    />

    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <button
        v-for="trace in traces"
        :key="trace.uuid"
        type="button"
        class="text-left border border-surface-200 rounded-xl overflow-hidden flex flex-col hover:shadow-md transition-shadow cursor-pointer bg-transparent p-0"
        @click="router.push(`/unifolio/traces/${trace.uuid}`)"
      >
        <div class="h-36 bg-surface-100 flex items-center justify-center overflow-hidden">
          <img v-if="trace.vignette" :src="urlFichier(trace.vignette)" alt="" class="w-full h-full object-cover" loading="lazy" />
          <i v-else class="pi pi-images text-4xl text-muted-color" />
        </div>
        <div class="p-4 flex flex-col gap-2 flex-1">
          <div class="flex items-start justify-between gap-2">
            <span class="font-semibold">{{ trace.titre }}</span>
            <Tag :value="trace.statut.libelle" :severity="trace.statut.badge" />
          </div>
          <span class="text-sm text-muted-color">
            {{ [trace.scolarite?.annee, formatDate(trace.dateRealisation), trace.typeActivite?.libelle].filter(Boolean).join(' · ') }}
          </span>
          <div class="flex flex-wrap gap-1 mt-auto">
            <span
              v-for="c in trace.competences"
              :key="c.id"
              class="text-xs font-medium px-2 py-0.5 rounded-full"
              :style="{ background: couleurCompetence(c.couleur), color: texteSurCompetence(c.couleur) }"
            >{{ c.nomCourt }}</span>
            <span v-if="trace.competences.length === 0" class="text-xs text-muted-color">Non rattachée</span>
          </div>
          <span class="text-xs text-muted-color">{{ trace.nbMedias }} preuve(s)</span>
        </div>
      </button>
    </div>
  </div>
</template>
