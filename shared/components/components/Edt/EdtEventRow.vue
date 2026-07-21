<script setup>
import {computed, onMounted} from 'vue';
import { adjustColor, colorNameToRgb } from '@helpers';

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  selectable: {
    type: Boolean,
    default: false,
  },
  selected: {
    type: Boolean,
    default: false,
  },
  showActionButton: {
    type: Boolean,
    default: false,
  },
  actionTooltip: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['select', 'action']);

const badgeStyle = computed(() => ({
  backgroundColor: adjustColor(colorNameToRgb(props.item?.color), 0.6, 0.1),
}));

const onRowClick = () => {
  if (!props.selectable) {
    return;
  }

  emit('select', props.item);
};

const onActionClick = (event) => {
  event.stopPropagation();
  emit('action', props.item);
};
</script>

<template>
  <div
      class="flex flex-col gap-1 pb-3"
      :class="{ 'cursor-pointer transition-all hover:bg-primary-500/10 rounded-lg px-2 py-2 -mx-2': selectable, 'bg-primary-500/20 hover:bg-primary-500/30 rounded-lg px-2 py-2 -mx-2': selected }"
      @click="onRowClick"
  >
    <div class="flex flex-row items-center gap-2">
      <div class="max-w-40 font-bold text-primary-600 dark:text-primary-300">{{ item.heure }}</div>
      <span class="rounded-lg px-2.5 py-1 text-xs font-semibold dark:text-black" :style="badgeStyle">
      {{ item.groupe }}
    </span>
      <div class="min-w-48 flex-1 font-bold">{{ item.cours }}</div>
    </div>

    <div class="text-muted-color text-sm flex flex-row items-center gap-2">
      <div class="flex items-center gap-1">
        <i class="pi pi-map-marker"></i>
        {{ item.salle }}
      </div>
      <span> - </span>
      <p class="mb-0!">{{ item.intervenant }}</p>
    </div>
    <Button
        v-if="showActionButton"
        icon="pi pi-user"
        size="small"
        severity="primary"
        text
        rounded
        v-tooltip.top="actionTooltip"
        @click="onActionClick"
    />
  </div>
</template>
