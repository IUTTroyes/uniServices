<script setup>
import PageParcauto from '../components/PageParcauto.vue';
import PlanningParcauto from '../components/PlanningParcauto.vue';
</script>

<template>
  <PageParcauto v-slot="{ parc, actualiser }" titre="Réservations" icon="pi pi-calendar">
    <PlanningParcauto :vehicules="parc.vehicules" :pass-telepeage="parc.passTelepeage" :gestion="parc.gestion" @actualiser="actualiser" />
  </PageParcauto>
</template>
