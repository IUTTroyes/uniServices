<script setup>
import noImage from "@common-images/photos_personnels/noimage.png";
import { ref, watch } from "vue";

const props = defineProps({
  userPhoto: String,
});

const photoSrc = ref(noImage);

watch(
    () => props.userPhoto,
    (newPhoto) => {
      const basePath = "/common-images/photos_personnels/";
      if (!newPhoto) {
        photoSrc.value = noImage;
        return;
      }

      const img = new Image();
      img.src = `${basePath}${newPhoto}`;
      img.onload = () => {
        photoSrc.value = img.src;
      };
      img.onerror = () => {
        photoSrc.value = noImage;
      };
    },
    { immediate: true }
);
</script>

<template>
  <img :src="photoSrc" alt="photo de profil" class="rounded-full">
</template>

<style scoped>

</style>
