<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  data?: {
    totalDocuments?: number;
    totalCategories?: number;
    favoriteCount?: number;
  };
}>();

const stats = computed(() => ({
  totalDocuments: props.data?.totalDocuments ?? 0,
  totalCategories: props.data?.totalCategories ?? 0,
  favoriteCount: props.data?.favoriteCount ?? 0,
}));
</script>

<template>
  <div class="grid grid-cols-3 gap-3 text-center">
    <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl border border-blue-100 dark:border-blue-900/50">
      <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">
        {{ stats.totalDocuments }}
      </div>
      <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mt-1">
        Documents
      </div>
    </div>

    <div class="p-3 bg-purple-50 dark:bg-purple-950/40 rounded-xl border border-purple-100 dark:border-purple-900/50">
      <div class="text-2xl font-extrabold text-purple-600 dark:text-purple-400">
        {{ stats.totalCategories }}
      </div>
      <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mt-1">
        Catégories
      </div>
    </div>

    <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-100 dark:border-amber-900/50">
      <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">
        {{ stats.favoriteCount }}
      </div>
      <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mt-1">
        Favoris
      </div>
    </div>
  </div>
</template>
