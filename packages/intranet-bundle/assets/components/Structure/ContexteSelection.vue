<script setup>
defineProps({
  titre: { type: String, default: 'Contexte de travail' },
  valeur: { type: String, default: '' },
});
</script>

<template>
  <section :aria-label="titre" class="card mb-6! overflow-hidden border-l-4! border-l-primary-500!">
    <div class="flex flex-col gap-6 p-5 lg:flex-row lg:items-center lg:justify-between">
      <div class="flex items-start gap-4 lg:max-w-md">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary dark:bg-primary-950/40" aria-hidden="true">
          <i class="pi pi-calendar text-xl"></i>
        </span>
        <div class="min-w-0">
          <p class="m-0! text-xs font-bold uppercase tracking-wider text-muted-color">{{ titre }}</p>
          <p class="mt-1! mb-1! text-xl font-bold text-color" aria-live="polite">{{ valeur || 'Sélectionnez votre contexte' }}</p>
          <p class="m-0! text-sm text-muted-color">Les données et les actions concernent cette sélection.</p>
        </div>
      </div>
      <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap lg:border-l lg:border-surface-200 lg:pl-6 dark:lg:border-surface-700">
        <slot />
      </div>
    </div>
  </section>
</template>
