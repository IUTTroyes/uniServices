<script setup>
import { ListSkeleton } from '@components';

defineProps({
  enseignements: {
    type: Array,
    required: true,
  },
  isLoadingEnseignements: {
    type: Boolean,
    required: true,
  },
  diplomes: {
    type: Array,
    required: true,
  },
  selectedDiplome: {
    type: [String, Number, null],
    default: null,
  },
  typesDisponibles: {
    type: Array,
    required: true,
  },
  filtresTypes: {
    type: Array,
    required: true,
  },
  selectedType: {
    type: String,
    required: true,
  },
  enseignementsFiltres: {
    type: Array,
    required: true,
  },
  shortcuts: {
    type: Array,
    required: true,
  },
});

const emit = defineEmits(['update:selectedDiplome', 'update:selectedType', 'openTeaching']);
</script>

<template>
  <article class="card p-5! mb-0!">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
      <h3 class="m-0! text-lg!">Mes enseignements</h3>
      <span class="text-sm text-muted-color">{{ enseignements.length }} enseignements</span>
    </div>
    <ListSkeleton v-if="isLoadingEnseignements" :count="3" class="mb-4" />
    <Tabs v-else-if="diplomes.length" :value="selectedDiplome" scrollable @update:value="emit('update:selectedDiplome', $event)">
      <TabList aria-label="Diplômes de mes enseignements">
        <Tab v-for="diplome in diplomes" :key="diplome.id" :value="diplome.id">
          <span>{{ diplome.libelle }}</span>
          <span class="ml-2 text-xs text-muted-color">({{ diplome.enseignements.length }})</span>
        </Tab>
      </TabList>
      <TabPanels class="px-0! pb-0!">
        <TabPanel v-for="diplome in diplomes" :key="diplome.id" :value="diplome.id">
          <template v-if="diplome.id === selectedDiplome">
            <div v-if="typesDisponibles.length > 1" class="flex flex-wrap items-center gap-3 mb-4">
              <span id="types-enseignements" class="text-sm text-muted-color">Type d’enseignement</span>
              <SelectButton
                  :model-value="selectedType"
                  :options="filtresTypes"
                  option-label="label"
                  option-value="value"
                  :allow-empty="false"
                  aria-labelledby="types-enseignements"
                  size="small"
                  class="flex-wrap"
                  @update:modelValue="emit('update:selectedType', $event)"
              />
            </div>
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
              <article v-for="enseignement in enseignementsFiltres" :key="enseignement.id" class="p-4 rounded-xl border border-surface-200 dark:border-surface-700 flex flex-col gap-2">
                <div><span class="uppercase font-semibold text-xs text-muted-color">{{ enseignement.type }} - </span><span class="text-xs font-semibold text-primary">{{ enseignement.codeEnseignement }}</span></div>
                <h4 class="m-0! text-base!">{{ enseignement.libelle }}</h4>
                <div class="flex flex-wrap gap-1 mt-auto">
                  <Button v-for="shortcut in shortcuts" :key="shortcut.key" :label="shortcut.label" :icon="shortcut.icon" severity="secondary" text size="small" :aria-label="`${shortcut.label} — ${enseignement.libelle}`" @click="emit('openTeaching', enseignement, shortcut.key)" />
                </div>
              </article>
            </div>
          </template>
        </TabPanel>
      </TabPanels>
    </Tabs>
    <p v-else class="m-0! text-sm text-muted-color">Aucun enseignement disponible pour ce contexte.</p>
  </article>
</template>
