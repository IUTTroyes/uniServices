<script setup>

const props = defineProps({
  'tooltip': {
    type: String,
    required: true
  },
  'icon': {
    type: String,
    default: 'pi pi-info'
  }
})
</script>

<template>
  <Button :icon="icon"
          outlined
          severity="info"
          rounded
          v-tooltip.bottom="tooltip"
          class="mr-2"
           />
</template>

<style scoped>

</style>
