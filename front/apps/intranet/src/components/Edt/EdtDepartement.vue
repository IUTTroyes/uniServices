<script setup>
import {VueCal} from 'vue-cal';
import 'vue-cal/style';
import {computed, nextTick, onMounted, reactive, ref, watch} from 'vue';
import { useSemestreStore, useUsersStore } from '@stores';
import {SimpleSkeleton, MessageCard, PhotoUser} from '@components';
import {getISOWeekNumber} from "@helpers/date";
import EdtEvent from "./EdtEvent.vue";
import {getEdtEventsService, getSemaineUniversitaireService, getGroupesService} from "@requests";
import {adjustColor, colorNameToRgb, darkenColor} from "@helpers/colors.js";
import { useToast } from 'primevue/usetoast';
import EdtListe from "./EdtListe.vue";

// Référence vers le composant vue-cal
const vuecalRef = ref(null)

const viewTranslations = {
  day: 'JOUR',
  week: 'SEMAINE',
};
const toast = useToast();
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const usersStore = useUsersStore();
const semestreStore = useSemestreStore();
const isLoadingSemestres = ref(false);
const semestresList = ref([]);
const selectedSemestre = ref(null);
const hasError = ref(false);
const personnel = usersStore.user;
const departement = usersStore.departementDefaut;
const weekUnivNumber = ref(0);
const events = ref([]);
const schedules = ref([]); // Pour les groupes de TP
const isLoadingGroupes = ref(false);

const liste = ref(false);

onMounted(async () => {
  await getSemestres();
  await getEventsDepartementWeek();
});

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  try {
    await semestreStore.getSemestresByDepartement(departement.id, true);
    semestresList.value = semestreStore.semestres;
  } catch (error) {
    console.error('Erreur lors du chargement des semestres :', error);
    hasError.value = true;
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger les semestres. Nous faisons notre possible pour résoudre cette erreur au plus vite.',
      life: 5000,
    });
  } finally {
    isLoadingSemestres.value = false;
  }
};

watch(selectedSemestre, async (newValue) => {
  if (newValue) {
    await getSemestreGroupes(newValue);
    await getWeekUnivNumber(new Date(vuecalRef.value?.view?.start || new Date()));
    await getEventsDepartementWeek()
  }
});

