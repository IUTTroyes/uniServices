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
          text
          size="small"
          severity="secondary"
          v-tooltip.bottom="tooltip"
          :aria-label="tooltip"
           />
</template>

<style scoped>

</style>
