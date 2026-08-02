<script setup>
import {
  CalendarIcon,
  UsersIcon,
  UserPlusIcon,
  ClockIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline';
import { Card, ButtonDelete } from '@components';

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
      <Button
        label="Créer une période"
        icon="pi pi-plus"
        @click="emit('create')"
        size="small"
        class="bg-violet-600 hover:bg-violet-750 text-white border-0 font-bold rounded-xl shadow-md cursor-pointer"
      />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <Card
        v-for="p in periods"
        :key="p.id"
        :title="p.name"
        icon="pi pi-calendar"
        :color="p.type === 'Alternance' ? 'purple' : 'blue'"
        :badge="p.anneeUniv"
        badge-severity="secondary"
        class="flex flex-col justify-between h-full shadow-sm hover:shadow-md transition-all duration-300"
        body-class="flex-1 flex flex-col justify-between pt-4"
      >
        <div>
          <!-- Type and Date tags -->
          <div class="flex flex-wrap gap-2 mb-4">
            <Tag
              :value="p.type"
              severity="info"
              class="text-[9px] font-extrabold uppercase font-sans px-2.5 py-0.5 rounded"
            />
            <Tag
              :value="p.datesFlexibles ? 'Dates flexibles' : 'Dates strictes'"
              :severity="p.datesFlexibles ? 'info' : 'secondary'"
              class="text-[9px] font-bold uppercase font-sans px-2 py-0.5 rounded"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[11px] text-slate-500 dark:text-slate-400 font-medium">
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
            class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-3 border border-slate-100 dark:border-slate-700/40 text-[10px] mt-4 space-y-1 text-slate-500 font-sans">
            <span class="font-bold text-slate-700 dark:text-slate-350 block mb-1">Paramètres Convention :</span>
            <p class="truncate"><strong class="text-slate-650 dark:text-slate-400">Compétences :</strong> {{
              p.competencesVisees || 'Non définies' }}</p>
            <p class="truncate"><strong class="text-slate-650 dark:text-slate-400">Rendu :</strong> {{
              p.documentsRendre || 'Non définies' }}</p>
          </div>
        </div>

        <div class="flex gap-2 w-full pt-4 border-t border-slate-100 dark:border-slate-700/60 mt-4">
          <Button
            label="Accéder au suivi"
            icon="pi pi-search"
            @click="emit('select', p)"
            class="flex-1 bg-violet-600 hover:bg-violet-750 text-white font-bold border-0 rounded-xl text-xs transition-all cursor-pointer"
          />
          <Button
            icon="pi pi-cog"
            severity="secondary"
            @click="emit('edit', p)"
            v-tooltip="'Paramètres de la période'"
            class="px-3 bg-slate-50 dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600/60 text-slate-700 dark:text-slate-300 border-0 rounded-xl transition-all cursor-pointer"
          />
          <ButtonDelete
            tooltip="Supprimer la période"
            @confirm-delete="emit('delete', p)"
            class="px-3 border-0 rounded-xl transition-all cursor-pointer !mr-0"
          />
        </div>
      </Card>
    </div>
  </div>
</template>
