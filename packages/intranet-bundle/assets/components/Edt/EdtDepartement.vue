<script setup>
import {VueCal} from 'vue-cal'
import 'vue-cal/style'
import {computed, nextTick, onMounted, ref, watch} from 'vue';
import {useUsersStore} from '@stores';
import {PhotoUser, SimpleSkeleton, ErrorView} from '@components';
import {getISOWeekNumber} from "@helpers/date";
import EdtEvent from "./EdtEvent.vue";
import {
  getEdtEventsService,
  getGroupesService,
  getPersonnelsService,
  getSemaineUniversitaireService,
  getSallesService,
  getEnseignementsService,
  getDiplomesService,
  getAnneesService,
  getSemestresService
} from "@requests";
import {adjustColor, darkenColor} from "@helpers/colors.js";
import EdtListe from "./EdtListe.vue";
import Loader from "@components/loader/GlobalLoader.vue";
import {
  applyOverlapMetadata,
  calculateHoursByType,
  calculateTotalHours,
  getBadgeSeverityByType,
  mapDepartementEvents,
  styleVueCalEvents,
} from '@/service/utils/edtUtils';

// Référence vers le composant vue-cal
const vuecalRef = ref(null)

const viewTranslations = {
  day: 'JOUR',
  week: 'SEMAINE',
};
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const usersStore = useUsersStore();
const isLoadingDiplomes = ref(true);
const diplomes = ref([]);
const selectedDiplome = ref(null);
const isLoadingAnnees = ref(true)
const selectedAnnee = ref(null);
const annees = ref([]);
const isLoadingSemestres = ref(true)
const semestres = ref([])
const selectedSemestre = ref(null);
const isLoadingEnseignants = ref(false);
const hasErrorEnseignants = ref(false);
const enseignantsList = ref([]);
const selectedEnseignant = ref(null);
const isLoadingSalles = ref(false);
const hasErrorSalles = ref(false);
const sallesList = ref([]);
const selectedSalle = ref(null);
const isLoadingEnseignements = ref(false);
const hasErrorEnseignements = ref(false);
const enseignementsList = ref([]);
const selectedEnseignement = ref(null);
const isLoadingEvents = ref(false);
const hasError = ref(false);
const personnel = usersStore.user;
const departement = usersStore.departementDefaut;
const weekUnivNumber = ref(0);
const events = ref([]);
const schedules = ref([]);
const isLoadingGroupes = ref(false);

const liste = ref(false);

onMounted(async () => {
  await getDiplomes();
  await getEnseignants();
  await getSalles();
  isLoadingEvents.value = false;
});

watch(selectedDiplome, async () => {
  await getAnnees();
});
watch(selectedAnnee, async () => {
  await getSemestres();
})

const getDiplomes = async () => {
  isLoadingDiplomes.value = true;
  try {
    const params = {
      departement: departement.id,
      anneeUniversitaire: anneeUniv.id,
    }
    diplomes.value = await getDiplomesService(params, '/edt')
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching diplomes:', error);
  } finally {
    selectedDiplome.value = diplomes.value[0] ?? null;
    isLoadingDiplomes.value = false;
  }
};

const getAnnees = async () => {
  isLoadingAnnees.value = true;
  try {
    if (!selectedDiplome.value?.id) {
      annees.value = [];
      return;
    }

    const params = {
      diplome: selectedDiplome.value.id
    }
    annees.value = await getAnneesService(params)
  } catch (error) {
    console.error('Error fetching annees:', error);
  } finally {
    selectedAnnee.value = annees.value[0] ?? null
    isLoadingAnnees.value = false;
  }
}

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  try {
    if (!selectedAnnee.value?.id) {
      semestres.value = [];
      return;
    }

    const params = {
      annee: selectedAnnee.value.id
    }
    semestres.value = await getSemestresService(params)
  } catch (error) {
    console.error('Error fetching semestres', error)
  } finally {
    selectedSemestre.value = semestres.value[0] ?? null
    isLoadingSemestres.value = false;
  }
}

