<script setup>
defineProps({ items: { type: Array, required: true } });
</script>

<template>
  <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
    <div v-for="item in items" :key="item.label" class="rounded-xl border border-surface-200 dark:border-surface-700 p-4 bg-surface-50 dark:bg-surface-800">
      <p class="m-0! text-sm text-muted-color">{{ item.label }}</p>
      <p class="mt-2! mb-0! text-2xl font-semibold flex items-center gap-2">
        <i v-if="item.icon" :class="[item.icon, 'text-primary text-base!']" aria-hidden="true" />
        {{ item.value }}
      </p>
    </div>
  </div>
</template>
