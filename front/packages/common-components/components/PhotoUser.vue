<script setup>
import noImage from "@common-images/photos_personnels/noimage.png";
import { computed } from "vue";

const props = defineProps({
  userPhoto: String,
});

const photoSrc = computed(() => {
  const basePath = "/common-images/photos_personnels/";
  return props.userPhoto ? `${basePath}${props.userPhoto}` : noImage;
});
</script>

<template>
  <img :src="photoSrc" alt="photo de profil" class="rounded-full w-full h-auto">
</template>

<style scoped>

</style>
