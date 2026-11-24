<script setup>
import PageParcauto from '../components/PageParcauto.vue';
import GestionParcauto from '../components/GestionParcauto.vue';
</script>

<template>
  <PageParcauto v-slot="{ parc, actualiser }" titre="Véhicules et équipements" icon="pi pi-car">
    <GestionParcauto v-bind="parc" @actualiser="actualiser" />
  </PageParcauto>
</template>
