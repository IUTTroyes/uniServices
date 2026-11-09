<script setup>
import { ref } from 'vue';
import EmptyState from './EmptyState.vue';

const props = defineProps({
  message: {
    type: String,
    default: 'Une erreur est survenue. Veuillez réessayer plus tard ou contacter les administrateurs du site.',
  },
  compact: Boolean,
  debugMessage: {
    type: String,
    default: '',
  },
});

const copied = ref(false);

const copyDebugMessage = async () => {
  if (!props.debugMessage) {
    return;
  }

  try {
    await navigator.clipboard.writeText(props.debugMessage);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2000);
  } catch (error) {
    copied.value = false;
  }
};
</script>

<template>
  <EmptyState title="Erreur" icon="pi pi-exclamation-triangle" color="rose" :compact="compact">
    <p role="alert" class="m-0">{{ message }}</p>
    <template #action>
      <div class="flex w-full flex-col items-center gap-4">
        <a href="mailto:intranet.iut-troyes@univ-reims.fr" class="text-sm underline">
          Contacter les administrateurs du site
          <i class="pi pi-external-link text-xs" aria-hidden="true"></i>
        </a>
        <div v-if="debugMessage" class="w-full max-w-2xl rounded-xl border border-surface-300 dark:border-surface-700 bg-surface-0 dark:bg-surface-900 p-4 mt-2">
          <div class="flex items-center justify-between gap-3 mb-2">
            <p class="m-0 text-sm font-semibold text-color">Détail technique (debug)</p>
            <Button
                size="small"
                severity="secondary"
                outlined
                icon="pi pi-copy"
                :label="copied ? 'Copié' : 'Copier'"
                @click="copyDebugMessage"
            />
          </div>
          <pre class="m-0 text-xs whitespace-pre-wrap break-words max-h-48 overflow-auto">{{ debugMessage }}</pre>
        </div>
        <Button label="Retour" severity="danger" size="small" @click="$router.go(-1)" />
      </div>
    </template>
  </EmptyState>
</template>
