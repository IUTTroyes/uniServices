<script setup>
import { showDanger, showSuccess, showWarn } from '@helpers/toast';
import { formatDateFr } from '@helpers/date';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useConfirmation } from '@composables/useConfirmation';
import { HeaderComponent, EmptyState, Card, Alert, LoadingBar } from '@components';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import MediaPreview from '../components/traces/MediaPreview.vue';
import AutoEvaluationPanel from '../components/autoeval/AutoEvaluationPanel.vue';
import RetourEnseignant from '../components/evaluation/RetourEnseignant.vue';
import CommentairesThread from '../components/commentaires/CommentairesThread.vue';
import { pourcentage } from '../requests/autoEvaluationService';
import traceService, { messageErreur } from '../requests/traceService';
import { couleurCompetence, texteSurCompetence } from '../components/traces/options';

const route = useRoute();
const router = useRouter();
const { confirmer } = useConfirmation();

const trace = ref(null);
const chargement = ref(true);
const erreur = ref(null);

/** silencieux : rafraîchit sans masquer la page (ex. panneau d'auto-évaluation ouvert). */
const charger = async (silencieux = false) => {
  if (silencieux !== true) chargement.value = true;
  try {
    const { data } = await traceService.lire(route.params.uuid);
    trace.value = data;
  } catch (e) {
    erreur.value = e?.response?.status === 404 ? 'Cette trace n\'existe pas ou plus.' : messageErreur(e);
  } finally {
    chargement.value = false;
  }
};
onMounted(charger);

const rattachementsParCompetence = computed(() => {
  const groupes = new Map();
  for (const r of trace.value?.rattachements ?? []) {
    const cle = r.competence?.id ?? 0;
    if (!groupes.has(cle)) groupes.set(cle, { competence: r.competence, items: [] });
    groupes.get(cle).items.push(r);
  }
  return [...groupes.values()];
});

const libelleType = { competence: 'Compétence', niveau: 'Niveau', ac: 'AC' };

/** Dernière demande de révision tant que la trace est à réviser. */
const revisionDemandee = computed(() => {
  if (trace.value?.statut.value !== 'a_reviser') return null;
  const retours = trace.value.rattachements.flatMap((r) => r.retours ?? []).filter((x) => x.statut.value === 'revision_demandee');
  return retours.sort((a, b) => String(b.rendueLe).localeCompare(String(a.rendueLe)))[0] ?? null;
});

const panneau = ref(false);
const cibleAuto = ref(null);
const autoEvaluer = (r) => {
  cibleAuto.value = { rattachement: r.id };
  panneau.value = true;
};
const declarationIa = computed(() => (trace.value ? {
  conception: trace.value.conceptionIa.libelle,
  realisation: trace.value.realisationIa.libelle,
  detail: trace.value.usageIaDetail,
} : null));

const action = async (fn, succes) => {
  try {
    const { data } = await fn();
    trace.value = data;
    showSuccess(undefined, succes, 3000);
  } catch (e) {
    showWarn(messageErreur(e), 'Action impossible', 7000);
  }
};

const supprimer = () => {
  confirmer({
    header: 'Supprimer la trace',
    message: 'Supprimer définitivement cette trace et toutes ses preuves ?',
    icon: 'pi pi-trash',
    libelle: 'Supprimer',
    danger: true,
    accept: async () => {
      try {
        await traceService.supprimer(trace.value.uuid);
        showSuccess(undefined, 'Trace supprimée', 3000);
        router.push('/unifolio/traces');
      } catch (e) {
        showDanger(messageErreur(e), 'Suppression impossible', 6000);
      }
    },
  });
};

const formatDate = (iso) => formatDateFr(iso, 'longue');
</script>

