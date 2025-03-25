<script setup>
import { ref, defineProps } from 'vue';

const props = defineProps({
  enseignement: {
    type: Object,
    required: true
  },
})
</script>

<template>
  <ul>
    <li>Parcours </li>
    <li>Semestres</li>
    <li>Suspendue = {{enseignement.suspendu}}</li>
    <li>Compétence(s) ciblée(s) = </li>
    <li class="flex gap-4">Apprentissages critiques = <span v-for="ac in enseignement.apcApprentissageCritique">{{ac.code}}</span></li>
    <li>Saé cocnernée(s) (link)</li>
    <li>Pré-requis = {{enseignement.preRequis ?? 'Aucune ressource prérequise'}}</li>
    <li>Description = <pre>{{enseignement.description ?? 'Aucune description disponible'}}</pre></li>
    <li>Mots clés = {{enseignement.motsCles}}</li>
    <li>Heures = {{enseignement.heures}}</li>
    <li>Nb notes = {{enseignement.nbNotes}}</li>
  </ul>
</template>

<style scoped>

</style>
