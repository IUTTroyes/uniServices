<script setup>
import {computed, onMounted, ref, watch} from "vue";
import {SimpleSkeleton, HeaderComponent, EdtEventRow, ButtonInfo, ButtonEdit, ButtonDelete} from "@components";
import {useAnneeStore, useSemestreStore, useUsersStore} from "@stores";
import {getAnneeService, getSemestresService, getEtudiantAbsencesService, deleteEtudiantAbsenceService} from "@requests";
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
const page = ref(0);
const rowOptions = [5, 10, 20, 50];

const limit = ref(rowOptions[0]);
const offset = computed(() => limit.value * page.value);

const absences = ref([]);
const totalAbsences = ref(0);
const isLoadingAbsences = ref(true);
const absencesStats = ref([]);
const isLoadingAbsencesStats = ref(true);
const showDetailsDialog = ref(false);
const selectedEpisode = ref(null);

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
    await getAbsencesStats();
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
      itemsPerPage: limit.value,
      page: page.value + 1,
    }
    absences.value = await getEtudiantAbsencesService(params, '/administration');
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
      semestre: semestre.value.id,
      anneeUniversitaire: anneeUniv.id,
    }
    absencesStats.value = await getEtudiantAbsencesService(params, '/administration/stats');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des statistiques des absences :", error);
  } finally {
    totalAbsences.value = absencesStats.value.find(stat => stat.title?.toLowerCase().includes('total'))?.value || 0;
    isLoadingAbsencesStats.value = false;
  }
}

const formatEventForRow = (event) => {
  const debutEvent = event?.debut
      ? new Date(event.debut).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
      : '--:--';
  const finEvent = event?.fin
      ? new Date(event.fin).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
      : '--:--';

  return {
    id: event?.id,
    heure: `${debutEvent} - ${finEvent}`,
    groupe: event?.libGroupe || event?.codeGroupe || '-',
    cours: event?.codeModule ? `${event.codeModule} - ${event?.libModule || ''}` : (event?.libModule || event),
    salle: event?.salle || '-',
    color: event?.couleur,
    intervenant: event?.intervenant || event?.personnel?.display || '-',
  };
};

const formatEpisodePeriod = (absence) => {
  if (!absence?.dateDebut) return '-';

  const dateDebut = new Date(absence.dateDebut);
  const dateFin = absence?.dateFin ? new Date(absence.dateFin) : dateDebut;
  const sameDay = absence.dateDebut === absence.dateFin;

  if (sameDay) {
    return dateDebut.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
  }

  const memeMoisEtAnnee = dateDebut.getMonth() === dateFin.getMonth() && dateDebut.getFullYear() === dateFin.getFullYear();
  if (memeMoisEtAnnee) {
    return `${dateDebut.toLocaleDateString('fr-FR', { day: '2-digit' })}-${dateFin.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })}`;
  }

  return `${dateDebut.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })} - ${dateFin.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })}`;
};

const getEpisodeSummary = (absence) => {
  const joursCount = absence?.joursCount || 0;
  const creneauxCount = absence?.creneauxCount || 0;
  const mode = absence?.mode || 'episode';

  if (mode === 'flat') {
    const event = absence?.events?.[0];
    const module = event?.codeModule ? `${event.codeModule} - ${event?.libModule || ''}` : (event?.libModule || '-');
    return `${creneauxCount} créneau${creneauxCount > 1 ? 'x' : ''} - ${module}`;
  }

  return `${joursCount} jour${joursCount > 1 ? 's' : ''}, ${creneauxCount} créneau${creneauxCount > 1 ? 'x' : ''}`;
};

const detailAbsences = computed(() => selectedEpisode.value?.absences || []);

const openDetailsModal = (episode) => {
  selectedEpisode.value = episode;
  showDetailsDialog.value = true;
};

