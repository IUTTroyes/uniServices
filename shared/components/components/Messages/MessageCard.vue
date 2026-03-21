<script setup lang="ts">
const props = defineProps({
  'content': {
    type: String,
    required: true
  },
  'type': {
    type: String,
    required: false,
  }
})
</script>

<template>
<div class="w-full bg-white shadow-sm border border-neutral-200 rounded-lg p-6 flex items-center justify-center gap-4">
    <div class="flex-shrink-0">
      <i class="pi pi-info-circle"></i>
    </div>
    <div class="text-sm text-gray-800 leading-relaxed">
      {{content}}
    </div>
  </div>
</template>

<style scoped>

</style>
