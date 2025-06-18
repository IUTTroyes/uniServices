<script setup>
import {VueCal} from 'vue-cal'
import 'vue-cal/style'

import {onMounted, ref, watch, nextTick} from 'vue'
import {getPersonnelEdtWeekEventsService, getSemaineUniversitaireService} from "@requests";
import {adjustColor, colorNameToRgb, darkenColor} from "@helpers/colors.js";
import {getISOWeekNumber} from "@helpers/date";
import {useUsersStore} from "@stores";
import {PhotoUser} from "@components";

// Importer le store des utilisateurs
const usersStore = useUsersStore();
const personnel = usersStore.user;
const departement = usersStore.departementDefaut;
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };

// Référence vers le composant vue-cal
const vuecalRef = ref(null)
const events = ref([]);
const weekUnivNumber = ref(0);

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



// Watcher pour mettre à jour le numéro de semaine universitaire lorsque la vue change
watch (() => vuecalRef.value?.view?.start, (newValue) => {
  if (newValue) {
    getWeekUnivNumber(new Date(newValue));
    getEventsPersonnelWeek()
  }
}, { immediate: true });



// Ajout d'une fonction pour détecter les chevauchements
function detectOverlap(event, allEvents) {
  return allEvents.some(e =>
      (event.start < e.end && event.end > e.start) &&
      event !== e
  );
}

const getEventsPersonnelWeek = async () => {
  try {
    const response = await getPersonnelEdtWeekEventsService(weekUnivNumber.value, personnel.id, anneeUniv.id, departement.id);
    if (response && response.length > 0) {
      const mappedEvents = response.map(event => {
        const startDate = new Date(event.debut);
        const endDate = new Date(event.fin);

        return {
          ...event,
          ongoing: new Date(startDate.getTime() + startDate.getTimezoneOffset() * 60000) <= new Date() && new Date(endDate.getTime() + endDate.getTimezoneOffset() * 60000) >= new Date(),
          start: new Date(startDate.getTime() + startDate.getTimezoneOffset() * 60000), // Ajustement du fuseau horaire
          end: new Date(endDate.getTime() + endDate.getTimezoneOffset() * 60000), // Ajustement du fuseau horaire
          backgroundColor: adjustColor(colorNameToRgb(event.couleur), 1, 0.2),
          location: event.salle,
          title: event.codeModule + ' - ' + event.libModule,
          type: event.type,
          groupe: event.groupe || '**',
          personnel: event.personnel,
          intervenantPhoto: event.personnel.photoName ?? null,
          overlap: false,
          eval: event.evaluation,
          intervenants: event.enseignement.previsionnels
              .filter(intervenant => intervenant.personnel.id !== personnel.id)
              .map(intervenant => ({
                id: intervenant.id,
                display: intervenant.personnel?.display || 'Inconnu',
                photoName: intervenant.personnel?.photoName || null,
              })),
        };
      });

      events.value = mappedEvents.map(event => ({
        ...event,
        title: detectOverlap(event, mappedEvents) ? event.codeModule : event.title,
        overlap: !!detectOverlap(event, mappedEvents),
      }));
    } else {
      events.value = [];
    }
  } catch (error) {
    console.error('Error fetching events:', error);
  } finally {
    await nextTick();
    const eventsObjects = document.querySelectorAll('.vuecal__event');
    eventsObjects.forEach(event => {
      if (event.style.backgroundColor) {
        event.style.border = `2px solid ${adjustColor(darkenColor(event.style.backgroundColor, 50), 0, 0.2)}`;
        event.style.borderTop = `6px solid ${adjustColor(darkenColor(event.style.backgroundColor, 60), 0, 0.2)}`;
        event.style.overflow = 'auto';
        event.style.scrollbarWidth = 'none';
        event.style.cssText += '::-webkit-scrollbar { display: none; }';
        event.style.opacity = 0.9;
      }
    });
  }

  console.log('events', events.value);
};

onMounted(() => {
  getEventsPersonnelWeek();
});

const selectedEvent = ref(null)
const visible = ref(false)