const getEnseignants = async () => {
  try {
    const params = {
      departement: departement.id,
    };
    enseignantsList.value = await getPersonnelsService(params);
  } catch (error) {
    hasErrorEnseignants.value = true;
    console.error('Erreur lors du chargement des enseignants :', error);
  } finally {
    isLoadingEnseignants.value = false;
  }
};

const getSalles = async () => {
  try {
    sallesList.value = await getSallesService();
  } catch (error) {
    console.error('Erreur lors du chargement des salles :', error);
  } finally {
    isLoadingSalles.value = false;
  }
};

const getEnseignements = async () => {
  try {
    const params = {
      semestre: selectedSemestre.value ? selectedSemestre.value?.id : null,
      departement: selectedSemestre.value ? null : departement.id,
      actif: true,
    };
    enseignementsList.value = await getEnseignementsService(params);

    // reconstruire le libelle pour inclure le code_enseignement
    enseignementsList.value = enseignementsList.value.map(enseignement => ({
      ...enseignement,
      libelle: `${enseignement.codeEnseignement} - ${enseignement.libelle}`
    }));
  } catch (error) {
    console.error('Erreur lors du chargement des enseignements :', error);
  } finally {
    isLoadingEnseignements.value = false;
  }
};

const getWeekUnivNumber = async (date) => {
  const calendarWeekNumber = getISOWeekNumber(date);
  try {
    const response = await getSemaineUniversitaireService(calendarWeekNumber, anneeUniv.id);
    weekUnivNumber.value = response[0]?.semaineFormation || 0; // Définit la semaine de formation
  } catch (error) {
    console.error('Erreur lors de la récupération du numéro de semaine universitaire :', error);
  }
};

const getSemestreGroupes = async (semestre) => {
  try {
    isLoadingGroupes.value = true;
    const params = {
      semestre: semestre.id,
      type: "TP",
    };
    const groupes = await getGroupesService(params, '/mini');
    schedules.value = groupes.map(groupe => ({
      id: groupe.id,
      label: groupe.libelle,
    }));
  } catch (error) {
    console.error('Erreur lors de la récupération des groupes :', error);
  } finally {
    isLoadingGroupes.value = false;
  }
}

watch(() => vuecalRef.value?.view?.start, async (newValue) => {
  if (newValue) {
    const startDate = new Date(newValue);
    await getWeekUnivNumber(startDate);
    await getEventsDepartementWeek();
  }
}, { immediate: true });

watch(selectedSemestre, async (newValue) => {
  isLoadingEvents.value = true;
  await getEnseignements();
  if (newValue) {
    await getSemestreGroupes(newValue);
  }
  isLoadingEvents.value = false;
});

watch(selectedEnseignant, async () => {
  isLoadingEvents.value = true;
  await getEventsDepartementWeek();
  isLoadingEvents.value = false;
});

watch(selectedSalle, async () => {
  isLoadingEvents.value = true;
  await getEventsDepartementWeek();
  isLoadingEvents.value = false;
});

watch(selectedEnseignement, async () => {
  isLoadingEvents.value = true;
  await getEventsDepartementWeek();
  isLoadingEvents.value = false;
});

const getEventsDepartementWeek = async () => {
  const hasAtLeastOneFilter = selectedSemestre.value || selectedEnseignant.value || selectedSalle.value || selectedEnseignement.value;

  try {
    if (!hasAtLeastOneFilter) {
      events.value = [];
      return;
    }

    const params = {
      semaineFormation: weekUnivNumber.value,
      semestre: selectedSemestre.value ? selectedSemestre.value.id : null,
      anneeUniversitaire: anneeUniv.id,
      departement: departement.id,
      personnel: selectedEnseignant.value ? selectedEnseignant.value.id : null,
      salle: selectedSalle.value ? selectedSalle.value.id : null,
      enseignement: selectedEnseignement.value ? selectedEnseignement.value.id : null,
    };

    const response = await getEdtEventsService(params);

    if (!response?.length) {
      events.value = [];
      return;
    }

    const mappedEvents = mapDepartementEvents({
      response,
      selectedSemestre: !!selectedSemestre.value,
      schedules: schedules.value,
      personnelId: personnel.id,
    });

    events.value = applyOverlapMetadata(mappedEvents, !!selectedSemestre.value);
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching events:', error);
  } finally {
    await nextTick();
    styleVueCalEvents();
  }
};

