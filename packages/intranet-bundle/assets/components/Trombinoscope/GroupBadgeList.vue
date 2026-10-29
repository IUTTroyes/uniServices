<script setup lang="ts">
import type { GroupesMap } from '@/types/trombinoscope';

interface Props {
  groupes?: GroupesMap;
  maxBadgeWidth?: string;
}

withDefaults(defineProps<Props>(), {
  maxBadgeWidth: 'max-w-[130px]',
});

const groupTypes = [
  { key: 'CM', label: 'CM', bg: 'bg-blue-100/80 text-blue-900 border-blue-300', text: 'text-blue-700' },
  { key: 'TD', label: 'TD', bg: 'bg-emerald-100/80 text-emerald-900 border-emerald-300', text: 'text-emerald-700' },
  { key: 'TP', label: 'TP', bg: 'bg-amber-100/80 text-amber-950 border-amber-300', text: 'text-amber-800' },
] as const;
</script>

<template>
  <div class="flex flex-wrap items-center justify-center gap-1.5">
    <template v-for="t in groupTypes" :key="t.key">
      <span
        v-for="grp in (groupes?.[t.key] || [])"
        :key="grp"
        :class="[
          'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold border shadow-2xs cursor-default truncate',
          t.bg,
          maxBadgeWidth
        ]"
        v-tooltip.top="`Groupe ${t.label} : ${grp}`"
      >
        <span :class="['text-[10px] font-black uppercase flex-shrink-0', t.text]">
          {{ t.label }}
        </span>
        <span class="truncate">{{ grp }}</span>
      </span>
    </template>

    <span
      v-if="!groupes?.CM?.length && !groupes?.TD?.length && !groupes?.TP?.length"
      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200"
    >
      <i class="pi pi-info-circle text-[11px] text-gray-500" />
      <span>Aucun groupe</span>
    </span>
  </div>
</template>

