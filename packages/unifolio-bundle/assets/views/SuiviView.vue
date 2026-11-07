<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import ProgressBar from 'primevue/progressbar';
import { suiviService, pourcent, teinteScore, ALERTES } from '../requests/suiviService';
import { messageErreur } from '../requests/traceService';
import { couleurCompetence, texteSurCompetence } from '../components/traces/options';

const router = useRouter();
const campagnes = ref([]);
const choix = ref(null);
const suivi = ref(null);
const chargement = ref(true);
const chargementMatrice = ref(false);
const erreur = ref(null);
const seulementAlertes = ref(false);

onMounted(async () => {
  try {
    const { data } = await suiviService.campagnes();
    campagnes.value = data;
    choix.value = data[0]?.uuid ?? null;
  } catch (e) {
    erreur.value = messageErreur(e, 'Impossible de charger les campagnes.');
  } finally {
    chargement.value = false;
  }
});

watch(choix, async (uuid) => {
  suivi.value = null;
  if (!uuid) return;
  chargementMatrice.value = true;
  erreur.value = null;
  try {
    suivi.value = (await suiviService.campagne(uuid)).data;
  } catch (e) {
    erreur.value = messageErreur(e, 'Impossible de charger le suivi de cette campagne.');
  } finally {
    chargementMatrice.value = false;
  }
});

const lignes = computed(() => (suivi.value?.lignes ?? []).filter((l) => !seulementAlertes.value || l.alertes.length));
const libelleCampagne = (c) => `${c.libelle} — ${c.anneeUniversitaire.libelle}`;
const kpis = computed(() => {
  const i = suivi.value?.indicateurs;
  if (!i) return [];

  return [
    { cle: 'etudiants', libelle: 'Étudiants', valeur: i.nbEtudiants },
    { cle: 'depot', libelle: 'Ont déposé', valeur: i.tauxDepot === null ? '–' : `${pourcent(i.tauxDepot)} %`, alerte: i.sansDepot > 0 ? `${i.sansDepot} sans dépôt` : null },
    { cle: 'couverture', libelle: 'Éléments couverts', valeur: i.tauxCouverture === null ? '–' : `${pourcent(i.tauxCouverture)} %` },
    { cle: 'aevaluer', libelle: 'Traces à évaluer', valeur: i.aEvaluer, alerte: i.enRetard > 0 ? `${i.enRetard} en retard` : null },
    { cle: 'ecarts', libelle: 'Écarts notables', valeur: i.ecartsNotables },
  ];
});
const ouvrir = (l) => router.push(`/unifolio/evaluations/etudiants/${choix.value}/${l.etudiant.id}`);
</script>

<template>
  <HeaderComponent
    icon="pi pi-users"
    titre="Suivi des étudiants"
    description="Couverture du référentiel, évaluations et alertes, étudiant par étudiant."
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  >
    <template #actions>
      <a v-if="choix && suivi?.disponible" :href="suiviService.urlExport(choix)"><Button label="Exporter (CSV)" icon="pi pi-download" severity="secondary" outlined /></a>
    </template>
  </HeaderComponent>

  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />
  <EmptyState v-else-if="!campagnes.length" icon="pi pi-users" color="emerald" title="Aucune campagne à suivre" description="Vous n'avez pas d'étudiant affecté. Un référent portfolio peut vous en affecter." />

  <template v-else>
    <div class="flex flex-wrap items-center gap-3">
      <Select v-model="choix" :options="campagnes" option-value="uuid" :option-label="libelleCampagne" class="min-w-80" aria-label="Campagne" />
      <Tag v-if="campagnes.find((c) => c.uuid === choix)" :value="campagnes.find((c) => c.uuid === choix).perimetre === 'promo' ? 'Toute la promo' : 'Vos étudiants'" severity="secondary" />
      <label class="flex items-center gap-2 text-sm cursor-pointer ml-auto"><ToggleSwitch v-model="seulementAlertes" /> Uniquement les étudiants à surveiller</label>
    </div>

    <ProgressBar v-if="chargementMatrice" mode="indeterminate" style="height: 4px" />
    <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Erreur" :description="erreur" />

    <template v-else-if="suivi">
      <p v-if="suivi.message && !suivi.disponible" class="text-muted-color">{{ suivi.message }}</p>

      <div class="grid gap-3 grid-cols-2 md:grid-cols-5">
        <div v-for="k in kpis" :key="k.cle" class="card p-3 m-0 flex flex-col">
          <span class="text-sm text-muted-color">{{ k.libelle }}</span>
          <span class="text-2xl font-semibold">{{ k.valeur }}</span>
          <span v-if="k.alerte" class="text-xs text-red-600">{{ k.alerte }}</span>
        </div>
      </div>

      <div class="card p-0 overflow-x-auto">
        <table class="w-full border-collapse text-sm">
          <caption class="sr-only">Couverture du référentiel par étudiant et par compétence</caption>
          <thead>
            <tr class="text-left">
              <th scope="col" class="p-3 sticky left-0 bg-white dark:bg-surface-900 z-10">Étudiant</th>
              <th v-for="c in suivi.competences" :key="c.id" scope="col" class="p-2 text-center">
                <span class="inline-block px-2 py-1 rounded text-xs font-semibold" :style="{ background: couleurCompetence(c.couleur), color: texteSurCompetence(c.couleur) }" :title="c.libelle">{{ c.nomCourt }}</span>
              </th>
              <th scope="col" class="p-2 text-center">Traces</th>
              <th scope="col" class="p-2">Alertes</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="l in lignes" :key="l.etudiant.id" class="border-t border-surface-200 hover:bg-surface-50 cursor-pointer" tabindex="0" @click="ouvrir(l)" @keydown.enter="ouvrir(l)">
              <th scope="row" class="p-3 text-left font-medium sticky left-0 bg-white dark:bg-surface-900">{{ l.etudiant.nom }} {{ l.etudiant.prenom }}</th>
              <td v-for="c in suivi.competences" :key="c.id" class="p-1 text-center">
                <div v-if="l.cellules[c.id]" class="rounded px-2 py-1 leading-tight" :style="teinteScore(l.cellules[c.id].evalScore)" :title="`Couverts ${l.cellules[c.id].couverts}/${l.cellules[c.id].total} · auto-évaluation ${pourcent(l.cellules[c.id].autoScore) ?? '–'} %`">
                  <strong>{{ l.cellules[c.id].couverts }}/{{ l.cellules[c.id].total }}</strong>
                  <div class="text-xs">{{ pourcent(l.cellules[c.id].evalScore) !== null ? `${pourcent(l.cellules[c.id].evalScore)} %` : '·' }}</div>
                </div>
              </td>
              <td class="p-2 text-center">{{ l.resume.nbTraces }}</td>
              <td class="p-2">
                <div class="flex flex-wrap gap-1">
                  <Tag v-for="a in l.alertes" :key="a" :value="ALERTES[a].libelle" :severity="ALERTES[a].severite" :icon="ALERTES[a].icone" />
                </div>
              </td>
            </tr>
            <tr v-if="!lignes.length"><td :colspan="suivi.competences.length + 3" class="p-4 text-center text-muted-color">Aucun étudiant à afficher.</td></tr>
          </tbody>
        </table>
      </div>
      <p class="text-xs text-muted-color m-0">Chaque case : éléments couverts par une trace déposée, puis score d'évaluation moyen (toutes évaluations rendues). Cliquez sur un étudiant pour ouvrir son suivi détaillé.</p>
    </template>
  </template>
</template>
