<script setup>
import { Card } from '@components';
import {
  CalendarIcon,
  UsersIcon,
  UserIcon,
  UserPlusIcon,
  ClockIcon,
  DocumentTextIcon,
  Cog6ToothIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  period: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['edit']);
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center pb-2 border-b border-slate-100 dark:border-slate-800">
      <div>
        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">
          Configuration & Paramètres de la période
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">Détails administratifs, calendrier et modalités pédagogiques.</p>
      </div>
      <button
        @click="emit('edit', period)"
        class="py-2 px-4 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer border-0"
      >
        <Cog6ToothIcon class="w-3.5 h-3.5" />
        <span>Modifier la période</span>
      </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Section 1: Informations Générales & Calendrier -->
      <Card
        title="Informations & Calendrier"
        subtitle="Dates clés et durée minimale du stage."
        :icon="CalendarIcon"
        iconClass="text-violet-600 dark:text-violet-400"
        iconBgClass="bg-violet-50 dark:bg-violet-950/20 border-violet-100 dark:border-violet-900/30"
        bodyClass="space-y-4"
      >
        <div class="grid grid-cols-2 gap-4 text-xs">
          <div class="space-y-1">
            <span class="text-slate-400 block">Type de période</span>
            <span class="font-bold text-slate-800 dark:text-slate-200 text-sm flex items-center gap-1.5">
              <span class="bg-violet-100 dark:bg-violet-950/40 text-violet-700 dark:text-violet-400 px-2 py-0.5 rounded text-[9px] uppercase tracking-wider font-extrabold">{{ period.type }}</span>
            </span>
          </div>
          <div class="space-y-1">
            <span class="text-slate-400 block">Année Universitaire</span>
            <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ period.anneeUniv }}</span>
          </div>
          <div class="space-y-1">
            <span class="text-slate-400 block">Dates de stage</span>
            <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ period.dates }}</span>
          </div>
          <div class="space-y-1">
            <span class="text-slate-400 block">Durée minimale</span>
            <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ period.minWeeks }} semaines</span>
          </div>
          <div class="space-y-1 col-span-2">
            <span class="text-slate-400 block">Souplesse des dates</span>
            <span :class="['px-2 py-0.5 text-[9px] rounded font-bold uppercase inline-block', period.datesFlexibles ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400']">
              {{ period.datesFlexibles ? 'Dates de début et de fin flexibles' : 'Dates strictes imposées' }}
            </span>
          </div>
        </div>
      </Card>

      <!-- Section 2: Responsables & Équipe -->
      <Card
        title="Responsables & Accompagnement"
        subtitle="Membres de l'équipe pédagogique référents."
        :icon="UsersIcon"
        iconClass="text-indigo-600 dark:text-indigo-400"
        iconBgClass="bg-indigo-50 dark:bg-indigo-950/20 border-indigo-100 dark:border-indigo-900/30"
        bodyClass="space-y-4"
      >
        <div class="space-y-3 text-xs">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-700/40 flex items-center justify-center shrink-0">
              <UserIcon class="w-4 h-4 text-slate-500" />
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Responsable Principal</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ period.responsablePrincipal }}</span>
            </div>
          </div>
          <div class="flex items-start gap-3 border-t border-slate-50 dark:border-slate-700/50 pt-3">
            <div class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-700/40 flex items-center justify-center shrink-0">
              <UserPlusIcon class="w-4 h-4 text-slate-500" />
            </div>
            <div>
              <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Co-responsables</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 text-sm" v-if="period.coResponsables && period.coResponsables.length > 0">
                {{ period.coResponsables.join(', ') }}
              </span>
              <span class="text-slate-400 italic text-sm" v-else>Aucun co-responsable défini</span>
            </div>
          </div>
        </div>
      </Card>

      <!-- Section 3: Interruptions & Soutenances -->
      <Card
        title="Interruptions & Soutenances"
        subtitle="Dates des interruptions et sessions de soutenance."
        :icon="ClockIcon"
        iconClass="text-amber-600 dark:text-amber-400"
        iconBgClass="bg-amber-50 dark:bg-amber-950/20 border-amber-100 dark:border-amber-900/30"
        bodyClass="space-y-4"
      >
        <div class="space-y-4 text-xs">
          <div>
            <span class="font-bold text-slate-800 dark:text-slate-200 block mb-2">Périodes d'interruption :</span>
            <div v-if="period.interruptions && period.interruptions.length > 0" class="space-y-1.5">
              <div v-for="i in period.interruptions" :key="i.dateDebut" class="bg-slate-50 dark:bg-slate-900/40 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800/40 flex justify-between items-center">
                <div>
                  <span class="font-bold text-slate-700 dark:text-slate-350">Du {{ new Date(i.dateDebut).toLocaleDateString('fr-FR') }} au {{ new Date(i.dateFin).toLocaleDateString('fr-FR') }}</span>
                  <span class="text-[10px] text-slate-400 block mt-0.5">{{ i.motif || 'Aucun motif renseigné' }}</span>
                </div>
                <span class="bg-amber-150 dark:bg-amber-950/30 text-amber-800 dark:text-amber-450 px-2 py-0.5 rounded font-bold text-[9px] uppercase tracking-wider">Interruption</span>
              </div>
            </div>
            <div v-else class="text-slate-400 italic text-xs py-2 bg-slate-50/50 dark:bg-slate-900/10 rounded-xl text-center border border-dashed border-slate-200/50 dark:border-slate-750">
              Aucune période d'interruption déclarée.
            </div>
          </div>

          <div class="border-t border-slate-50 dark:border-slate-750 pt-3">
            <span class="font-bold text-slate-800 dark:text-slate-200 block mb-2">Sessions de soutenance :</span>
            <div v-if="period.soutenances && period.soutenances.length > 0" class="space-y-2">
              <div v-for="s in period.soutenances" :key="s.dateDebut" class="bg-slate-50 dark:bg-slate-900/40 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800/40 space-y-1.5">
                <div class="flex justify-between items-center">
                  <span class="font-bold text-slate-700 dark:text-slate-350">Soutenances du {{ new Date(s.dateDebut).toLocaleDateString('fr') }} au {{ new Date(s.dateFin).toLocaleDateString('fr') }}</span>
                  <span class="bg-emerald-100 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-400 px-2 py-0.5 rounded font-bold text-[9px] uppercase tracking-wider">Soutenances</span>
                </div>
                <div class="text-[10px] text-slate-400 space-y-0.5">
                  <p>Date limite rendu rapport : <strong class="text-slate-600 dark:text-slate-300">{{ new Date(s.dateRenduRapport).toLocaleDateString('fr') }}</strong></p>
                  <p v-if="s.modalites">Modalités : {{ s.modalites }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-slate-400 italic text-xs py-2 bg-slate-50/50 dark:bg-slate-900/10 rounded-xl text-center border border-dashed border-slate-200/50 dark:border-slate-750">
              Aucune période de soutenance programmée.
            </div>
          </div>
        </div>
      </Card>

      <!-- Section 4: Modalités d'Évaluation & Pédagogie -->
      <Card
        title="Modalités Pédagogiques & Rendu"
        subtitle="Consignes de validation et compétences attendues."
        :icon="DocumentTextIcon"
        iconClass="text-emerald-600 dark:text-emerald-400"
        iconBgClass="bg-emerald-50 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900/30"
        bodyClass="space-y-4"
      >
        <div class="space-y-3 text-xs">
          <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3 border border-slate-100 dark:border-slate-800/40">
            <span class="font-bold text-slate-800 dark:text-slate-200 block mb-1">Compétences visées :</span>
            <p class="text-slate-600 dark:text-slate-450 leading-relaxed font-medium">
              {{ period.competencesVisees || 'Non définies.' }}
            </p>
          </div>
          <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3 border border-slate-100 dark:border-slate-800/40">
            <span class="font-bold text-slate-800 dark:text-slate-200 block mb-1">Documents à rendre :</span>
            <p class="text-slate-600 dark:text-slate-450 leading-relaxed font-medium">
              {{ period.documentsRendre || 'Non définis.' }}
            </p>
          </div>
          <div v-if="period.commentaireLibre" class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3 border border-slate-100 dark:border-slate-800/40">
            <span class="font-bold text-slate-800 dark:text-slate-200 block mb-1">Consignes complémentaires :</span>
            <p class="text-slate-600 dark:text-slate-450 leading-relaxed font-medium">
              {{ period.commentaireLibre }}
            </p>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>
