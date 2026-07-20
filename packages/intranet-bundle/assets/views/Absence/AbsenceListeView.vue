<script setup>
import { onMounted, ref, watch } from "vue";
import {SimpleSkeleton, HeaderComponent} from "@components";
import {useAnneeStore, useAnneeUnivStore, useSemestreStore, useUsersStore} from "@stores";
import {getAnneeService, getSemestresService, getEtudiantAbsencesService} from "@requests";
import {useRoute, useRouter} from "vue-router";
import {Button} from "primevue";

const route = useRoute();
const router = useRouter();
const hasError = ref(false);
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const usersStore = useUsersStore();
const departementId = usersStore.departementDefaut.id;
const semestreStore = useSemestreStore();
const anneeStore = useAnneeStore();
const semestres = ref([]);
const isLoadingSemestres = ref(true);
const semestre = ref({});
const annees = ref([]);
const annee = ref({});
const isLoadingAnnee = ref(true);
const isLoadingAnnees = ref(false);

const absences = ref([]);
const isLoadingAbsences = ref(true);

const resolveSemestreSelection = () => {
  const semestreIdFromQuery = route.query.semestreId;
  const semestreFromQuery = semestres.value.find(s => String(s.id) === String(semestreIdFromQuery));
  if (semestreFromQuery) {
    return semestreFromQuery;
  }

  const semestreFromStore = semestres.value.find(s => String(s.id) === String(semestreStore.semestre?.id));
  if (semestreFromStore) {
    return semestreFromStore;
  }

  return semestres.value.find(s => s.actif) || semestres.value[0] || {};
};

onMounted(async () => {
  await getAnnees();
  await getAnnee();
  await getSemestres();
  semestre.value = resolveSemestreSelection();
  semestreStore.setSelectedSemestre(semestre.value);
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

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  hasError.value = false;
  try {
    const params = {
      annee: annee.value.id,
    };
    semestres.value = await getSemestresService(params, '/mini');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des semestres :", error);
  } finally {

    isLoadingSemestres.value = false;
  }
};

watch(() => semestreStore.semestre, (newSemestre) => {
  semestre.value = newSemestre;
});

// watcher pour relancer getGroupes quand semestre change
watch(semestre, async (newSemestre, oldSemestre) => {
  if (newSemestre?.id !== oldSemestre?.id) {
    await getAbsences();
  }
});

// watcher pour relancer getSemestres quand annee change
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

  await getSemestres();
  // Changer automatiquement de semestre lors d'un changement d'année
  semestre.value = resolveSemestreSelection();
  semestreStore.setSelectedSemestre(semestre.value);

  await anneeStore.setSelectedAnnee(newAnnee)
});

const getAbsences = async () => {
  try {
    const params = {
      semestre: semestre.value.id,
      anneeUniversitaire: anneeUniv.id,
    }
    absences.value = await getEtudiantAbsencesService(params, '/administration');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des absences :", error);
  } finally {
    isLoadingAbsences.value = false;
  }
}

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
</script>

<template>
  <HeaderComponent
      icon="pi pi-calendar"
      titre="Absences"
      description="Gérez les absences et les justificatifs"
  />

  <div class="flex justify-around items-center mb-12">
    <div class="card w-1/5 flex items-center justify-center flex-col">
      <div class="font-bold text-lg card-header text-center">Total d'absences</div>
      <div class="flex items-center gap-2 card-body">
        <i class="pi pi-list text-yellow-500 text-3xl!"></i>
        <SimpleSkeleton v-if="isLoadingAbsences" class="w-1/2"/>
        <span v-else class="text-yellow-500 text-4xl font-extrabold">12</span>
      </div>
    </div>
    <div class="card w-1/5 flex items-center justify-center flex-col">
      <div class="font-bold text-lg card-header text-center">Justifiées</div>
      <div class="flex items-center gap-2 card-body">
        <i class="pi pi-check-circle text-green-500 text-3xl!"></i>
        <span class="text-green-500 text-4xl font-extrabold">5</span>
      </div>
    </div>
    <div class="card w-1/5 flex items-center justify-center flex-col">
      <div class="font-bold text-lg card-header text-center">Non justifiées</div>
      <div class="flex items-center gap-2 card-body">
        <i class="pi pi-times-circle text-red-500 text-3xl!"></i>
        <span class="text-red-500 text-4xl font-extrabold">10</span>
      </div>
    </div>
    <div class="card w-1/5 flex items-center justify-center flex-col">
      <div class="font-bold text-lg card-header text-center">Étudiants concernés</div>
      <div class="flex items-center gap-2 card-body">
        <i class="pi pi-users text-blue-500 text-3xl!"></i>
        <span class="text-blue-500 text-4xl font-extrabold">6</span>
      </div>
    </div>
  </div>

  <div class="flex flex-col gap-6">
    <div class="card">
      <div class="flex flex-col md:flex-row justify-between items-start w-full card-header">
        <div>
          <p class="top-card-header">
            Contrôle des présences
          </p>
          <div class="flex flex-col items-start">
            <p class="uppercase text-xs font-bold mb-0! text-muted-color">
              semestre
            </p>
            <h2 class="mt-0!">
              {{semestre.libelle}}
            </h2>
          </div>
        </div>
        <SimpleSkeleton v-if="isLoadingAnnees || isLoadingSemestres" class="!w-60 !h-10"></SimpleSkeleton>
        <div v-else class="flex flex-col gap-2">
          <div class="flex gap-4 justify-end">
            <Select class="w-60" v-model="annee" option-label="libelle" :options="annees">
              <template #value>
                {{ annee?.libelle || "Changer d'année" }}
              </template>
            </Select>
            <Select class="w-60" v-model="semestre" option-label="libelle" :options="semestres" placeholder="Changer de semestre"/>
          </div>
          <div class="flex justify-end items-center">
            <router-link :to="{ name: 'new-absence', params: { anneeId: annee?.id }, query: { semestreId: semestre?.id } }">
              <Button label="Créer une absence" icon="pi pi-plus" @click="getAbsences()" severity="primary"/>
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <section class="w-full grid grid-cols-1 xl:grid-cols-2 gap-6 mt-4">
          <div class="flex flex-col justify-between xl:col-span-1">
            <div>
              <h3 class="m-0 mb-3">Dernière absences saisies</h3>
              <Message severity="info" icon="pi pi-info-circle" class="w-full flex justify-center" v-if="!isLoadingAbsences && (!absences || absences.length === 0)">
                Aucune absence trouvée pour ce semestre.
              </Message>
              <DataTable
                  v-else
                  :value="absences"
                  striped-rows
                  class="w-full"
              >
                <Column field="scolariteSemestre.scolarite.etudiant.display" header="étudiant" />
              </DataTable>
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
