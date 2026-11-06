<script setup>
import AbsencesPeriodesTable from '@/components/Absence/AbsencesPeriodesTable.vue';
import ContexteSelection from '@/components/Structure/ContexteSelection.vue';
import {computed, onMounted, ref, watch} from "vue";
import {SimpleSkeleton, HeaderComponent, ButtonDelete, Kpi} from "@components";
import {useAnneeStore, useUsersStore} from "@stores";
import {getAnneeService, getEtudiantAbsencesService, deleteEtudiantAbsenceService} from "@requests";
import {useRoute, useRouter} from "vue-router";
import {Button} from "primevue";

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

const absences = ref([]);
const totalAbsences = ref(0);
const isLoadingAbsences = ref(true);
const absencesStats = ref([]);
const isLoadingAbsencesStats = ref(true);

onMounted(async () => {
  await getAnnees();
  await getAnnee();
  await getAbsences();
  await getAbsencesStats();
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
  // Récupération de l'id de l'année via l'URL
  try {
    const anneeId = Number.parseInt(String(route.params.anneeId), 10);
    if (!Number.isNaN(anneeId)) {
      annee.value = annees.value.find((item) => item.id === anneeId) || annees.value[0] || {};
    } else {
      annee.value = annees.value[0] || {};
    }
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
  await getAbsences();
  await getAbsencesStats();
});

const getAbsences = async () => {
  try {
    const params = {
      annee: annee.value?.id,
      anneeUniversitaire: anneeUniv.id,
      itemsPerPage: limit.value,
      page: page.value + 1,
    }
    absences.value = await getEtudiantAbsencesService(params, '/administration');
    totalAbsences.value = absences.value?.totalRecords ?? absences.value?.totalItems ?? absences.value?.length ?? 0;
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des absences :", error);
  } finally {
    isLoadingAbsences.value = false;
    console.log(absences.value)
  }
}

const getAbsencesStats = async () => {
  try {
    const params = {
      annee: annee.value?.id,
      anneeUniversitaire: anneeUniv.id,
    }
    absencesStats.value = await getEtudiantAbsencesService(params, '/administration/stats');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des statistiques des absences :", error);
  } finally {
    isLoadingAbsencesStats.value = false;
  }
}

const editAbsence = async (absence) => {
  await router.push({
    name: 'new-absence',
    params: { anneeId: annee?.value?.id },
    query: {
      absenceId: absence?.id,
    },
  });
};

const deleteAbsence = async (absence) => {
  if (!absence?.id) {
    return;
  }

  try {
    await deleteEtudiantAbsenceService(absence.id, '/administration', true);
    await getAbsences();
    await getAbsencesStats();


  } catch (error) {
    console.error("Erreur lors de la suppression de l'absence :", error);
  }
};

const optionChart = {
  responsive: true,
  maintainAspectRatio: true,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
};

const chartData = {
  labels: ['Justifiées', 'Non justifiées'],
  datasets: [
    {
      data: [5, 10],
      backgroundColor: ['#4dc9f6', '#f67019'],
    },
  ],
}

const onPageChange = async event => {
  limit.value = event.rows;
  page.value = event.page;
  await getAbsences();
};
</script>

<template>
  <ContexteSelection titre="Année de travail" :valeur="[annee?.libelle].filter(Boolean).join(' · ')">
    <div class="flex flex-col gap-2"><label for="contexte-annee" class="text-sm font-semibold text-muted-color">Année de formation</label>
      <Select :loading="isLoadingAnnees" :disabled="isLoadingAnnees" input-id="contexte-annee" class="w-full sm:w-60" v-model="annee" option-label="libelle" :options="annees" placeholder="Changer d'année"/>
    </div>
  </ContexteSelection>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <Kpi
      v-for="stat in absencesStats"
      :key="stat.title"
      :label="stat.title"
      :value="stat.value"
      :icon="stat.icon"
      :color="stat.color"
    />
  </div>

  <div class="flex flex-col gap-6">
    <div class="card">
      <div class="flex flex-col md:flex-row justify-between items-start w-full card-header">
        <div>
          <p class="top-card-header">
            Contrôle des présences
          </p>
          <div class="flex flex-col items-start">
            <p class="uppercase text-xs font-bold mb-0! text-muted-color">{{ annee?.libelle }}</p>
            <h2 class="mt-0!">Liste des absences</h2>
          </div>
        </div>
        <SimpleSkeleton v-if="isLoadingAnnees || isLoadingAnnee" class="!w-60 !h-10"></SimpleSkeleton>
        <div v-else class="flex flex-col gap-2">

          <div class="flex justify-end items-center">
            <router-link :to="{ name: 'new-absence', params: { anneeId: annee?.id } }">
              <Button label="Créer une absence" icon="pi pi-plus" @click="getAbsences()" severity="primary"/>
            </router-link>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div>
          <h3 class="m-0 mb-3">Toutes les absences saisies</h3>
          <Message severity="info" icon="pi pi-info-circle" class="w-full flex justify-center" v-if="!isLoadingAbsences && (!absences || absences.length === 0)">
            Aucune absence trouvée pour cette année.
          </Message>
          <AbsencesPeriodesTable v-else :absences="absences" :total-absences="totalAbsences"
            :offset="offset" :limit="limit" :row-options="rowOptions" @page="onPageChange">
            <template #detail-actions="{absence}">
              <ButtonDelete tooltip="Supprimer l'absence" @confirm-delete="deleteAbsence(absence)" />
            </template>
          </AbsencesPeriodesTable>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <section class="w-full grid grid-cols-1 xl:grid-cols-3 gap-6 mt-4">
          <div class="flex flex-col justify-between xl:col-span-2">
            <div>
              <h3 class="m-0 mb-3">Dernière absences saisies</h3>
              <Message severity="info" icon="pi pi-info-circle" class="w-full flex justify-center" v-if="!isLoadingAbsences && (!absences || absences.length === 0)">
                Aucune absence trouvée pour cette année.
              </Message>

            </div>
          </div>

          <div class="flex flex-col justify-between xl:col-span-1">
            <div>
              <h3 class="m-0 mb-3">Répartition par ressource</h3>
            </div>
            <div class="flex flex-col justify-center items-center h-full">
              <Chart type="pie" :data="chartData" :options="optionChart"/>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
