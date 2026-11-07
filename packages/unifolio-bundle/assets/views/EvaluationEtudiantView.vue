<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import evaluationService from '../requests/evaluationService';
import { messageErreur } from '../requests/traceService';
import { pourcentage } from '../requests/autoEvaluationService';
import { couleurCompetence, texteSurCompetence } from '../components/traces/options';

const route = useRoute();
const router = useRouter();

const donnees = ref(null);
const chargement = ref(true);
const erreur = ref(null);

const charger = async (silencieux = false) => {
  if (silencieux !== true) chargement.value = true;
  try {
    const { data } = await evaluationService.etudiant(route.params.campagne, route.params.etudiant);
    donnees.value = data;
  } catch (e) {
    erreur.value = e?.response?.status === 403 ? 'Vous n\'êtes pas chargé(e) de cet étudiant.' : messageErreur(e, 'Impossible de charger le suivi.');
  } finally {
    chargement.value = false;
  }
};
onMounted(charger);

const groupes = computed(() => {
  const map = new Map();
  for (const el of donnees.value?.elements ?? []) {
    const cle = el.competence?.id ?? 0;
    if (!map.has(cle)) map.set(cle, { competence: el.competence, elements: [] });
    map.get(cle).elements.push(el);
  }
  return [...map.values()];
});

const mienne = (liste) => liste.find((e) => e.mienne) ?? null;
const autres = (liste) => liste.filter((e) => !e.mienne);
const ouvrirTrace = (t) => router.push(`/unifolio/evaluations/traces/${t.uuid}`);
const retour = `/unifolio/evaluations`;
const versBilans = () => router.push(`/unifolio/evaluations/bilans/${route.params.campagne}/${route.params.etudiant}`);
</script>

<template>
  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Suivi indisponible" :description="erreur" />

  <template v-else-if="donnees">
    <HeaderComponent
      icon="pi pi-user"
      :titre="`${donnees.etudiant.nom} ${donnees.etudiant.prenom}`"
      :description="[donnees.scolarite?.annee, donnees.campagne.libelle].filter(Boolean).join(' · ')"
      :show-back="true"
      :back-url="retour"
      color="emerald"
    >
      <template #actions>
        <Tag :value="donnees.campagne.phase.libelle" :severity="donnees.campagne.phase.badge" />
        <Button v-if="donnees.disponible" label="Évaluer tous les bilans" icon="pi pi-check-square" @click="versBilans" />
      </template>
    </HeaderComponent>

    <EmptyState v-if="!donnees.disponible" icon="pi pi-clock" color="amber" title="Évaluation indisponible" :description="donnees.message" />

    <div v-else class="flex flex-col gap-4">
      <div class="card p-4 text-sm text-muted-color">
        Évaluation par {{ donnees.granularite.libelle.toLowerCase() }} · {{ donnees.grille.libelle }} (v{{ donnees.grille.version }})
      </div>

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
          <div v-for="el in groupe.elements" :key="`${el.type}-${el.id}`" class="p-4 flex flex-col gap-3">
            <div class="flex flex-wrap items-start gap-2">
              <div class="flex-1 min-w-60 text-sm">
                <span
                  v-if="el.code"
                  class="text-xs font-semibold px-1.5 py-0.5 rounded mr-1"
                  :style="{ background: couleurCompetence(el.competence?.couleur, 0.15), color: couleurCompetence(el.competence?.couleur) }"
                >{{ el.code }}</span>
                <strong v-else-if="el.ordre">Niveau {{ el.ordre }} — </strong>
                {{ el.type === 'competence' ? 'Compétence au niveau attendu cette année' : el.libelle }}
              </div>
              <Tag
                v-if="el.synthese.score !== null"
                :value="`Synthèse ${pourcentage(el.synthese.score)} % · ${el.synthese.nbTraces} trace(s)`"
                severity="success"
                :title="`Règle : ${el.synthese.regle}`"
              />
            </div>

            <div v-if="el.traces.length" class="flex flex-col gap-2">
              <div v-for="t in el.traces" :key="t.rattachementId" class="flex flex-wrap items-center gap-2 text-sm">
                <button type="button" class="flex items-center gap-2 px-2 py-1 rounded-lg border border-surface-300 bg-transparent cursor-pointer hover:bg-surface-100" @click="ouvrirTrace(t)">
                  <i class="pi pi-images" /> {{ t.titre }}
                </button>
                <Tag :value="t.statut" severity="secondary" />
                <Tag v-if="t.autoEvaluation?.statut.value === 'soumise'" value="Auto-évaluée" severity="info" icon="pi pi-user" />
                <Tag
                  v-if="mienne(t.evaluations)"
                  :value="`Mon évaluation : ${mienne(t.evaluations).statut.libelle.toLowerCase()}${pourcentage(mienne(t.evaluations).score) !== null ? ` · ${pourcentage(mienne(t.evaluations).score)} %` : ''}`"
                  :severity="mienne(t.evaluations).statut.badge"
                />
                <Tag v-for="a in autres(t.evaluations)" :key="a.uuid" :value="`${a.evaluateur.nom} : ${pourcentage(a.score) ?? '—'} %`" severity="secondary" />
              </div>
            </div>
            <span v-else class="text-xs text-muted-color">Aucune trace rattachée.</span>

            <div class="flex flex-wrap items-center gap-2 text-sm">
              <span class="text-muted-color">Bilan :</span>
              <Tag v-if="el.bilan" :value="`Auto-évaluation ${el.bilan.statut.libelle.toLowerCase()}`" :severity="el.bilan.statut.badge" />
              <span v-else class="text-muted-color">pas d'auto-évaluation</span>
              <Tag v-if="mienne(el.evaluationsBilan)" :value="`Mon évaluation : ${mienne(el.evaluationsBilan).statut.libelle.toLowerCase()}`" :severity="mienne(el.evaluationsBilan).statut.badge" />
              <Tag v-else value="Mon évaluation : non faite" severity="secondary" />
            </div>
          </div>
        </div>
      </section>
    </div>
  </template>
</template>
