<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { HeaderComponent, EmptyState } from '@components';

const route = useRoute();
const titre = computed(() => route.meta.titre ?? 'UniFolio');
const lot = computed(() => route.meta.lot);
</script>

<template>
  <HeaderComponent
    :icon="route.meta.icon ?? 'pi pi-folder'"
    :titre="titre"
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  />
  <EmptyState
    icon="pi pi-hammer"
    color="emerald"
    title="Fonctionnalité en cours de construction"
    :description="lot ? `Cette section arrive avec le lot ${lot} de la refonte UniFolio.` : 'Cette section arrive prochainement.'"
  />
</template>
