<script setup>
import { formatDateFr } from '@helpers/date';
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { HeaderComponent, EmptyState, Card, LoadingBar } from '@components';
import Tag from 'primevue/tag';
import MediaPreview from '../components/traces/MediaPreview.vue';
import EvaluationLot from '../components/evaluation/EvaluationLot.vue';
import CommentairesThread from '../components/commentaires/CommentairesThread.vue';
import evaluationService from '../requests/evaluationService';
import { messageErreur } from '../requests/traceService';

const route = useRoute();

const donnees = ref(null);
const chargement = ref(true);
const erreur = ref(null);

const charger = async (silencieux = false) => {
  if (silencieux !== true) chargement.value = true;
  try {
    const { data } = await evaluationService.trace(route.params.uuid);
    donnees.value = data;
  } catch (e) {
    erreur.value = e?.response?.status === 403 ? 'Vous n\'avez pas accès à cette trace.' : messageErreur(e, 'Impossible de charger la trace.');
  } finally {
    chargement.value = false;
  }
};
onMounted(charger);

const trace = computed(() => donnees.value?.trace);
const portee = computed(() => ({ type: 'trace', uuid: route.params.uuid }));
const retour = computed(() => (donnees.value?.campagne ? `/unifolio/evaluations/etudiants/${donnees.value.campagne.uuid}/${donnees.value.etudiant.id}` : '/unifolio/evaluations'));
const declarationIa = computed(() => (trace.value ? { conception: trace.value.conceptionIa.libelle, realisation: trace.value.realisationIa.libelle, detail: trace.value.usageIaDetail } : null));
const formatDate = (iso) => formatDateFr(iso, 'longue');
</script>

<template>
  <LoadingBar v-if="chargement" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Trace indisponible" :description="erreur" />

  <template v-else-if="trace">
    <HeaderComponent
      icon="pi pi-images"
      :titre="trace.titre"
      :description="[donnees.etudiant.nom, trace.scolarite?.annee, trace.typeActivite?.libelle, formatDate(trace.dateRealisation)].filter(Boolean).join(' · ')"
      :show-back="true"
      :back-url="retour"
      color="emerald"
    >
      <template #actions>
        <Tag :value="trace.statut.libelle" :severity="trace.statut.badge" />
      </template>
    </HeaderComponent>

    <div class="grid gap-4 xl:grid-cols-12">
      <!-- La trace, à consulter pendant l'évaluation -->
      <div class="xl:col-span-5 flex flex-col gap-4">
        <Card bare tag="section" v-if="trace.contexte" class="p-4">
          <h3 class="text-base font-semibold mt-0">Contexte et démarche</h3>
          <p class="whitespace-pre-line m-0">{{ trace.contexte }}</p>
        </Card>

        <Card bare tag="section" class="p-4 flex flex-col gap-6">
          <h3 class="text-base font-semibold m-0">Preuves</h3>
          <p v-if="trace.medias.length === 0" class="text-muted-color m-0">Aucune preuve.</p>
          <MediaPreview v-for="media in trace.medias" :key="media.id" :media="media" />
        </Card>

        <Card bare tag="section" class="p-4 flex flex-col gap-2 text-sm">
          <h3 class="text-base font-semibold m-0">Usage de l'IA déclaré</h3>
          <span><strong>Conception :</strong> {{ trace.conceptionIa.libelle }}</span>
          <span><strong>Réalisation :</strong> {{ trace.realisationIa.libelle }}</span>
          <span v-if="trace.usageIaDetail" class="text-muted-color whitespace-pre-line">{{ trace.usageIaDetail }}</span>
        </Card>

        <Card bare tag="section" class="p-4">
          <CommentairesThread :trace-uuid="trace.uuid" :rattachements="trace.rattachements" />
        </Card>
      </div>

      <!-- Tous les points à évaluer, sur la même page -->
      <div class="xl:col-span-7">
        <p v-if="trace.rattachements.length === 0" class="text-muted-color m-0">Cette trace n'est rattachée à aucun élément du référentiel : il n'y a rien à évaluer.</p>
        <EvaluationLot v-else :portee="portee" :declaration-ia="declarationIa" @change="charger(true)" />
      </div>
    </div>
  </template>
</template>