const cmEventWidth = computed(() => {
  if (schedules.value.length === 0) return '100%';
  return `${schedules.value.length * 100}%`;
});

const selectedEvent = ref(null)
const visible = ref(false)

const openDialog = ({ event }) => {
  selectedEvent.value = event
  visible.value = true
}

// calculer le nombre total d'heures pour l'ensemble des événements affichés
const totalHeures = computed(() => {
  return calculateTotalHours(events.value);
});

// calculer le nombre total d'heures par type "CM", "TD", "TP"
const heuresParType = computed(() => {
  return calculateHoursByType(events.value);
});
</script>

<template>
  <div class="flex gap-4 w-full pb-6 overflow-x-auto">
    <div class="bg-neutral-100 dark:bg-neutral-800 p-4 rounded-lg w-full min-w-48 flex flex-col items-center justify-center">
      <div>
        Total heures
      </div>
      <div class="text-lg font-bold">
        {{totalHeures}} h
      </div>
    </div>
    <div v-for="heuresType in heuresParType" class="bg-neutral-100 dark:bg-neutral-800 bg-opacity-20 p-4 rounded-lg w-full min-w-48 flex flex-col items-center justify-center">
      <div>
        {{heuresType.type}}
      </div>
      <div class="text-lg font-bold">
        {{heuresType.heures}} h
      </div>
    </div>
  </div>

  <Dialog v-model:visible="visible" :header="selectedEvent?.title" class="!bg-gray-50 dark:!bg-gray-800 !border-2 !border-primary-500" :style="{ width: '25vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
    <div class="flex flex-col gap-2">
      <div>
        {{selectedEvent.start.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}} •
        {{selectedEvent.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}} -
        {{selectedEvent.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}}
      </div>
      <div class="flex items-center gap-2">
        <Badge v-if="selectedEvent.eval" severity="danger" class="uppercase">Évaluation</Badge>
        <Badge :severity="getBadgeSeverityByType(selectedEvent.enseignement.type)" class="uppercase">
          {{ selectedEvent.enseignement.type }}
        </Badge>
      </div>
      <div class="flex flex-col gap-1">
        <div>
          <strong>Semestre :</strong> {{ selectedEvent.semestre.libelle }}
        </div>
        <div>
          <strong>Groupe :</strong> <Badge class="!text-black" :style="{ backgroundColor: selectedEvent?.backgroundColor ? adjustColor(darkenColor(selectedEvent.backgroundColor, 60), 0, 0.2) : '' }">{{ selectedEvent?.type }}</Badge> {{ selectedEvent?.groupe?.libelle }} ({{selectedEvent?.groupe?.etudiants?.length || 0}} étudiants)
        </div>
        <div>
          <strong>Salle :</strong> {{ selectedEvent.location }}
        </div>
        <div>
          <strong>Intervenant :</strong>
          <div class="flex items-center gap-2">
            <PhotoUser :user-photo="selectedEvent.intervenantPhoto" class="!w-8 border-2 border-black" />
            {{ selectedEvent.libPersonnel || 'Inconnu' }}
          </div>
        </div>
        <Divider v-if="selectedEvent.intervenants && selectedEvent.intervenants.length > 0"></Divider>
        <div v-if="selectedEvent.intervenants && selectedEvent.intervenants.length > 0" class="flex flex-col gap-2">
          <strong>Autres intervenants sur la {{selectedEvent.enseignement.type}} :</strong>
          <div class="flex flex-col gap-2">
            <div v-for="intervenant in selectedEvent.intervenants" :key="intervenant.id" class="flex items-center gap-2">
              <PhotoUser :user-photo="selectedEvent.intervenantPhoto" class="!w-8 border-2 border-black" />
              {{ intervenant?.display || 'Inconnu' }}
            </div>
          </div>
        </div>
      </div>
      <Divider></Divider>
      <div class="flex w-full justify-end">
        <div class="flex gap-2">
          <Button icon="pi pi-list" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Appel" size="small" v-tooltip.top="'Faire l\'appel'"></Button>
          <Button icon="pi pi-check-circle" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Tous présents" size="small" v-tooltip.top="'Marquer tout le monde présents'"></Button>
          <Button icon="pi pi-book" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Plan de cours" size="small" v-tooltip.top="'Voir le plan de cours'"></Button>
          <Button v-if="selectedEvent.evaluation" icon="pi pi-file-edit" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Saisir les notes" size="small" v-tooltip.top="'Saisir les notes'"></Button>
        </div>
      </div>
    </div>
  </Dialog>
  <ErrorView v-if="hasError"></ErrorView>
  <div v-else class="bg-neutral-100 dark:bg-neutral-800 rounded-lg p-6 mb-6 w-full ">
    <div class="text-lg font-bold mb-4">
      Filtres
    </div>
    <SimpleSkeleton v-if="isLoadingDiplomes" class="w-full"/>
    <Tabs v-else :value="selectedDiplome?.id ?? null" :scrollable="diplomes.length > 1">
      <TabList>
        <Tab v-for="diplome in diplomes" :key="diplome.id" :value="diplome.id" @click="selectedDiplome = diplome">
        <span>
          <span>{{ diplome.typeDiplome.sigle }}</span> | <span>{{ diplome.sigle }}</span>
        </span>
        </Tab>
      </TabList>
    </Tabs>
    <div v-if="isLoadingDiplomes" class="flex items-center gap-4 w-full">
      <SimpleSkeleton class="w-1/2"/>
      <SimpleSkeleton class="w-1/2"/>
    </div>
    <div v-else class="mt-8 flex items-center gap-4 w-full">
      <SimpleSkeleton v-if="isLoadingAnnees" class="w-1/2"/>
      <Select v-else-if="selectedDiplome" v-model="selectedAnnee" :options="annees" option-label="libelle" placeholder="Sélectionner une année" class="w-1/2"/>
      <SimpleSkeleton v-if="isLoadingSemestres" class="w-1/2"/>
      <Select v-else-if="selectedAnnee" v-model="selectedSemestre" :options="semestres" option-label="libelle" placeholder="Sélectionner un semestre" class="w-1/2"/>
    </div>
    <div class="flex justify-center items-center gap-4 mb-4">
      <SimpleSkeleton v-if="isLoadingEnseignants" class="w-1/3"/>
      <Message v-else-if="hasErrorEnseignants" severity="error" class="w-full my-6" icon="pi pi-exclamation-circle">
        Erreur lors du chargement des enseignants.
      </Message>
      <Select
          v-else
          v-model="selectedEnseignant"
          :options="enseignantsList"
          optionLabel="display"
          placeholder="Filtrer par enseignant"
          class="w-full my-6"
          show-clear
          filter
      ></Select>
      <SimpleSkeleton v-if="isLoadingSalles" class="w-1/3"/>
      <Message v-else-if="hasErrorSalles" severity="error" class="w-full my-6" icon="pi pi-exclamation-circle">
        Erreur lors du chargement des salles.
      </Message>
      <Select
          v-else
          v-model="selectedSalle"
          :options="sallesList"
          optionLabel="libelle"
          placeholder="Filtrer par salle"
          class="w-full my-6"
          show-clear
          filter
      ></Select>
      <SimpleSkeleton v-if="isLoadingEnseignements" class="w-1/3"/>
      <Message v-else-if="hasErrorEnseignements" severity="error" class="w-full my-6" icon="pi pi-exclamation-circle">
        Erreur lors du chargement des enseignements.
      </Message>
      <Select
          v-else
          v-model="selectedEnseignement"
          :options="enseignementsList"
          optionLabel="libelle"
          placeholder="Filtrer par enseignement"
          class="w-full my-6"
          show-clear
          filter
      ></Select>
    </div>
  </div>

  <div class="flex justify-end">
    <Button v-if="!liste" @click="liste = true">Afficher la liste</Button>
    <Button v-else @click="liste = false">Masquer la liste</Button>
  </div>

  <Loader v-if="isLoadingEvents"/>
  <ErrorView v-else-if="hasError" message="Une erreur est survenue lors du chargement de l'emploi du temps. Veuillez réessayer plus tard."/>
  <vue-cal
      v-else
      ref="vuecalRef"
      locale="fr"
      hide-weekends
      time
      :time-from="8 * 60"
      :time-to="21 * 60"
      :time-step="30"
      week-numbers
      stack-events
      :views="['day', 'week']"
      :default-view="'week'"
      :theme="false"
      diy
      :events="events"
      :schedules="selectedSemestre ? schedules : []"
      :style="{ '--cm-event-width': cmEventWidth }"
      @event-click="openDialog">
    <template #header="{ view, availableViews, vuecal }">
      <div class="p-6">
        <div class="flex justify-center items-center gap-12 mb-4">
          <Button
              icon="pi pi-chevron-circle-left"
              @click="view.previous"
              class="p-button-text"
          />
          <div class="flex flex-col items-center">
            <span v-html="view.title" class="font-bold text-xl flex flex-col items-center"></span>
            <span class="text-md text-muted-color">Semaine de formation : {{ weekUnivNumber }}</span>
          </div>
          <Button
              icon="pi pi-chevron-circle-right"
              @click="view.next"
              class="p-button-text"
          />
        </div>

        <div v-if="liste" class="flex flex-col gap-2 mb-12">
          <EdtListe :events="events" type="departement"/>
        </div>

        <div v-if="view.id === 'day' && liste === true" class="flex justify-center items-center mb-4">
          <div class="text-lg flex flex-col items-center bg-gray-300/20 rounded-md px-4 py-2 w-full uppercase">
            <div>{{ view.start.toLocaleDateString('fr-FR', { weekday: 'long' }) }}</div>
            <div class="font-bold">{{ view.start.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }) }}</div>
          </div>
        </div>

        <div class="flex justify-between items-center">
          <div class="view-buttons flex gap-4">
            <Button
                v-for="(grid, viewId) in availableViews"
                :key="viewId"
                @click="vuecal.view.switch(viewId)"
                :class="{ 'p-button-primary': view.id === viewId, 'p-button-outlined': view.id !== viewId }"
                class="uppercase"
            >
              {{ viewTranslations[viewId] || viewId }}
            </Button>
          </div>

          <Button
              @click="view.goToToday()"
              class="uppercase p-button-outlined"
              severity="primary"
          >aujourd'hui</Button>
        </div>
      </div>
    </template>

    <template #weekday-heading="{ label, id, date }">
      <div :class="id">{{ label }}</div>
      <strong>{{ new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }) }}</strong>
    </template>

    <template #event="{ event }">
      <EdtEvent :event="event" type="departement" />
    </template>
  </vue-cal>
