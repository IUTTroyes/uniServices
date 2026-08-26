<script setup>

defineProps({
  data: {
    type: Object,
    default: () => ({items: []}),
  },
});
</script>

<template>
  <Timeline :value="data.items" align="left" class="w-full">
    <template #content="slotProps">
      <div class="text-sm leading-4">
        {{ slotProps.item.title }}
      </div>
    </template>
  </Timeline>
</template>

<style scoped>
</style>
