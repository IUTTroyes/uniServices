<script setup>
defineProps({ avancement: { type: Object, default: null } });
</script>

<template>
  <span class="block">
    <span v-if="avancement?.sequence" class="block mb-2">
      <span class="block text-sm font-semibold whitespace-pre-wrap">Séquence<span v-if="avancement.sequence.ordre != null"> {{ avancement.sequence.ordre }}</span> — {{ avancement.sequence.programme }}</span>
      <Tag :value="avancement.etatSequence === 'terminee' ? 'Terminée' : 'En cours'" :severity="avancement.etatSequence === 'terminee' ? 'success' : 'info'" class="mt-1" />
    </span>
    <span v-if="avancement?.repere" class="block text-sm whitespace-pre-wrap">{{ avancement.repere }}</span>
    <span v-else-if="!avancement?.sequence" class="block text-sm text-muted-color">Ajouter un repère…</span>
  </span>
</template>
