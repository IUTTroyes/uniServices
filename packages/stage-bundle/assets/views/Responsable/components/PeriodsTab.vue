<script setup>
import {
  PlusIcon,
  CalendarIcon,
  UsersIcon,
  UserPlusIcon,
  ClockIcon,
  DocumentTextIcon,
  MagnifyingGlassIcon,
  Cog6ToothIcon,
  TrashIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  periods: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['create', 'edit', 'delete', 'select']);
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">
        Configuration des périodes universitaires
      </h2>
      <button @click="emit('create')"
        class="text-xs font-bold px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer border-0">
        <PlusIcon class="w-3.5 h-3.5" />
        <span>Créer une période</span>
      </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div v-for="p in periods" :key="p.id"
        class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700/60 rounded-3xl p-6 shadow-sm flex flex-col justify-between space-y-4">
        <div>
          <div class="flex justify-between items-start gap-4">
            <div class="flex gap-2">
              <span
                class="bg-violet-50 dark:bg-violet-950/40 text-violet-700 dark:text-violet-400 px-2.5 py-0.5 rounded font-bold text-[9px] uppercase tracking-wider font-extrabold">
                {{ p.type }}
              </span>
              <span
                class="bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2.5 py-0.5 rounded font-bold text-[9px] font-extrabold">
                {{ p.anneeUniv }}
              </span>
            </div>
            <span
              :class="['px-2 py-0.5 text-[9px] rounded font-bold uppercase', p.datesFlexibles ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400']">
              {{ p.datesFlexibles ? 'Dates flexibles' : 'Dates strictes' }}
            </span>
          </div>

          <h3 class="text-base font-bold text-slate-900 dark:text-white mt-4">{{ p.name }}</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-[11px] text-slate-500 dark:text-slate-400 font-medium">
            <div class="space-y-2">
              <div class="flex items-center gap-2">
                <CalendarIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                <span>{{ p.dates }} ({{ p.minWeeks }} sem. min)</span>
              </div>
              <div class="flex items-center gap-2">
                <UsersIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                <span>Responsable : <strong>{{ p.responsablePrincipal }}</strong></span>
              </div>
              <div class="flex items-start gap-2">
                <UserPlusIcon class="w-3.5 h-3.5 text-slate-400 mt-0.5 shrink-0" />
                <span>Co-responsables : <strong>{{ p.coResponsables?.join(', ') || 'Aucun' }}</strong></span>
              </div>
            </div>

            <div
              class="space-y-2 border-t md:border-t-0 md:border-l border-slate-100 dark:border-slate-700 md:pl-4 pt-2 md:pt-0">
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ p.interruptions?.length || 0 }}</span>
                <span>Interruption(s)</span>
              </div>
              <div class="flex items-center gap-2" v-for="s in p.soutenances" :key="s.dateDebut">
                <ClockIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                <span>Soutenances : {{ new Date(s.dateDebut).toLocaleDateString('fr') }} au {{ new
                  Date(s.dateFin).toLocaleDateString('fr') }}</span>
              </div>
              <div class="flex items-center gap-2">
                <DocumentTextIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                <span>Fichiers : {{ p.consignesFichiers?.length || 0 }} consignes</span>
              </div>
            </div>
          </div>

          <!-- Display convention parameters brief summary -->
          <div
            class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3 border border-slate-100 dark:border-slate-700/40 text-[10px] mt-4 space-y-1 text-slate-500">
            <span class="font-bold text-slate-700 dark:text-slate-350 block mb-1">Paramètres Convention :</span>
            <p class="truncate"><strong class="text-slate-650 dark:text-slate-400">Compétences :</strong> {{
              p.competencesVisees || 'Non définies' }}</p>
            <p class="truncate"><strong class="text-slate-650 dark:text-slate-400">Rendu :</strong> {{
              p.documentsRendre || 'Non définies' }}</p>
          </div>
        </div>

        <div class="flex gap-2 w-full pt-2">
          <button
            @click="emit('select', p)"
            class="flex-1 py-2 bg-violet-600 hover:bg-violet-700 text-white font-bold rounded-xl text-xs transition-all flex items-center justify-center gap-2 border-0 cursor-pointer">
            <MagnifyingGlassIcon class="w-3.5 h-3.5" />
            <span>Accéder au suivi</span>
          </button>
          <button
            @click="emit('edit', p)"
            class="px-3 py-2 bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600/60 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition-all flex items-center justify-center border-0 cursor-pointer"
            v-tooltip="'Paramètres de la période'">
            <Cog6ToothIcon class="w-4 h-4" />
          </button>
          <button
            @click="emit('delete', p)"
            class="px-3 py-2 bg-rose-50 dark:bg-rose-950/20 hover:bg-rose-100 dark:hover:bg-rose-900/40 text-rose-600 dark:text-rose-455 font-bold rounded-xl text-xs transition-all flex items-center justify-center border-0 cursor-pointer"
            v-tooltip="'Supprimer la période'">
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