watch(() => vuecalRef.value?.view?.start, async (newValue) => {
  if (newValue && selectedSemestre.value) { // Vérifie qu'un semestre est sélectionné
    const startDate = new Date(newValue);
    await getWeekUnivNumber(startDate); // Récupère le numéro de semaine de formation
    getEventsDepartementWeek(); // Met à jour les événements pour la semaine
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

const getSemestreGroupes = async (semestre) => {
  try {
    isLoadingGroupes.value = true;
    const params = {
      semestre: semestre.id,
      type: "TP",
    };
    const groupes = await getGroupesService(params);
    schedules.value = groupes.map(groupe => ({
      id: groupe.id,
      label: groupe.libelle,
      class: 'groupe',
      color: groupe.couleur ? adjustColor(darkenColor(groupe.couleur, 50), 0, 0.2) : '#ccc',
    }));
  } catch (error) {
    console.error('Erreur lors de la récupération des groupes :', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger les groupes. Nous faisons notre possible pour résoudre cette erreur au plus vite.',
      life: 5000,
    });
  } finally {
    isLoadingGroupes.value = false;
  }
}

function detectOverlap(event, allEvents) {
  return allEvents.some(e =>
      (event.start < e.end && event.end > e.start) &&
      event !== e
  );
}

const getEventsDepartementWeek = async () => {
  if (!selectedSemestre.value) {
    console.warn('Aucun semestre sélectionné, la requête ne sera pas exécutée.');
    return;
  }

  try {
    const params = {
      semaineFormation: weekUnivNumber.value,
      semestre: selectedSemestre.value.id,
      anneeUniversitaire: anneeUniv.id,
      departement: departement.id
    };
    const response = await getEdtEventsService(params);

    if (response && response.length > 0) {
      console.log('Events:', response);
      let mappedEvents = [];

      response.forEach(event => {
        // Définir la couleur en fonction du type de groupe
        let eventColor;
        switch (event.groupe.type) {
            // couleurs comme dans Celcat
          case 'CM':
            eventColor = '#33C1FF'; // Bleu pour CM
            break;
          case 'TD':
            eventColor = '#ffee33'; // Jaune pour TD
            break;
          case 'TP':
            eventColor = '#33FF57'; // Vert pour TP
            break;
          default:
            eventColor = '#CCCCCC'; // Gris par défaut
        }

        const startDate = new Date(event.debut);
        const endDate = new Date(event.fin);

        // Create the base event object
        const baseEvent = {
          ...event,
          ongoing: new Date(startDate.getTime() + startDate.getTimezoneOffset() * 60000) <= new Date() && new Date(endDate.getTime() + endDate.getTimezoneOffset() * 60000) >= new Date(),
          start: new Date(startDate.getTime() + startDate.getTimezoneOffset() * 60000), // Ajustement du fuseau horaire
          end: new Date(endDate.getTime() + endDate.getTimezoneOffset() * 60000), // Ajustement du fuseau horaire
          backgroundColor: adjustColor(colorNameToRgb(eventColor), 1, 0),
          location: event.salle,
          title: event.libModule,
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
              }))
        };

        // If the event is a CM, it should span across all columns
        if (event.type === 'CM') {
          console.log('CM event', event);
          // For CM events, use the first available schedule (if schedules exist)
          // We'll make it span across all columns with CSS later
          mappedEvents.push({
            ...baseEvent,
            schedule: schedules.value.length > 0 ? schedules.value[0].id : event.groupe.id,
            // Flag this as a CM event for styling
            isCmEvent: true
          });
        }
            // If the event is for a TD group with TP children, use the first TP child's schedule
        // and set width to 200% later to make it span across columns
        else if (event.type === 'TD' && event.groupe.enfants && event.groupe.enfants.length > 0) {
          // Get all TP children
          const tpChildren = event.groupe.enfants.filter(enfant => enfant.type === 'TP');

          if (tpChildren.length > 0 && event.type === 'TD') {
            // Use only the first TP child's schedule - we'll make it span with CSS later
            mappedEvents.push({
              ...baseEvent,
              schedule: tpChildren[0].id,
              // Flag this as a TD event for styling
              isTdEvent: true
            });
          } else {
            // If no TP children, use the TD group's ID
            mappedEvents.push({
              ...baseEvent,
              schedule: event.groupe.id
            });
          }
        } else {
          // For non-TD and non-CM events, use the group's ID directly
          mappedEvents.push({
            ...baseEvent,
            schedule: event.groupe.id
          });
        }
      });

      events.value = mappedEvents.map(event => ({
        ...event,
        title: detectOverlap(event, mappedEvents) ? event.codeModule : event.title,
        overlap: !!detectOverlap(event, mappedEvents),
        class: event.type === 'TD' ? 'td-event-spanning' : (event.type === 'CM' ? 'cm-event-spanning' : ''),
      }));
    } else {
      events.value = [];
    }
  } catch (error) {
    console.error('Error fetching events:', error);
  } finally {
    console.log('Events:', events.value);
    await nextTick();
    const eventsObjects = document.querySelectorAll('.vuecal__event');
    eventsObjects.forEach(eventEl => {
      if (eventEl.style.backgroundColor) {
        eventEl.style.border = `2px solid ${adjustColor(darkenColor(eventEl.style.backgroundColor, 50), 0, 0.2)}`;
        eventEl.style.borderTop = `6px solid ${adjustColor(darkenColor(eventEl.style.backgroundColor, 60), 0, 0.2)}`;
        eventEl.style.overflow = 'auto';
        eventEl.style.scrollbarWidth = 'none';
        eventEl.style.cssText += '::-webkit-scrollbar { display: none; }';
        eventEl.style.opacity = 0.9;
      }
    });
  }
};

const cmEventWidth = computed(() => {
  return `${schedules.value.length * 100}%`;
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

  <SimpleSkeleton v-if="isLoadingSemestres" class="w-1/3" />
  <Select
      v-else
      v-model="selectedSemestre"
      :options="semestresList"
      optionLabel="libelle"
      placeholder="Sélectionner un semestre"
      class="w-full my-6"
  ></Select>
  <div class="flex justify-end">
    <Button v-if="!liste" @click="liste = true">Afficher la liste</Button>
    <Button v-else @click="liste = false">Masquer la liste</Button>
  </div>
  <MessageCard v-if="!selectedSemestre" class="mt-4" content="Veuillez sélectionner un semestre pour afficher l'emploi du temps.">
  </MessageCard>
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
      :schedules="schedules"
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
          <EdtListe :events="events"/>
        </div>

        <div v-if="view.id === 'day' && liste === true" class="flex justify-center items-center mb-4">
          <div class="text-lg flex flex-col items-center bg-gray-300 bg-opacity-20 rounded-md px-4 py-2 w-full uppercase">
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
      <!--      {{event.id}}-->
      <EdtEvent :event="event" type="departement" />
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
:deep(.vuecal__body) {
  @apply gap-2;
}
:deep(.vuecal__weekday) {
  @apply bg-gray-300 bg-opacity-20 py-4 rounded-md flex flex-col items-center uppercase;
}

:deep(.vuecal__weekdays-headings) {
  @apply flex justify-between items-center gap-2 h-3/4;
}

:deep(.vuecal__schedules-headings) {
  @apply bg-white rounded-md border-b-2;
}

:deep(.vuecal--day-view .vuecal__scrollable-wrap .vuecal__scrollable) {
  @apply p-6;
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
