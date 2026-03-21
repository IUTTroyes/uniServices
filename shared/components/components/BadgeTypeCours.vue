<script setup lang="ts">

import { computed } from 'vue';

const props = defineProps({
  type: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    default: '',
  },
})

const colorByType = computed(() => {
  switch (props.type) {
    case 'CM':
      return 'typeCm';
    case 'TD':
      return 'typeTd';
    case 'TP':
      return 'typeTp';
    default:
      return 'info';
  }
})

const labelByType = computed(() => {
  switch (props.type) {
    case 'CM':
      return 'Cours Magistral';
    case 'TD':
      return 'Travaux Dirigés';
    case 'TP':
      return 'Travaux Pratiques';
    case 'success':
      return 'Réussite';
    default:
      return 'Inconnu';
  }
})
</script>

<template>
<Tag :value="labelByType"
       :class="`badge-type-cours ${colorByType}`" />
</template>

<style scoped>

</style>
