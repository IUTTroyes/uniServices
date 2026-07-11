<script setup>
import { Card } from '@components';
import { ChartPieIcon, CurrencyEuroIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  kpis: {
    type: Object,
    required: true
  },
  activePeriodName: {
    type: String,
    required: true
  }
});
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- Placement rates Card -->
      <Card
        title="Taux d'insertion des étudiants"
        :subtitle="'Proportion d\'étudiants ayant validé leur convention à ce jour. Période : ' + activePeriodName"
        :icon="ChartPieIcon"
        iconClass="text-violet-600 dark:text-violet-400"
        iconBgClass="bg-violet-50 dark:bg-violet-950/20 border-violet-100 dark:border-violet-900/30"
      >
        <div class="flex flex-col sm:flex-row items-center justify-around gap-6 py-2">
          <!-- SVG Donut Chart dynamically calculated -->
          <div class="relative w-44 h-44 shrink-0">
            <svg viewBox="0 0 36 36" class="w-full h-full transform -rotate-90">
              <path class="text-slate-100 dark:text-slate-700" stroke-width="3" stroke="currentColor" fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
              <path class="text-violet-600" stroke-width="3" :stroke-dasharray="kpis.rate + ', 100'"
                stroke-linecap="round" stroke="currentColor" fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
              <span class="text-3xl font-black text-slate-900 dark:text-white leading-none">{{ kpis.rate }} %</span>
              <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-1">Placés</span>
            </div>
          </div>

          <!-- Legend -->
          <div class="space-y-3 text-xs w-full">
            <div class="flex items-center justify-between">
              <span class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded bg-violet-600"></span>
                <span class="text-slate-500">Conventions signées / Placées</span>
              </span>
              <span class="font-bold text-slate-800 dark:text-slate-100">{{ kpis.placed }} étudiants</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded bg-slate-200 dark:bg-slate-700"></span>
                <span class="text-slate-500">Recherche active</span>
              </span>
              <span class="font-bold text-slate-800 dark:text-slate-100">{{ kpis.total - kpis.placed }} étudiants</span>
            </div>
          </div>
        </div>
      </Card>

      <!-- Average compensation salary stats -->
      <Card
        title="Statistiques financières de la période"
        subtitle="Moyennes de gratification horaire négociées par les étudiants."
        :icon="CurrencyEuroIcon"
        iconClass="text-amber-600 dark:text-amber-400"
        iconBgClass="bg-amber-50 dark:bg-amber-950/20 border-amber-100 dark:border-amber-900/30"
        bodyClass="flex flex-col justify-between h-full"
      >
        <div class="space-y-4 py-2">
          <div>
            <div class="flex justify-between text-xs mb-1 font-bold">
              <span class="text-slate-600 dark:text-slate-300">Gratification maximale enregistrée</span>
              <span class="text-violet-600">6.80 €/h</span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
              <div class="bg-violet-600 h-full rounded-full" style="width: 100%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs mb-1 font-bold">
              <span class="text-slate-600 dark:text-slate-300">Gratification moyenne</span>
              <span class="text-violet-500">4.95 €/h</span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
              <div class="bg-violet-500 h-full rounded-full" style="width: 72.8%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs mb-1 font-bold">
              <span class="text-slate-600 dark:text-slate-300">Minimum légal obligatoire</span>
              <span class="text-slate-400">4.35 €/h</span>
            </div>
            <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
              <div class="bg-slate-400 h-full rounded-full" style="width: 63.9%"></div>
            </div>
          </div>
        </div>

        <div class="text-[10px] text-slate-400 dark:text-slate-500 pt-4 border-t border-slate-100 dark:border-slate-700/50 mt-6">
          * Indicateurs de gratification calculés sur la base des contrats saisis.
        </div>
      </Card>

    </div>
  </div>
</template>
