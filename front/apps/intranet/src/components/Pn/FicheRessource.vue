<script setup>
const props = defineProps({
  enseignement: {
    type: Object,
    required: true
  },
})
</script>

<template>
  {{props.enseignement}}
</template>

<style scoped>

</style>