const formatLifecycleDate = (value) => {
  if (!value) {
    return '-';
  }

  return new Date(value).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const editAbsence = async (absence) => {
  await router.push({
    name: 'new-absence',
    params: { anneeId: annee?.value?.id },
    query: {
      ...route.query,
      semestreId: semestre?.value?.id,
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

    if (selectedEpisode.value?.id) {
      const refreshedEpisode = absences.value.find(item => item.id === selectedEpisode.value.id);
      if (refreshedEpisode) {
        selectedEpisode.value = refreshedEpisode;
      } else {
        showDetailsDialog.value = false;
        selectedEpisode.value = null;
      }
    }
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
  <HeaderComponent
      icon="pi pi-calendar"
      titre="Absences"
      description="Gérez les absences et les justificatifs"
  />

  <div class="flex justify-around items-center mb-12">
    <div v-for="stat in absencesStats" :key="stat.title" class="card w-1/5 flex items-center justify-center flex-col">
      <div class="font-bold text-lg card-header text-center">{{ stat.title }}</div>
      <div class="flex items-center gap-2 card-body">
        <i :class="[stat.icon, `text-${stat.color}`]" class="text-3xl!"></i>
        <SimpleSkeleton v-if="isLoadingAbsencesStats" class="w-1/2"/>
        <span v-else :class="`text-${stat.color}`" class="text-4xl font-extrabold">{{ stat.value }}</span>
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
      <div class="card-body">
        <div>
          <h3 class="m-0 mb-3">Toutes les absences saisies</h3>
          <Message severity="info" icon="pi pi-info-circle" class="w-full flex justify-center" v-if="!isLoadingAbsences && (!absences || absences.length === 0)">
            Aucune absence trouvée pour ce semestre.
          </Message>
          <DataTable
              v-else
              :value="absences"
              lazy
              striped-rows
              class="w-full"
              paginator
              :first="offset"
              :rows="limit"
              :rowsPerPageOptions="rowOptions"
              :totalRecords="totalAbsences"
              @page="onPageChange($event)"
              @update:rows="limit = $event"
          >
            <Column header="Étudiant">
              <template #body="slotProps">
                {{ slotProps.data.etudiantDisplay || '-' }}
              </template>
            </Column>
            <Column>
              <template #header>
                <span class="font-semibold">
                Période
                </span>
              </template>
              <template #body="slotProps">
                <div class="flex flex-col gap-2">
                  <div class="font-semibold">
                    {{ formatEpisodePeriod(slotProps.data) }}
                  </div>
                  <small class="text-muted-color">
                    {{ getEpisodeSummary(slotProps.data) }}
                  </small>
                </div>
              </template>
            </Column>
            <Column field="justifiee" header="Justifiée">
              <template #body="slotProps">
                <Badge v-if="slotProps.data.justifiee" severity="success">Oui</Badge>
                <Badge v-else severity="danger">Non</Badge>
              </template>
            </Column>
            <Column>
              <template #header>
                <span class="font-semibold">
                Actions
                </span>
              </template>
              <template #body="slotProps">
                <ButtonInfo tooltip="Voir les détails" @click="openDetailsModal(slotProps.data)" />

              </template>
            </Column>
            <template #footer> {{ totalAbsences }} résultat(s).</template>
          </DataTable>

          <Dialog
              header="Détail de la période"
              :visible="showDetailsDialog"
              modal
              dismissable-mask
              :style="{ width: '90vw' }"
              :breakpoints="{ '1199px': '85vw', '575px': '95vw' }"
              @update:visible="showDetailsDialog = $event"
          >
            <div class="flex flex-col gap-4">
              <div>
                <div class="font-semibold">{{ selectedEpisode?.etudiantDisplay || '-' }}</div>
                <div class="font-semibold">{{ formatEpisodePeriod(selectedEpisode) }}</div>
                <small class="text-muted-color">{{ getEpisodeSummary(selectedEpisode) }}</small>
              </div>
              <DataTable :value="detailAbsences" striped-rows class="w-full">
                <Column header="Événement">
                  <template #body="slotProps">
                    <EdtEventRow :item="formatEventForRow(slotProps.data.event)" />
                  </template>
                </Column>
                <Column header="Saisie par">
                  <template #body="slotProps">
                    {{ slotProps.data.personnelDisplay || '-' }}
                  </template>
                </Column>
                <Column header="Horodatage">
                  <template #body="slotProps">
                    <div class="flex flex-col gap-1">
                      <small>Saisie le : {{ formatLifecycleDate(slotProps.data.created) }}</small>
                      <small v-if="formatLifecycleDate(slotProps.data.created) !== formatLifecycleDate(slotProps.data.updated)">Modifiée : {{ formatLifecycleDate(slotProps.data.updated) }}</small>
                    </div>
                  </template>
                </Column>
                <Column header="Actions">
                  <template #body="slotProps">
                    <ButtonDelete tooltip="Supprimer l'absence" @confirm-delete="deleteAbsence(slotProps.data)" />
                  </template>
                </Column>
              </DataTable>
            </div>
          </Dialog>
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
                Aucune absence trouvée pour ce semestre.
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
