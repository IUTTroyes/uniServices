<script setup>
import {computed, onMounted, ref, watch} from "vue";
import {SimpleSkeleton, HeaderComponent, Kpi} from "@components";
import {useAnneeStore, useUsersStore} from "@stores";
import {getAnneeService} from "@requests";
import {Button} from "primevue";
import {useRoute, useRouter} from "vue-router";

const route = useRoute();
const router = useRouter();
const hasError = ref(false);
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const usersStore = useUsersStore();
const departementId = usersStore.departementDefaut.id;
const anneeStore = useAnneeStore();
const annees = ref([]);
const annee = ref({});
const isLoadingAnnee = ref(true);
const isLoadingAnnees = ref(false);
const page = ref(0);
const rowOptions = [5, 10, 20, 50];
const limit = ref(rowOptions[0]);
const offset = computed(() => limit.value * page.value);

onMounted(async () => {
  await getAnnees();
  await getAnnee();
});

const getAnnees = async () => {
  if (anneeStore.annees && Array.isArray(anneeStore.annees) && anneeStore.annees.length > 0) {
    annees.value = anneeStore.annees;
    return;
  }
  try {
    isLoadingAnnees.value = true;
    const params = {
      departement: departementId,
      actif: true,
    };
    await anneeStore.getAnneesDepartement(params);
    annees.value = Array.isArray(anneeStore.annees) ? anneeStore.annees : [];
  } catch (error) {
    console.error("Erreur lors de la récupération des années :", error);
    hasError.value = true;
  } finally {
    isLoadingAnnees.value = false;
  }
};

const getAnnee = async () => {
  isLoadingAnnee.value = true;
  hasError.value = false;
  try {
    const anneeId = route.params.anneeId;
    annee.value = await getAnneeService(anneeId);
    await anneeStore.setSelectedAnnee(annee.value);
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération de l'année :", error);
  } finally {
    isLoadingAnnee.value = false;
  }
};

watch(annee, async (newAnnee, oldAnnee) => {
  if (newAnnee?.id === oldAnnee?.id) return;

  if (newAnnee?.id && String(route.params.anneeId) !== String(newAnnee.id)) {
    await router.replace({
      name: route.name,
      params: {
        ...route.params,
        anneeId: String(newAnnee.id),
      },
      query: route.query,
    });
  }

  await anneeStore.setSelectedAnnee(newAnnee);
  page.value = 0;

  // ICI: appeler les requêtes qui récupères les datas
});

const onPageChange = async event => {
  limit.value = event.rows;
  page.value = event.page;
  await getAbsences();
};
</script>

<template>
  <HeaderComponent
      icon="pi pi-calendar"
      titre="Suivi du pointage des présences"
      description="Vérifiez que l'appel a bien été réalisée sur l'ensemble des cours"
  />

<!--  <div class="flex justify-around items-center mb-12">-->
<!--    <div v-for="stat in absencesStats" :key="stat.title" class="card w-1/5 flex items-center justify-center flex-col">-->
<!--      <Kpi-->
<!--          :label="stat.title"-->
<!--          :value="stat.value"-->
<!--          :icon="stat.icon"-->
<!--          :color="stat.color"-->
<!--      />-->
<!--    </div>-->
<!--  </div>-->

  <div class="flex flex-col gap-6">
    <div class="card">
      <div class="flex flex-col md:flex-row justify-between items-start w-full card-header">
        <div>
          <p class="top-card-header">
            Contrôle de l'appel
          </p>
          <div class="flex flex-col items-start">
            <p class="uppercase text-xs font-bold mb-0! text-muted-color">
              année
            </p>
            <h2 class="mt-0!">
              {{ annee?.libelle }}
            </h2>
          </div>
        </div>
        <SimpleSkeleton v-if="isLoadingAnnees || isLoadingAnnee" class="!w-60 !h-10"></SimpleSkeleton>
        <div v-else class="flex flex-col gap-2">
          <div class="flex flex-col justify-center items-end filters-card-header">
            <div class="text-sm uppercase font-semibold">Changer d'année</div>
            <Select class="w-60" v-model="annee" option-label="libelle" :options="annees">
              <template #value>
                {{ annee?.libelle || "Changer d'année" }}
              </template>
            </Select>
          </div>
        </div>
      </div>
      <div class="card-body">

      </div>
    </div>


  </div>
</template>

<style scoped>

</style>
