<script setup>
import {useSemestreStore} from "@stores";
import {onMounted, ref} from "vue";

const semestreStore = useSemestreStore();
const departementId = localStorage.getItem('departement');
const semestres = ref([]);

onMounted(async () => {
  await semestreStore.getSemestresByDepartement(departementId, );
  semestres.value = semestreStore.semestres;
  console.log(semestres.value);
});
</script>

<template>
  <div v-for="semestre in semestres">{{semestre.libelle}}</div>
</template>

<style scoped>

</style>
