<script setup>
import { computed, onMounted, ref } from "vue";
import { getEtudiantScolaritesService } from "@requests";
import { useToast } from "primevue/usetoast";

const toast = useToast();

const props = defineProps({
  isVisible: Boolean,
  etudiantSco: Object,
  etudiantPhoto: String,
});

const loadingScolarites = ref(true);
const etudiantScolarites = ref([]);
const activeTab = ref(null);

const copyToClipboard = (email) => {
  navigator.clipboard.writeText(email).then(() => {
    toast.add({
      severity: "success",
      summary: "Succès",
      detail: "Adresse email copiée",
      life: 5000,
    });
  }).catch(() => alert("Échec de la copie."));
};

const getEtudiantScolarites = async () => {
  loadingScolarites.value = true;
  try {
    const response = await getEtudiantScolaritesService(props.etudiantSco.etudiant.id);
    etudiantScolarites.value = response.member;
    etudiantScolarites.value.sort((a, b) => {
      const anneeA = a.structureAnneeUniversitaire.annee;
      const anneeB = b.structureAnneeUniversitaire.annee;
      return anneeB - anneeA;
    });

    // Définir la première tab comme active
    activeTab.value = etudiantScolarites.value[0]?.structureAnneeUniversitaire.libelle || null;
  } catch (error) {
    console.error("Erreur lors de la récupération :", error);
  } finally {
    loadingScolarites.value = false;
  }
};

const changeTab = (scolarite) => {
  activeTab.value = scolarite.structureAnneeUniversitaire.libelle;
};

onMounted(async () => {
  await getEtudiantScolarites();
});
</script>

<template>
  <div class="flex flex-row gap-6">
    <div class="flex flex-col gap-2 w-2/3 p-4">
      <Tabs v-model="activeTab" scrollable>
        <TabList>
          <Tab
            v-for="scolarite in etudiantScolarites"
            :key="scolarite.structureAnneeUniversitaire.libelle"
            :value="scolarite.structureAnneeUniversitaire.libelle"
            @click="changeTab(scolarite)"
          >
            {{ scolarite.structureAnneeUniversitaire.libelle }}
          </Tab>
        </TabList>
      </Tabs>

      <div v-if="activeTab">
        <div class="flex flex-row justify-between gap-2 w-full h-full">
          <div
            v-for="scolarite_semestre in etudiantScolarites.find(s => s.structureAnneeUniversitaire.libelle === activeTab)?.scolarite_semestre"
            class="card w-full h-full"
          >
            <div class="font-bold text-lg">
              {{ scolarite_semestre.structure_semestre.annee.libelle }} -
              <span class="text-muted-color font-normal">{{ scolarite_semestre.structure_semestre.libelle }}</span>
            </div>
            <hr>
          </div>
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-2 w-1/3 p-4 card scol-profil">
      <div class="w-full flex justify-center">
        <img :src="etudiantPhoto" alt="photo de profil" class="rounded-full md:w-1/2 w-full h-auto border-8 border-gray-300 border-opacity-60">
      </div>
      <div class="flex flex-col gap-2 p-4 bg-surface-0 shadow-md dark:bg-surface-800 dark:border-gray-700 rounded-md">
        <div class="text-center font-bold flex justify-center gap-1">
          <div class="first-letter:uppercase lowercase">{{ props.etudiantSco.etudiant.prenom }}</div>
          <div class="uppercase">{{ props.etudiantSco.etudiant.nom }}</div>
        </div>
        <div class="text-center underline hover:cursor-pointer" @click="copyToClipboard(props.etudiantSco.etudiant.mailUniv)">
          {{ props.etudiantSco.etudiant.mailUniv }} <i class="pi pi-copy"></i>
        </div>
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
