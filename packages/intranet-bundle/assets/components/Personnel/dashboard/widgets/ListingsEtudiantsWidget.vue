<script setup>
import ListingsEtudiants from '../../../Listings/ListingsEtudiants.vue';

defineProps({
  data: {
    type: Object,
    default: () => ({}),
  },
  widget: {
    type: Object,
    default: () => ({}),
  },
});
</script>

<template>
  <div class="listings-etudiants-widget">
    <ListingsEtudiants :compact="true" />
  </div>
</template>

<style scoped>
.listings-etudiants-widget {
  min-height: 140px;
}
</style>