<template>
  <LoadingBar v-if="chargement" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Trace indisponible" :description="erreur" />

  <template v-else-if="trace">
    <HeaderComponent
      icon="pi pi-images"
      :titre="trace.titre"
      :description="[trace.scolarite?.annee, trace.scolarite?.anneeUniversitaire, trace.typeActivite?.libelle, formatDate(trace.dateRealisation)].filter(Boolean).join(' · ')"
      :show-back="true"
      back-url="/unifolio/traces"
      color="emerald"
    >
      <template #actions>
        <Tag :value="trace.statut.libelle" :severity="trace.statut.badge" />
        <Button v-if="trace.modifiable" label="Modifier" icon="pi pi-pencil" @click="router.push(`/unifolio/traces/${trace.uuid}/modifier`)" />
        <Button
          v-if="['brouillon', 'a_reviser'].includes(trace.statut.value)"
          label="Déposer"
          icon="pi pi-send"
          severity="success"
          outlined
          @click="action(() => traceService.deposer(trace.uuid), 'Trace déposée')"
        />
        <Button
          v-if="trace.statut.value === 'deposee'"
          label="Repasser en brouillon"
          icon="pi pi-undo"
          severity="secondary"
          outlined
          @click="action(() => traceService.remettreEnBrouillon(trace.uuid), 'Trace repassée en brouillon')"
        />
        <Button v-if="trace.statut.value === 'brouillon'" icon="pi pi-trash" severity="danger" text aria-label="Supprimer la trace" @click="supprimer" />
      </template>
    </HeaderComponent>

    <div class="grid gap-4 xl:grid-cols-3">
      <div class="xl:col-span-2 flex flex-col gap-4">
        <Alert icon-native v-if="revisionDemandee" severity="warn" :closable="false">
          <div class="flex flex-col gap-1">
            <strong>{{ revisionDemandee.evaluateur.nom }} vous demande de réviser cette trace.</strong>
            <span class="whitespace-pre-line">{{ revisionDemandee.appreciation }}</span>
            <span class="text-sm">Modifiez la trace puis déposez-la à nouveau.</span>
          </div>
        </Alert>

        <Card bare tag="section" v-if="trace.contexte" class="p-4">
          <h3 class="text-base font-semibold mt-0">Contexte et démarche</h3>
          <p class="whitespace-pre-line m-0">{{ trace.contexte }}</p>
        </Card>

        <Card bare tag="section" class="p-4 flex flex-col gap-6">
          <h3 class="text-base font-semibold m-0">Preuves</h3>
          <p v-if="trace.medias.length === 0" class="text-muted-color m-0">Aucune preuve.</p>
          <MediaPreview v-for="media in trace.medias" :key="media.id" :media="media" />
        </Card>

        <Card bare tag="section" v-if="trace.statut.value !== 'brouillon'" class="p-4">
          <CommentairesThread :trace-uuid="trace.uuid" :rattachements="trace.rattachements" />
        </Card>
      </div>

      <div class="flex flex-col gap-4">
        <Card bare tag="section" class="p-4 flex flex-col gap-3">
          <h3 class="text-base font-semibold m-0">Compétences démontrées</h3>
          <p v-if="trace.rattachements.length === 0" class="text-muted-color m-0 text-sm">Pas encore rattachée au référentiel.</p>
          <div
            v-for="groupe in rattachementsParCompetence"
            :key="groupe.competence?.id ?? 0"
            class="flex flex-col gap-2 rounded-lg p-3 border-l-[6px]"
            :style="{ borderLeftColor: couleurCompetence(groupe.competence?.couleur), background: couleurCompetence(groupe.competence?.couleur, 0.08) }"
          >
            <span
              class="self-start text-xs font-semibold px-2 py-1 rounded"
              :style="{ background: couleurCompetence(groupe.competence?.couleur), color: texteSurCompetence(groupe.competence?.couleur) }"
            >{{ groupe.competence?.nomCourt ?? 'Compétence' }}</span>
            <div v-for="r in groupe.items" :key="r.id" class="flex flex-col gap-1">
              <span class="text-sm">
                <span
                  class="text-xs font-semibold px-1.5 py-0.5 rounded mr-1"
                  :style="{ background: couleurCompetence(groupe.competence?.couleur, 0.15), color: couleurCompetence(groupe.competence?.couleur) }"
                >{{ r.code || libelleType[r.type] }}</span>{{ r.libelle }}
              </span>
              <span v-if="r.argumentaire" class="text-sm text-muted-color whitespace-pre-line">{{ r.argumentaire }}</span>
              <div class="flex items-center gap-2">
                <Button
                  :label="r.autoEvaluation ? 'Mon auto-évaluation' : 'M\'auto-évaluer'"
                  icon="pi pi-chart-line"
                  size="small"
                  text
                  class="!px-0"
                  @click="autoEvaluer(r)"
                />
                <Tag v-if="r.autoEvaluation" :value="r.autoEvaluation.statut.libelle" :severity="r.autoEvaluation.statut.badge" />
                <span v-if="pourcentage(r.autoEvaluation?.score) !== null" class="text-xs text-muted-color">{{ pourcentage(r.autoEvaluation.score) }} %</span>
              </div>
              <RetourEnseignant v-for="retour in r.retours" :key="retour.uuid" :retour="retour" />
            </div>
          </div>
        </Card>

        <Card bare tag="section" class="p-4 flex flex-col gap-2 text-sm">
          <h3 class="text-base font-semibold m-0">Usage de l'IA</h3>
          <span><strong>Conception :</strong> {{ trace.conceptionIa.libelle }}</span>
          <span><strong>Réalisation :</strong> {{ trace.realisationIa.libelle }}</span>
          <span v-if="trace.usageIaDetail" class="text-muted-color whitespace-pre-line">{{ trace.usageIaDetail }}</span>
        </Card>
      </div>
    </div>

    <AutoEvaluationPanel v-model:visible="panneau" :cible="cibleAuto" :declaration-ia="declarationIa" @change="charger(true)" />
  </template>
</template>
