<script setup>
import PageParcauto from '../components/PageParcauto.vue';
import PilotageParcauto from '../components/PilotageParcauto.vue';
</script>

<template>
  <PageParcauto v-slot="{ parc }" titre="Pilotage" icon="pi pi-chart-bar">
    <PilotageParcauto v-if="parc.gestion" :vehicules="parc.vehicules" />
  </PageParcauto>
</template>
