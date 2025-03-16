<script setup>
const props = defineProps({
  isVisible: Boolean,
  etudiant: Object
})

</script>

<template>
  <Dialog header="Edit Etudiant" :visible="props.isVisible" :modal="true" :closable="true">
    <p>{{ props.etudiant }}</p>
  </Dialog>
</template>

<style scoped>

</style>
