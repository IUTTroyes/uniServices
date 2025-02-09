<script setup>

const props = defineProps({
  'tooltip': {
    type: String,
    required: true
  },
})
</script>

<template>
  <Button icon="pi pi-pencil"
          outlined
          severity="warn"
          rounded
          v-tooltip.bottom="tooltip"
          class="mr-2"
           />
</template>

<style scoped>

</style>
