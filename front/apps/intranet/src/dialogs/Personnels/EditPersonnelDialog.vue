<script setup>
const props = defineProps({
  isVisible: Boolean,
  personnel: Object
})

</script>

<template>
  <Dialog header="Edit Personnel" :visible="props.isVisible" :modal="true" :closable="true">
    <p>{{ props.personnel }}</p>
  </Dialog>
</template>

<style scoped>

</style>
