<script setup>

const props = defineProps({
  competence: {
    type: Object,
    required: true
  }
})
</script>

<template>
<Tag :class="competence.couleur" class="font-bold">
    {{ competence.nomCourt }}
  </Tag>

</template>

<style scoped>

</style>
