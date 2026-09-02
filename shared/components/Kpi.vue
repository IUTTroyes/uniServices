<script setup lang="ts">
import {SimpleSkeleton} from '@components';

const props = withDefaults(defineProps<{
  label: string;
  value: string | number;
  icon: any;
  color: string;
  description?: string;
  loading?: boolean;
}>(), {
  loading: false,
});
</script>

<template>
  <div class="card w-full flex items-center justify-center flex-col">
    <div class="font-bold text-lg card-header text-center">{{ props.label }}</div>
    <div class="flex items-center gap-2 card-body">
      <i :class="[props.icon, `text-${props.color}`]" class="text-3xl!"></i>
      <SimpleSkeleton v-if="props.loading" class="w-1/2" />
      <span v-else :class="`text-${props.color}`" class="text-4xl font-extrabold">{{ props.value }}</span>
    </div>
    <p v-if="props.description" class="text-xs text-gray-600 dark:text-gray-400 mt-1">
      {{ props.description }}
    </p>
  </div>
</template>
