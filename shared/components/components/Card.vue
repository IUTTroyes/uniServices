<script setup>

const props = defineProps({
  title: {
    type: String,
    required: true
  }
})
</script>

<template>
  <div class="card">
    <div class="font-semibold text-xl mb-4">{{ title }}</div>
    <slot />
  </div>
</template>


<style scoped>

</style>
