<script setup>
import {VueCal} from 'vue-cal'
import 'vue-cal/style'

import {ref, watch, nextTick, computed} from 'vue'
import EdtEvent from './EdtEvent.vue'
import EdtListe from "./EdtListe.vue";
import {getEdtEventsService, getSemaineUniversitaireService} from "@requests";
import {adjustColor, darkenColor} from "@helpers/colors.js";
import {getISOWeekNumber} from "@helpers/date";
import {useUsersStore} from "@stores";
import {PhotoUser, ErrorView} from "@components";
import {
  applyOverlapMetadata,
  calculateHoursByType,
  calculateTotalHours,
  getBadgeSeverityByType,
  mapPersonnelEvents,
  styleVueCalEvents,
} from "@/service/utils/edtUtils";

const usersStore = useUsersStore();
const personnel = usersStore.user;
const departement = usersStore.departementDefaut;
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const liste = ref(false);
const vuecalRef = ref(null)
const events = ref([]);
const weekUnivNumber = ref(0);
const hasError = ref(false);
const viewTranslations = {
  week: 'SEMAINE',
  day: 'JOUR',
};

watch(() => vuecalRef.value?.view?.start, async (newValue) => {
  if (newValue) {
    const startDate = new Date(newValue);
    await getWeekUnivNumber(startDate); // Récupère le numéro de semaine de formation
    getEventsPersonnelWeek(); // Met à jour les événements pour la semaine
  }
}, { immediate: true });

const getWeekUnivNumber = async (date) => {
  const calendarWeekNumber = getISOWeekNumber(date); // Calcule le numéro de semaine ISO
  try {
    const response = await getSemaineUniversitaireService(calendarWeekNumber, anneeUniv.id);
    weekUnivNumber.value = response[0]?.semaineFormation || 0; // Définit la semaine de formation
  } catch (error) {
    console.error('Erreur lors de la récupération du numéro de semaine universitaire :', error);
  }
};

const getEventsPersonnelWeek = async () => {
  try {
    const params = {
      semaineFormation: weekUnivNumber.value,
      personnel: personnel.id,
      anneeUniversitaire: anneeUniv.id,
      departement: departement.id,
    };
    const response = await getEdtEventsService(params);
    if (response && response.length > 0) {
      const mappedEvents = mapPersonnelEvents({
        response,
        personnelId: personnel.id,
      });
      events.value = applyOverlapMetadata(mappedEvents, { includeSpanningClass: false });
    } else {
      events.value = [];
    }
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching events:', error);
  } finally {
    await nextTick();
    styleVueCalEvents();
  }
};

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
  <div class="flex justify-end">
    <Button v-if="!liste" @click="liste = true">Afficher la liste</Button>
    <Button v-else @click="liste = false">Masquer la liste</Button>
  </div>

  <ErrorView v-if="hasError" message="Une erreur est survenue lors du chargement de l'emploi du temps. Veuillez réessayer plus tard." />
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
      :stack-events="true"
      :views="['day', 'week']"
      :default-view="'week'"
      :theme="false"
      diy
      :events="events"
      @event-click="openDialog">
    <!-- En-tête personnalisé -->
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
          <EdtListe :events="events" type="personnel"/>
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
      <EdtEvent :event="event" type="perso" />
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

:deep(.vuecal__event-details) {
  @apply h-full;
}

:deep(.vuecal__body) {
  @apply gap-2;
}

:deep(.vuecal__cell) {
  @apply border border-neutral-100 dark:border-neutral-700 rounded-md;
}

:deep(.vuecal__weekday) {
  @apply bg-neutral-100 dark:bg-neutral-800 py-4 rounded-md flex flex-col items-center uppercase;
}

:deep(.vuecal__weekdays-headings) {
  @apply flex justify-between items-center gap-2;
}

:deep(.vuecal__scrollable--day-view .vuecal__time-column) {
  @apply p-0 ;
}
</style>
