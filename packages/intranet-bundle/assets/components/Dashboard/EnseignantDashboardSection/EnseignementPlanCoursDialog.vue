<script setup>
defineProps({
  visible: Boolean,
  enseignement: { type: Object, default: null },
  anneeUniversitaire: { type: [Number, String], default: null },
});
const emit = defineEmits(['update:visible']);
</script>

<template>
  <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" modal
          :header="`Plan de cours · ${enseignement?.codeEnseignement || enseignement?.libelle || ''}`"
          class="w-[90vw] md:w-[75vw] lg:w-[50vw]">
  </Dialog>
</template>
