<script setup>
import { formatDateFr } from '@helpers/date';
import { useRouter } from 'vue-router';
import { HeaderComponent, EmptyState, Card, LoadingBar } from '@components';
import Tag from 'primevue/tag';
import evaluationService from '../requests/evaluationService';
import { useChargement } from '../composables/useChargement';

const router = useRouter();
const { donnees: file, chargement, erreur } = useChargement(() => evaluationService.file(), { initial: [], messageParDefaut: 'Impossible de charger votre file d\'évaluation.' });

const date = (iso) => formatDateFr(iso);
const ouvrir = (groupe, etudiant) => router.push(`/unifolio/evaluations/etudiants/${groupe.campagne.uuid}/${etudiant.id}`);
const aFaire = (e) => e.aEvaluer + e.enCours + e.aReprendre;
</script>

<template>
  <HeaderComponent
    icon="pi pi-check-square"
    titre="À évaluer"
    description="Les étudiants dont vous êtes chargé(e), par campagne, avec ce qu'il reste à évaluer."
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  />

  <LoadingBar v-if="chargement" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Erreur" :description="erreur" />
  <EmptyState
    v-else-if="file.length === 0"
    icon="pi pi-inbox"
    color="emerald"
    title="Aucun étudiant à évaluer"
    description="Vous n'êtes affecté(e) à aucune campagne. Un référent portfolio peut vous affecter des étudiants."
  />

  <div v-else class="flex flex-col gap-4">
    <Card bare tag="section" v-for="g in file" :key="g.campagne.uuid" class="p-0 overflow-hidden" :class="{ 'opacity-70': g.campagne.phase.value === 'cloturee' }">
      <header class="p-4 flex flex-wrap items-center gap-3 border-b border-surface-200">
        <div class="flex-1 min-w-60">
          <div class="font-semibold">{{ g.campagne.libelle }}</div>
          <div class="text-sm text-muted-color">
            {{ [g.campagne.diplome.parcours ?? g.campagne.diplome.libelle, g.campagne.anneeFormation?.libelle, g.campagne.anneeUniversitaire.libelle].filter(Boolean).join(' · ') }}
          </div>
        </div>
        <Tag :value="g.campagne.phase.libelle" :severity="g.campagne.phase.badge" />
        <Tag v-if="g.campagne.enRetard" value="Limite d'évaluation dépassée" severity="danger" icon="pi pi-clock" />
        <span v-else-if="g.campagne.evaluationLimite" class="text-sm text-muted-color">Évaluation avant le {{ date(g.campagne.evaluationLimite) }}</span>
        <Tag v-if="g.campagne.autoEvaluationRequise" value="Auto-évaluation exigée" severity="info" icon="pi pi-user" />
      </header>

      <table class="w-full text-left border-collapse text-sm">
        <thead>
          <tr class="text-muted-color text-xs border-b border-surface-200">
            <th class="font-medium p-3">Étudiant</th>
            <th class="font-medium p-3 text-center">Traces</th>
            <th class="font-medium p-3 text-center">À évaluer</th>
            <th class="font-medium p-3 text-center">En cours</th>
            <th class="font-medium p-3 text-center">À reprendre</th>
            <th class="font-medium p-3 text-center">En attente de l'étudiant</th>
            <th class="font-medium p-3 text-center">Rendues</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="e in g.etudiants"
            :key="e.id"
            class="border-b border-surface-100 hover:bg-surface-50 cursor-pointer"
            tabindex="0"
            @click="ouvrir(g, e)"
            @keydown.enter="ouvrir(g, e)"
          >
            <td class="p-3 font-medium">{{ e.nom }} {{ e.prenom }}</td>
            <td class="p-3 text-center">{{ e.nbTraces }}</td>
            <td class="p-3 text-center"><Tag v-if="e.aEvaluer" :value="e.aEvaluer" severity="warn" /><span v-else class="text-muted-color">0</span></td>
            <td class="p-3 text-center"><Tag v-if="e.enCours" :value="e.enCours" severity="info" /><span v-else class="text-muted-color">0</span></td>
            <td class="p-3 text-center"><Tag v-if="e.aReprendre" :value="e.aReprendre" severity="danger" /><span v-else class="text-muted-color">0</span></td>
            <td class="p-3 text-center">
              <span v-if="e.enAttente + e.attenteAutoEvaluation" :title="e.attenteAutoEvaluation ? `${e.attenteAutoEvaluation} en attente d'auto-évaluation` : ''">
                {{ e.enAttente + e.attenteAutoEvaluation }}
              </span>
              <span v-else class="text-muted-color">0</span>
            </td>
            <td class="p-3 text-center"><span :class="e.rendues ? 'text-emerald-700 font-medium' : 'text-muted-color'">{{ e.rendues }}</span></td>
          </tr>
        </tbody>
        <tfoot v-if="aFaire(g.totaux) > 0">
          <tr><td colspan="7" class="p-3 text-right text-sm font-medium">{{ aFaire(g.totaux) }} élément(s) restent à traiter</td></tr>
        </tfoot>
      </table>
    </Card>
  </div>
</template>
