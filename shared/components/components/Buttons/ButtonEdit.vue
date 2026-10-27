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
          text
          size="small"
          severity="secondary"
          v-tooltip.bottom="tooltip"
          :aria-label="tooltip"
           />
</template>

<style scoped>

</style>
