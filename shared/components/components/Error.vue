<template>
  <div class="w-full flex justify-center text-red-500">
    <p>{{ message }}</p>
  </div>
</template>

<script setup>
const props = defineProps({
  message: {
    type: String,
    required: true
  }
});
</script>

<style scoped>
/* Add any necessary styles here */
</style>
