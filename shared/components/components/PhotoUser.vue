<script setup>
import noImage from "@common-images/photos_personnels/noimage.png";
import { ref, watch } from "vue";

const props = defineProps({
  userPhoto: String,
  type: {
    type: String,
    default: "personnels",
  },
});

const photoSrc = ref(noImage);

watch(
    [() => props.userPhoto, () => props.type],
    ([newPhoto, newType]) => {
      if (!newPhoto) {
        photoSrc.value = noImage;
        return;
      }

      if (newPhoto.startsWith("http") || newPhoto.startsWith("/")) {
        photoSrc.value = newPhoto;
        return;
      }

      const folder = newType === "etudiants" ? "photos_etudiants" : "photos_personnels";
      const basePath = `/common-images/${folder}/`;

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
