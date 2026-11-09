<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { Card, EmptyState, HeaderComponent, ListSkeleton } from '@components';
import { getComparaisonService } from '@/requests/questionnaire_service';

/**
 * Comparaison des campagnes d'un même modèle, d'une année sur l'autre : participation, puis pour chaque question
 * commune la moyenne (échelles) ou la répartition (choix). Tableaux accessibles, une colonne par campagne.
 */
const route = useRoute();
const uuid = route.params.id as string;

const chargement = ref(true);
const donnees = ref<any>(null);
const campagnes = computed(() => donnees.value?.campagnes ?? []);
const questions = computed(() => donnees.value?.questions ?? []);

function cellule(valeur: any, question: any): string {
  if (!valeur) return '—';
  if (valeur.moyenne !== null && valeur.moyenne !== undefined) return `${valeur.moyenne} (${valeur.n} rép.)`;
  if (valeur.choix) {
    return Object.entries(valeur.choix).map(([texte, pct]) => `${texte} : ${pct} %`).join(' · ');
  }
  return `${valeur.n} rép.`;
}

onMounted(async () => {
  try {
    donnees.value = await getComparaisonService(uuid);
  } finally {
    chargement.value = false;
  }
});
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent
      :titre="`Comparaison — ${donnees?.modele?.title ?? 'campagnes'}`"
      description="Évolution des campagnes créées à partir du même modèle. Une campagne sous le seuil d'anonymat n'apparaît que dans la participation."
      icon="pi pi-chart-line"
      color="green"
    />

    <ListSkeleton v-if="chargement" />

    <EmptyState
      v-else-if="campagnes.length < 2"
      icon="pi pi-chart-line"
      title="Pas encore de comparaison possible"
      description="Il faut au moins deux campagnes publiées à partir du même modèle."
    />

    <template v-else>
      <Card title="Participation" icon="pi pi-users" color="green">
        <table class="w-full text-sm border-collapse">
          <caption class="sr-only">Participation par campagne</caption>
          <thead>
            <tr class="text-left border-b border-slate-200 dark:border-slate-700">
              <th scope="col" class="py-1">Campagne</th>
              <th scope="col" class="py-1">Année</th>
              <th scope="col" class="py-1 text-right">Invités</th>
              <th scope="col" class="py-1 text-right">Réponses</th>
              <th scope="col" class="py-1 text-right">Taux</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in campagnes" :key="c.uuid" class="border-b border-slate-100 dark:border-slate-800">
              <th scope="row" class="py-1 font-normal">{{ c.title }}</th>
              <td class="py-1">{{ c.annee || '—' }}</td>
              <td class="py-1 text-right">{{ c.invites }}</td>
              <td class="py-1 text-right">{{ c.reponses }}</td>
              <td class="py-1 text-right">{{ c.taux }} %</td>
            </tr>
          </tbody>
        </table>
      </Card>

      <Card title="Questions" icon="pi pi-list" color="green">
        <EmptyState v-if="questions.length === 0" compact title="Aucune question commune avec des réponses" />
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <caption class="sr-only">Résultats par question et par campagne</caption>
            <thead>
              <tr class="text-left border-b border-slate-200 dark:border-slate-700">
                <th scope="col" class="py-1">Question</th>
                <th v-for="c in campagnes" :key="c.uuid" scope="col" class="py-1">{{ c.annee || c.title }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="q in questions" :key="q.cle" class="border-b border-slate-100 dark:border-slate-800 align-top">
                <th scope="row" class="py-1 font-normal">{{ q.label }}</th>
                <td v-for="(v, i) in q.valeurs" :key="i" class="py-1">{{ cellule(v, q) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </template>
  </div>
</template>
