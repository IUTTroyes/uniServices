<script setup>

const props = defineProps({
  ac: {
    type: Object,
    required: true
  }
})
</script>

<template>
<Tag :class="ac.apcNiveau.apcCompetence.couleur" class="font-bold">
    {{ ac.code }}
  </Tag>

</template>

<style scoped>

</style>
