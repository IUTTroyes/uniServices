<script setup>
import { formatDateFr } from '@helpers/date';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent, EmptyState, Card, LoadingBar } from '@components';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { suiviService, pourcent } from '../requests/suiviService';
import { useChargement } from '../composables/useChargement';
import { couleurCompetence } from '../components/traces/options';

const router = useRouter();
const ouverts = ref({});
const { donnees, chargement, erreur } = useChargement(() => suiviService.progression(), {
  messageParDefaut: 'Impossible de charger votre progression.',
  apres: (data) => {
    donnees.value = data;
    // L'année en cours (dernière) est dépliée par défaut.
    const derniere = data.annees.length - 1;
    if (derniere >= 0) ouverts.value[derniere] = true;
  },
});

const bascule = (i) => { ouverts.value[i] = !ouverts.value[i]; };
const date = (iso) => formatDateFr(iso, 'jourMois');
const part = (c) => (c.total > 0 ? Math.round((c.couverts * 100) / c.total) : 0);
</script>

<template>
  <HeaderComponent
    icon="pi pi-chart-bar"
    titre="Ma progression"
    description="Les éléments du référentiel que vos traces démontrent, année par année."
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  >
    <template #actions>
      <Button label="Déposer une trace" icon="pi pi-plus" @click="router.push('/unifolio/traces/nouvelle')" />
    </template>
  </HeaderComponent>

  <LoadingBar v-if="chargement" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Erreur" :description="erreur" />
  <EmptyState v-else-if="!donnees.annees.length" icon="pi pi-chart-bar" color="emerald" title="Pas encore de progression" description="Votre progression apparaîtra dès que vous serez inscrit·e dans une année de BUT et que vous aurez déposé des traces." />

  <div v-else class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem] items-start">
    <div class="flex flex-col gap-4">
      <Card bare tag="section" v-for="(a, i) in donnees.annees" :key="a.scolarite.id" class="p-0 overflow-hidden">
        <button type="button" class="w-full flex flex-wrap items-center gap-3 p-4 bg-transparent border-0 cursor-pointer text-left" :aria-expanded="!!ouverts[i]" @click="bascule(i)">
          <i :class="ouverts[i] ? 'pi pi-chevron-down' : 'pi pi-chevron-right'" aria-hidden="true" />
          <div class="flex-1 min-w-48">
            <div class="font-semibold">{{ a.scolarite.annee }}</div>
            <div class="text-sm text-muted-color">{{ a.scolarite.anneeUniversitaire }}<template v-if="a.granularite"> · par {{ a.granularite.libelle.toLowerCase() }}</template></div>
          </div>
          <Tag v-if="a.campagne" :value="a.campagne.phase.libelle" :severity="a.campagne.phase.badge" />
          <template v-if="a.disponible">
            <Tag :value="`${a.resume.couverts} / ${a.resume.total} couverts`" severity="secondary" />
            <Tag v-if="pourcent(a.resume.evalScore) !== null" :value="`Évalué : ${pourcent(a.resume.evalScore)} %`" severity="success" />
          </template>
        </button>

        <div v-if="ouverts[i]" class="p-4 pt-0 flex flex-col gap-4">
          <p v-if="!a.disponible" class="m-0 text-sm text-muted-color">{{ a.message }}</p>
          <article v-for="c in a.competences" :key="c.id" class="rounded-lg p-3 border-l-[6px] flex flex-col gap-2" :style="{ borderLeftColor: couleurCompetence(c.couleur), background: couleurCompetence(c.couleur, 0.07) }">
            <div class="flex flex-wrap items-baseline gap-2">
              <strong class="flex-1 min-w-40">{{ c.nomCourt }}</strong>
              <span class="text-sm">{{ c.couverts }} / {{ c.total }}</span>
              <span v-if="pourcent(c.autoScore) !== null" class="text-xs text-muted-color">auto-évaluation {{ pourcent(c.autoScore) }} %</span>
              <span v-if="pourcent(c.evalScore) !== null" class="text-xs font-semibold">évalué {{ pourcent(c.evalScore) }} %</span>
            </div>
            <div class="h-2 rounded-full bg-surface-200 overflow-hidden" role="progressbar" :aria-valuenow="part(c)" aria-valuemin="0" aria-valuemax="100" :aria-label="`${c.nomCourt} : ${part(c)} % couvert`">
              <div class="h-full rounded-full" :style="{ width: `${part(c)}%`, background: couleurCompetence(c.couleur) }" />
            </div>
            <ul class="list-none m-0 p-0 flex flex-col gap-1 text-sm">
              <li v-for="e in c.elements" :key="e.id" class="flex items-start gap-2">
                <i :class="e.couvert ? 'pi pi-check-circle text-emerald-600' : 'pi pi-circle text-muted-color'" class="mt-1" :aria-label="e.couvert ? 'Couvert' : 'Pas encore couvert'" />
                <span class="flex-1" :class="{ 'text-muted-color': !e.couvert }"><strong v-if="e.code">{{ e.code }}</strong> {{ e.libelle }}</span>
                <span v-if="e.nbTraces" class="text-xs text-muted-color">{{ e.nbTraces }} trace(s)</span>
                <span v-if="pourcent(e.evalScore) !== null" class="text-xs font-semibold">{{ pourcent(e.evalScore) }} %</span>
              </li>
            </ul>
          </article>
        </div>
      </Card>
    </div>

    <Card bare tag="aside" class="p-4 flex flex-col gap-3 xl:sticky xl:top-2" aria-label="Échéances">
      <h3 class="m-0 text-base font-semibold"><i class="pi pi-calendar mr-1" /> Échéances</h3>
      <p v-if="!donnees.echeances.length" class="m-0 text-sm text-muted-color">Aucune échéance à venir.</p>
      <ul v-else class="list-none m-0 p-0 flex flex-col gap-3">
        <li v-for="e in donnees.echeances" :key="e.type + e.date" class="flex flex-col">
          <span class="font-medium">{{ e.libelle }}</span>
          <span class="text-sm text-muted-color">{{ date(e.date) }} · {{ e.campagne }}</span>
        </li>
      </ul>
    </Card>
  </div>
</template>