</template>

<style scoped>
@reference "../../assets/tailwind.css";

:deep(.vuecal__event) {
  @apply rounded-xl text-sm text-black !overflow-scroll p-0 transition duration-200 ease-in-out;
  &:hover {
    @apply border !border-primary-500 transition duration-200 ease-in-out cursor-pointer shadow-md z-20;
  }
}
:deep(.vuecal__body) {
  @apply gap-2;
}

:deep(.vuecal__weekday) {
  @apply bg-neutral-100 dark:bg-neutral-800 py-4 rounded-md flex flex-col items-center uppercase;
}

:deep(.vuecal__weekdays-headings) {
  @apply flex justify-between items-center gap-2 h-3/4;
}

:deep(.vuecal__schedules-headings) {
  @apply rounded-md;
}

:deep(.vuecal--day-view .vuecal__scrollable-wrap .vuecal__scrollable) {
  @apply p-6;
}

:deep(.vuecal__cell) {
  @apply border border-neutral-100 dark:border-neutral-700 rounded-md;
}

/* Style for TD events that span across columns */
:deep(.td-event-spanning) {
  width: 200% !important; /* Make the event span across two columns */
}

/* Style for CM events that span across all columns */
:deep(.cm-event-spanning) {
  width: var(--cm-event-width) !important;
}

:deep(.vuecal__schedule) {
  overflow: visible !important;
}
</style>