const openDialog = ({ event }) => {
  selectedEvent.value = event
  visible.value = true
  console.log('selected', selectedEvent.value)
}

function getBadgeSeverity(type) {
  const badgeMapping = {
    ressource: 'primary',
    sae: 'warn',
    matiere: 'success',
  };

  return badgeMapping[type] || 'info'; // Valeur par défaut si le type est inconnu
}
</script>

<template>

  <Dialog v-model:visible="visible" :header="selectedEvent?.title" class="!bg-gray-50 dark:!bg-gray-800 !border-2 !border-primary-500" :style="{ width: '25vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
    <div class="flex flex-col gap-2">
      <div>
        {{selectedEvent.start.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}} •
        {{selectedEvent.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}} -
        {{selectedEvent.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}}
      </div>
      <div class="flex items-center gap-2">
        <Badge v-if="selectedEvent.eval" severity="danger" class="uppercase">Évaluation</Badge>
        <Badge :severity="getBadgeSeverity(selectedEvent.enseignement.type)" class="uppercase">
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

  <vue-cal
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
      <div class="rounded-lg !h-full">
        <div class="p-2 flex flex-col justify-between h-full gap-1">
          <div>
            <div class="title font-black">{{ event.title }}</div>
            <div class="flex gap-1 items-center"><Badge class="!text-black" :style="{ backgroundColor: event.backgroundColor ? adjustColor(darkenColor(event.backgroundColor, 60), 0, 0.2) : '' }">{{ event.type }}</Badge>{{event.semestre.libelle}} | {{ event.groupe.libelle }}</div>
            <div>{{ event.location }}</div>
          </div>
          <div v-if="event.overlap" class="flex flex-col gap-2">
            <div>{{ event.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }} - {{ event.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}</div>
            <div class="flex gap-2">
              <Button icon="pi pi-list" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Appel" size="small" v-tooltip.top="'Faire l\'appel'"></Button>
              <Button icon="pi pi-check-circle" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Tous présents" size="small" v-tooltip.top="'Marquer tout le monde présents'"></Button>
              <Button icon="pi pi-book" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Plan de cours" size="small" v-tooltip.top="'Voir le plan de cours'"></Button>
            </div>
          </div>
          <div v-else class="flex justify-between items-center flex-wrap gap-2">
            <div class="flex gap-2">
              <Button icon="pi pi-list" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Appel" size="small" v-tooltip.top="'Faire l\'appel'"></Button>
              <Button icon="pi pi-check-circle" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Tous présents" size="small" v-tooltip.top="'Marquer tout le monde présents'"></Button>
              <Button icon="pi pi-book" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Plan de cours" size="small" v-tooltip.top="'Voir le plan de cours'"></Button>
            </div>
            <div class="flex flex-col items-center">
              <Badge v-if="event.evaluation" severity="danger" class="uppercase">éval.</Badge>
              <div class="opacity-60">{{ event.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }} - {{ event.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}</div>
            </div>
          </div>
          <!--          <div>-->
          <!--            {{ event.location }} | {{ event.type }} {{ event.groupe }} | <span class="opacity-70">-->
          <!--            {{ event.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }} - {{ event.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}-->
          <!--          </span>-->
          <!--          </div>-->
          <!--          <div class="flex items-center gap-2">-->
          <!--            <PhotoUser :user-photo="event.personnel.photoName" class="!w-8 border-2 border-black"/>-->
          <!--            <div class="text-sm">{{ event.libPersonnel }}</div>-->
          <!--          </div>-->
        </div>
      </div>
    </template>
  </vue-cal>
</template>

<style scoped>
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
  @apply border border-gray-200 dark:border-gray-800 border-opacity-20 rounded-md;
}

:deep(.vuecal__weekday) {
  @apply bg-gray-200 bg-opacity-20 dark:bg-gray-800 py-4 rounded-md flex flex-col items-center uppercase;
}

:deep(.vuecal__weekdays-headings) {
  @apply flex justify-between items-center gap-2;
}

:deep(.vuecal--day-view .vuecal__scrollable-wrap .vuecal__scrollable .vuecal__time-column) {
  @apply p-0;
}
</style>
