<script setup lang="ts">
import { EmptyState } from '@components';

/**
 * Restitution accessible des résultats d'une section : uniquement des tableaux et du texte (lisibles au clavier et par
 * un lecteur d'écran), sans graphique. Reçoit une section des statistiques (SectionInstanceAnalyticsDto).
 */
defineProps<{ section: any; seuil?: number }>();

const pourcentage = (v: number) => `${Math.round(v)} %`;
</script>

<template>
  <section :aria-labelledby="`section-${section.sectionInstanceId}`" class="space-y-4">
    <h3 :id="`section-${section.sectionInstanceId}`" class="text-lg font-semibold text-slate-900 dark:text-white">
      {{ section.sectionTitle }}
    </h3>
    <p class="text-sm text-slate-600 dark:text-slate-400">
      <span v-if="section.enseignement">{{ section.enseignement }}<span v-if="section.intervenant"> — {{ section.intervenant }}</span> · </span>
      {{ section.reponses }} répondant{{ section.reponses > 1 ? 's' : '' }}
    </p>

    <EmptyState
      v-if="section.masquee"
      compact
      icon="pi pi-eye-slash"
      color="amber"
      title="Résultats masqués"
      :description="`Moins de ${seuil ?? '?'} réponses pour cette section : le détail n'est pas affiché pour préserver l'anonymat.`"
    />

    <article v-for="question in section.questions" v-else :key="question.questionId" class="space-y-2">
      <h4 class="text-base font-medium text-slate-800 dark:text-slate-200">{{ question.questionLabel }}</h4>
      <p class="text-sm text-slate-500 dark:text-slate-400">{{ question.totalResponses }} réponse{{ question.totalResponses > 1 ? 's' : '' }}</p>

      <table v-if="question.stats.choices" class="w-full text-sm border-collapse">
        <caption class="sr-only">Répartition des réponses à « {{ question.questionLabel }} »</caption>
        <thead><tr class="text-left border-b border-slate-200 dark:border-slate-700"><th scope="col" class="py-1">Réponse</th><th scope="col" class="py-1 text-right">Nombre</th><th scope="col" class="py-1 text-right">Part</th></tr></thead>
        <tbody>
          <tr v-for="c in question.stats.choices" :key="c.text" class="border-b border-slate-100 dark:border-slate-800">
            <th scope="row" class="py-1 font-normal">{{ c.text }}</th><td class="py-1 text-right">{{ c.count }}</td><td class="py-1 text-right">{{ pourcentage(c.percentage) }}</td>
          </tr>
        </tbody>
      </table>

      <div v-else-if="question.stats.average !== undefined" class="space-y-1">
        <p class="text-sm">Moyenne : <strong>{{ question.stats.average }}</strong> (échelle de {{ question.stats.min }} à {{ question.stats.max }})</p>
        <table class="w-full text-sm border-collapse">
          <caption class="sr-only">Distribution des notes à « {{ question.questionLabel }} »</caption>
          <thead><tr class="text-left border-b border-slate-200 dark:border-slate-700"><th scope="col" class="py-1">Note</th><th scope="col" class="py-1 text-right">Nombre</th></tr></thead>
          <tbody>
            <tr v-for="(nb, note) in question.stats.distribution" :key="note" class="border-b border-slate-100 dark:border-slate-800">
              <th scope="row" class="py-1 font-normal">{{ note }}</th><td class="py-1 text-right">{{ nb }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <table v-else-if="question.stats.ranking" class="w-full text-sm border-collapse">
        <caption class="sr-only">Classement moyen à « {{ question.questionLabel }} »</caption>
        <thead><tr class="text-left border-b border-slate-200 dark:border-slate-700"><th scope="col" class="py-1">Élément</th><th scope="col" class="py-1 text-right">Rang moyen</th></tr></thead>
        <tbody>
          <tr v-for="r in question.stats.ranking" :key="r.text" class="border-b border-slate-100 dark:border-slate-800">
            <th scope="row" class="py-1 font-normal">{{ r.text }}</th><td class="py-1 text-right">{{ r.averageRank }}</td>
          </tr>
        </tbody>
      </table>

      <table v-else-if="question.stats.grid" class="w-full text-sm border-collapse">
        <caption class="sr-only">Réponses à la grille « {{ question.questionLabel }} »</caption>
        <thead>
          <tr class="text-left border-b border-slate-200 dark:border-slate-700">
            <th scope="col" class="py-1">Ligne</th>
            <th v-for="col in question.stats.columns" :key="col" scope="col" class="py-1 text-right">{{ col }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in question.stats.rows" :key="row" class="border-b border-slate-100 dark:border-slate-800">
            <th scope="row" class="py-1 font-normal">{{ row }}</th>
            <td v-for="col in question.stats.columns" :key="col" class="py-1 text-right">{{ question.stats.grid?.[row]?.[col] ?? 0 }}</td>
          </tr>
        </tbody>
      </table>

      <ul v-else-if="question.stats.samples?.length" class="space-y-1 list-disc pl-5 text-sm text-slate-700 dark:text-slate-300">
        <li v-for="(texte, i) in question.stats.samples" :key="i">{{ texte }}</li>
      </ul>
      <p v-else class="text-sm text-slate-500 dark:text-slate-400">Aucune réponse.</p>
    </article>
  </section>
</template>
