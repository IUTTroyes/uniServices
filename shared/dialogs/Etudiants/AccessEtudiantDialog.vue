<script setup>
const props = defineProps({
  isVisible: Boolean,
  etudiant: Object
})

</script>

<template>
  <Dialog header="Droits Etudiant" :visible="props.isVisible"  modal :style="{ width: '70vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" dismissable-mask :closable="true">
    <p>{{ props.etudiant }}</p>
  </Dialog>
</template>

<style scoped>

</style>
