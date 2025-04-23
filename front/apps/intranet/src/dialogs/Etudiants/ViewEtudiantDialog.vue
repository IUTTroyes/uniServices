<script setup>
import EtudiantProfil from '@components/components/Etudiant/ProfilEtudiant.vue'
import noImage from "@images/photos_etudiants/noimage.png";
import {onMounted, ref} from "vue";

const props = defineProps({
  isVisible: Boolean,
  etudiantSco: Object
})

const etudiantPhoto = ref(noImage);

onMounted(() => {
  if (props.etudiantSco?.etudiant.photoName) {
    const photoPath = new URL(
        `@common-images/photos_etudiants/${props.etudiantSco.etudiant.photoName}`,
        import.meta.url
    ).href;

    fetch(photoPath)
        .then((response) => {
          if (response.ok) {
            etudiantPhoto.value = photoPath;
          }
        })
        .catch(() => {
          etudiantPhoto.value = noImage;
        });
  }
});
</script>

<template>
  <Dialog :header="`Scolarité de ${props.etudiantSco?.etudiant.prenom} ${props.etudiantSco?.etudiant.nom}`" :visible="props.isVisible" modal :style="{ width: '70vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" dismissable-mask :closable="true">
    <EtudiantProfil :etudiantSco="props.etudiantSco" :isVisible="props.isVisible" :etudiantPhoto="etudiantPhoto" />
  </Dialog>
</template>

<style scoped>
</style>
