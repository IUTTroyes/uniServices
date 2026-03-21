<script setup>
import Message from 'primevue/message'
import { computed, useSlots } from 'vue'

const props = defineProps({
  severity: { type: String, default: 'info' },
  icon: { type: String, default: null },
  message: { type: String, default: null },
  closable: { type: Boolean, default: true },
})
const computedIcon = computed(() => {
  if (props.icon) {
    return props.icon; // Si une icône est fournie, l'utiliser
  }

  const severityIcons = {
    info: 'pi pi-info-circle',
    success: 'pi pi-check-circle',
    warning: 'pi pi-exclamation-triangle',
    error: 'pi pi-times-circle',
  };

  return severityIcons[props.severity] || 'pi pi-info-circle';
});

const hasSlotContent = useSlots().default?.().length > 0;
</script>

<template>
  <Message
      :closable="closable"
      :severity="severity"
      :icon="computedIcon">
    <template v-if="hasSlotContent">
      <slot />
    </template>
    <template v-else>
      {{ message }}
    </template>
  </Message>
</template>

<style scoped>

</style>
