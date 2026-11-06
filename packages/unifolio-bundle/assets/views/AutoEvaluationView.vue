<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import ProgressBar from 'primevue/progressbar';
import AutoEvaluationPanel from '../components/autoeval/AutoEvaluationPanel.vue';
import autoEvaluationService, { pourcentage } from '../requests/autoEvaluationService';
import { messageErreur } from '../requests/traceService';
import { useTraceReferenceStore } from '../stores/traceReferenceStore';
import { couleurCompetence, texteSurCompetence } from '../components/traces/options';

const router = useRouter();
const references = useTraceReferenceStore();

const scolarite = ref(null);
const donnees = ref(null);
const chargement = ref(true);
const erreur = ref(null);
const panneau = ref(false);
const cible = ref(null);

const charger = async (silencieux = false) => {
  if (silencieux !== true) chargement.value = true;
  erreur.value = null;
  try {
    const { data } = await autoEvaluationService.bilan(scolarite.value);
    donnees.value = data;
  } catch (e) {
    erreur.value = messageErreur(e, 'Impossible de charger votre bilan.');
  } finally {
    chargement.value = false;
  }
};

onMounted(async () => {
  try {
    await references.chargerScolarites();
    scolarite.value = references.scolariteActive?.id ?? null;
  } catch {
    // L'API retombera sur l'année active.
  }
  await charger();
});
watch(scolarite, (n, a) => { if (a !== null) charger(); });

const groupes = computed(() => {
  const map = new Map();
  for (const el of donnees.value?.elements ?? []) {
    const cle = el.competence?.id ?? 0;
    if (!map.has(cle)) map.set(cle, { competence: el.competence, elements: [] });
    map.get(cle).elements.push(el);
  }
  return [...map.values()];
});

const avancement = computed(() => {
  const els = donnees.value?.elements ?? [];
  return { total: els.length, soumis: els.filter((e) => e.bilan?.statut.value === 'soumise').length };
});

const ouvrirBilan = (el) => {
  cible.value = { scolarite: donnees.value.scolarite.id, type: el.type, id: el.id };
  panneau.value = true;
};
const ouvrirTrace = (t) => {
  cible.value = { rattachement: t.rattachementId };
  panneau.value = true;
};

const libelleScolarite = (s) => [s.annee, s.anneeUniversitaire].filter(Boolean).join(' — ');
</script>

<template>
  <HeaderComponent
    icon="pi pi-chart-line"
    titre="Auto-évaluation"
    description="Positionnez-vous sur les compétences de votre année, à partir de vos traces."
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  >
    <template #actions>
      <Select
        v-if="references.scolarites.length > 1"
        v-model="scolarite"
        :options="references.scolarites"
        :option-label="libelleScolarite"
        option-value="id"
        aria-label="Année"
      />
    </template>
  </HeaderComponent>

  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Erreur" :description="erreur" />
  <EmptyState v-else-if="donnees && !donnees.disponible" icon="pi pi-clock" color="amber" title="Pas encore disponible" :description="donnees.message" />

  <div v-else-if="donnees" class="flex flex-col gap-4">
    <div class="card p-4 flex flex-wrap items-center gap-4">
      <div class="flex-1 min-w-60">
        <div class="text-sm text-muted-color">
          {{ donnees.scolarite?.annee }} · bilan par {{ donnees.granularite.libelle.toLowerCase() }} · {{ donnees.grille.libelle }}
        </div>
        <div class="font-medium">{{ avancement.soumis }} / {{ avancement.total }} bilan(s) soumis</div>
      </div>
      <ProgressBar :value="avancement.total ? Math.round((avancement.soumis * 100) / avancement.total) : 0" class="w-60" />
    </div>

    <Message v-if="avancement.total === 0" severity="info">Aucun élément du référentiel n'est défini pour votre année.</Message>

    <section
      v-for="groupe in groupes"
      :key="groupe.competence?.id ?? 0"
      class="card p-0 overflow-hidden border-l-[6px]"
      :style="{ borderLeftColor: couleurCompetence(groupe.competence?.couleur) }"
    >
      <header class="p-4 flex items-center gap-3" :style="{ background: couleurCompetence(groupe.competence?.couleur, 0.1) }">
        <span
          class="text-xs font-semibold px-2 py-1 rounded"
          :style="{ background: couleurCompetence(groupe.competence?.couleur), color: texteSurCompetence(groupe.competence?.couleur) }"
        >{{ groupe.competence?.nomCourt ?? 'Compétence' }}</span>
        <span class="font-medium text-sm">{{ groupe.competence?.libelle }}</span>
      </header>

      <div class="divide-y divide-surface-200">
        <div v-for="el in groupe.elements" :key="`${el.type}-${el.id}`" class="p-4 flex flex-col gap-3 md:flex-row md:items-start">
          <div class="flex-1 flex flex-col gap-2">
            <div class="text-sm">
              <span
                v-if="el.code"
                class="text-xs font-semibold px-1.5 py-0.5 rounded mr-1"
                :style="{ background: couleurCompetence(el.competence?.couleur, 0.15), color: couleurCompetence(el.competence?.couleur) }"
              >{{ el.code }}</span>
              <strong v-else-if="el.ordre">Niveau {{ el.ordre }} — </strong>
              {{ el.type === 'competence' ? 'Compétence au niveau attendu cette année' : el.libelle }}
            </div>

            <div v-if="el.traces.length" class="flex flex-wrap gap-2">
              <button
                v-for="t in el.traces"
                :key="t.rattachementId"
                type="button"
                class="text-xs flex items-center gap-2 px-2 py-1 rounded-full border border-surface-300 bg-transparent cursor-pointer hover:bg-surface-100"
                :title="t.autoEvaluation ? `Auto-évaluation : ${t.autoEvaluation.statut.libelle}` : 'M\'auto-évaluer sur cette trace'"
                @click="ouvrirTrace(t)"
              >
                <i class="pi pi-images" /> {{ t.titre }}
                <i v-if="t.autoEvaluation?.statut.value === 'soumise'" class="pi pi-check-circle text-green-600" />
                <i v-else-if="t.autoEvaluation" class="pi pi-pencil text-amber-600" />
              </button>
            </div>
            <span v-else class="text-xs text-muted-color">
              Aucune trace rattachée.
              <a class="text-primary cursor-pointer" @click="router.push('/unifolio/traces/nouvelle')">Déposer une trace</a>
            </span>
          </div>

          <div class="flex items-center gap-2 md:w-64 md:justify-end">
            <template v-if="el.bilan">
              <Tag :value="el.bilan.statut.libelle" :severity="el.bilan.statut.badge" />
              <span v-if="pourcentage(el.bilan.score) !== null" class="text-sm text-muted-color">{{ pourcentage(el.bilan.score) }} %</span>
            </template>
            <Button
              :label="el.bilan ? 'Mon bilan' : 'Faire mon bilan'"
              :icon="el.bilan ? 'pi pi-pencil' : 'pi pi-plus'"
              size="small"
              :outlined="!!el.bilan"
              @click="ouvrirBilan(el)"
            />
          </div>
        </div>
      </div>
    </section>
  </div>

  <AutoEvaluationPanel v-model:visible="panneau" :cible="cible" @change="charger(true)" />
</template>
