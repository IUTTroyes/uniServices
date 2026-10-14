<script setup>
import { computed } from 'vue';
import { getApplicationMeta } from '@utils';

const props = defineProps({
  app: {
    type: [String, Object],
    required: true,
  },
  size: {
    type: String,
    default: 'sm', // 'xs' | 'sm' | 'md'
    validator: (val) => ['xs', 'sm', 'md'].includes(val),
  },
  showIcon: {
    type: Boolean,
    default: false,
  },
  showDot: {
    type: Boolean,
    default: false,
  },
  rounded: {
    type: Boolean,
    default: false,
  },
});

const meta = computed(() => getApplicationMeta(props.app));

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'text-[10px] px-1.5 py-0.5 gap-1';
    case 'md':
      return 'text-xs px-2.5 py-1 gap-2';
    case 'sm':
    default:
      return 'text-[11px] px-2 py-0.5 gap-1.5';
  }
});

const iconSizeClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'text-[9px]';
    case 'md':
      return 'text-xs';
    case 'sm':
    default:
      return 'text-[10px]';
  }
});
</script>

<template>
  <Chip :label="meta.name" :class="[sizeClasses,
      meta.badgeClasses]">
    <template #icon>
      <i v-if="props.showIcon && meta.icon" :class="[meta.icon, iconSizeClasses, 'shrink-0']" />
    </template>
  </Chip>
</template>
