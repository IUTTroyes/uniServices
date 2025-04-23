<script setup>
import {computed, onMounted, ref} from "vue";

const props = defineProps({
  isVisible: Boolean,
  etudiantSco: Object,
  etudiantPhoto: String,
})

// Fonction pour copier l'email
const copyToClipboard = (email) => {
  navigator.clipboard.writeText(email).then(() => {
    alert("Email copié dans le presse-papiers !");
  }).catch(() => {
    alert("Échec de la copie de l'email.");
  });
};
</script>

<template>
  <div class="flex flex-row gap-6">
    <div class="flex flex-col gap-2 w-2/3 p-4">
      <h2>Informations de scolarité</h2>
      <p><strong>Numéro étudiant:</strong> {{ props.etudiantSco.etudiant.numeroEtudiant }}</p>
      <p><strong>Numéro INE:</strong> {{ props.etudiantSco.etudiant.ine }}</p>
      <p><strong>Numéro de sécurité sociale:</strong> {{ props.etudiantSco.etudiant.securiteSociale }}</p>
      <p><strong>Numéro de téléphone:</strong> {{ props.etudiantSco.etudiant.telephone }}</p>
    </div>

    <div class="flex flex-col gap-2 w-1/3 p-4 card scol-profil">
      <div class="w-full flex justify-center">
        <img :src="etudiantPhoto" alt="photo de profil" class="rounded-full md:w-1/2 w-full h-auto border-8 border-gray-300 border-opacity-60">
      </div>
      <div class="flex flex-col gap-2 p-4 bg-surface-0 shadow-md dark:bg-surface-800 dark:border-gray-700 rounded-md">
        <div class="text-center font-bold flex justify-center gap-1"><div class="first-letter:uppercase lowercase">{{props.etudiantSco.etudiant.prenom}}</div> <div class="uppercase">{{props.etudiantSco.etudiant.nom}}</div></div>
        <!--    todo: lien direct envoi de mail      -->
        <div class="text-center underline hover:cursor-pointer" @click="copyToClipboard(props.etudiantSco.etudiant.mailUniv)">{{props.etudiantSco.etudiant.mailUniv}} <i class="pi pi-copy"></i></div>
        <hr>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scol-profil {
  background-image: url("@/assets/illu/files.svg");
  background-repeat: no-repeat;
  background-size: 100%;
}
</style>
