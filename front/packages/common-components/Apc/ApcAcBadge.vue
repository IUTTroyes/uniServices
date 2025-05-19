<script setup>

const props = defineProps({
  ac: {
    type: Object,
    required: true
  }
})
</script>

<template>
<Tag :class="ac.niveau.competence.couleur" class="font-bold">
    {{ ac.code }}
  </Tag>

</template>

<style scoped>

</style>
