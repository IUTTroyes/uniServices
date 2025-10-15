<script setup>
import {VueCal} from 'vue-cal';
import 'vue-cal/style';
import EdtEvent from './EdtEvent.vue';
import {nextTick, onMounted, reactive, ref, watch} from 'vue';
import { useSemestreStore, useUsersStore } from '@stores';
import { SimpleSkeleton, ListSkeleton } from '@components';
import {getISOWeekNumber} from "@helpers/date";
import {getSemestreEdtWeekEventsService, getSemaineUniversitaireService, getGroupesService} from "@requests";
import {adjustColor, colorNameToRgb, darkenColor} from "@helpers/colors.js";
import { useToast } from 'primevue/usetoast';

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
const isLoadingEvents = ref(false);
const isLoadingGroupes = ref(false);
const groupes = ref([]);
const vuecalRef = ref(null)

const viewTranslations = {
  day: 'JOUR',
  week: 'SEMAINE',
};

const schedules = ref([]);

onMounted(async () => {
  await getSemestres();
});

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  try {
    await semestreStore.getSemestresByDepartement(departement.id, true, '/mini');
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
    // Get groups for the selected semester
    await getGroupesSemestre(newValue);

    // Get events if we have a view start date
    if (vuecalRef.value?.view?.start) {
      const startDate = new Date(vuecalRef.value.view.start);
      await getWeekUnivNumber(startDate);
      await getEventsDepartementWeek();
    }
  } else {
    schedules.value = [];
    groupes.value = [];
  }
});

watch(() => vuecalRef.value?.view?.start, async (newValue) => {
  if (newValue && selectedSemestre.value) { // Vérifie qu'un semestre est sélectionné
    const startDate = new Date(newValue);
    await getWeekUnivNumber(startDate); // Récupère le numéro de semaine de formation
    // await getEventsDepartementWeek();
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

const getGroupesSemestre = async (semestre) => {
  try {
    isLoadingGroupes.value = true;
    const params = {
      semestre: semestre.id,
      // Fetch all group types (TP, TD, CM)
    };
    groupes.value = await getGroupesService(params);

    // Log the types of groups fetched
    const groupTypes = {};
    groupes.value.forEach(g => {
      if (!groupTypes[g.type]) {
        groupTypes[g.type] = 0;
      }
      groupTypes[g.type]++;
    });
  } catch (error) {
    console.error('Erreur lors de la récupération des groupes du semestre :', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger les groupes du semestre. Nous faisons notre possible pour résoudre cette erreur au plus vite.',
      life: 5000,
    });
  } finally {
    // Construire les schedules pour vuecal avec tous les types de groupes (TP, TD, CM)
    // Créer un schedule pour chaque type de groupe
    const tpGroups = (groupes.value || [])
        .filter(groupe => groupe.type === 'TP')
        .map(groupe => ({
          id: groupe.id,
          class: `groupe-tp-${groupe.id}`,
          label: `${groupe.libelle}`,
          type: 'TP'
        }));

    const tdGroups = (groupes.value || [])
        .filter(groupe => groupe.type === 'TD')
        .map(groupe => ({
          id: groupe.id,
          class: `groupe-td-${groupe.id}`,
          label: `${groupe.libelle}`,
          type: 'TD'
        }));

    const cmGroups = (groupes.value || [])
        .filter(groupe => groupe.type === 'CM')
        .map(groupe => ({
          id: groupe.id,
          class: `groupe-cm-${groupe.id}`,
          label: `${groupe.libelle}`,
          type: 'CM'
        }));

    // Créer des sections avec des en-têtes pour chaque type de groupe
    const schedulesArray = [];

    // Ajouter la section CM si elle contient des groupes
    if (cmGroups.length > 0) {
      // Ajouter les groupes CM
      schedulesArray.push(...cmGroups);
    }

    // Ajouter la section TD si elle contient des groupes
    if (tdGroups.length > 0) {
      // Ajouter les groupes TD
      schedulesArray.push(...tdGroups);
    }

    // Ajouter la section TP si elle contient des groupes
    if (tpGroups.length > 0) {
      // Ajouter les groupes TP
      schedulesArray.push(...tpGroups);
    }

    // Assigner le tableau de schedules
    schedules.value = schedulesArray;
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
  if (isLoadingEvents.value) {
    console.warn('Une requête est déjà en cours, la nouvelle requête ne sera pas exécutée.');
    return;
  }
  if (weekUnivNumber.value === undefined || weekUnivNumber.value === null) {
    console.warn('Numéro de semaine non défini, la requête ne sera pas exécutée.');
    return;
  }

  try {
    isLoadingEvents.value = true;
    const response = await getSemestreEdtWeekEventsService(
        weekUnivNumber.value,
        selectedSemestre.value.id,
        anneeUniv.id,
        departement.id
    );
    if (response && response.length > 0) {
      const mappedEvents = response.map(event => {
        const startDate = new Date(event.debut);
        const endDate = new Date(event.fin);

        return {
          ...event,
          ongoing: new Date(startDate.getTime() + startDate.getTimezoneOffset() * 60000) <= new Date() && new Date(endDate.getTime() + endDate.getTimezoneOffset() * 60000) >= new Date(),
          start: new Date(startDate.getTime() + startDate.getTimezoneOffset() * 60000), // Ajustement du fuseau horaire
          end: new Date(endDate.getTime() + endDate.getTimezoneOffset() * 60000), // Ajustement du fuseau horaire
          backgroundColor: "white",
          location: event.salle,
          title: event.codeModule + ' - ' + event.libModule,
          type: event.groupe.type,
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
          schedule: event.groupe.id || null,
        };
      });

      // Process events to assign them to the correct schedule based on group type
      const processedEvents = [];

      mappedEvents.forEach(event => {
        // Create a base event with overlap detection
        const baseEvent = {
          ...event,
          title: detectOverlap(event, mappedEvents) ? event.codeModule : event.title,
          overlap: !!detectOverlap(event, mappedEvents),
        };
        // Find the group in our groupes array to get its type
        const groupe = groupes.value.find(g => g.id === event.groupe.id);
        const groupeType = event.type;

        // Assign the event to its corresponding schedule based on group type
        // Make sure the schedule exists and is not a header
        const scheduleExists = schedules.value.some(s =>
            s.id === event.groupe.id
        );

        if (scheduleExists) {
          baseEvent.schedule = event.groupe.id;
        } else {
          // If the schedule doesn't exist, log a warning
          console.warn(`Schedule not found for event: ${event.title}, group ID: ${event.groupe.id}`);
        }

        // Add the event to the processed events array
        processedEvents.push(baseEvent);
      });

      events.value = processedEvents;
    } else {
      events.value = [];
    }
  } catch (error) {
    console.error('Error fetching events:', error);
  } finally {
    await nextTick();
    const eventsObjects = document.querySelectorAll('.vuecal__event');
    eventsObjects.forEach(eventEl => {
      // Get the event data from the vue-cal events array
      const eventId = eventEl.getAttribute('data-event-id');
      const eventData = events.value.find(e => e._eid === eventId);

      if (!eventData) return;

      // Find the group in our groupes array to get its type
      const groupeType = eventData.type;
      console.log(groupeType);

      // Apply white and gray styles for all events as requested
      eventEl.style.backgroundColor = 'white';
      eventEl.style.opacity = 0.9;

      // Apply different border styles based on group type, but using gray shades
      if (groupeType === 'TP') {
        eventEl.style.border = '2px solid #bdbdbd'; // Medium gray for TP
        eventEl.style.borderTop = '6px solid #9e9e9e'; // Darker gray for TP top border
      } else if (groupeType === 'TD') {
        eventEl.style.border = '2px solid #bdbdbd'; // Medium gray for TD
        eventEl.style.borderTop = '6px solid #9e9e9e'; // Darker gray for TD top border
      } else if (groupeType === 'CM') {
        eventEl.style.border = '2px solid #bdbdbd'; // Medium gray for CM
        eventEl.style.borderTop = '6px solid #9e9e9e'; // Darker gray for CM top border
      } else {
        // Default styling for other types
        eventEl.style.border = '2px solid #bdbdbd'; // Medium gray
        eventEl.style.borderTop = '6px solid #9e9e9e'; // Darker gray
      }

      // Common styles for all events (from EdtPerso)
      eventEl.style.borderRadius = '0.75rem'; // rounded-xl
      eventEl.style.padding = '0'; // p-0
      eventEl.style.opacity = '0.9';
      eventEl.style.transition = 'all 0.2s ease-in-out';

      // Add a badge with the group type
      const badge = document.createElement('div');
      badge.className = 'event-badge';
      badge.textContent = groupeType || 'Groupe';
      badge.style.position = 'absolute';
      badge.style.top = '2px';
      badge.style.right = '2px';
      badge.style.fontSize = '0.7rem';
      badge.style.padding = '2px 4px';

      // Set badge color to gray for all group types
      badge.style.backgroundColor = '#9e9e9e'; // Darker gray for all badges
      badge.style.color = 'white';
      badge.style.borderRadius = '4px';
      badge.style.zIndex = '101';

      // Only add if it doesn't already exist
      if (!eventEl.querySelector('.event-badge')) {
        eventEl.appendChild(badge);
      }
    });
    isLoadingEvents.value = false;
  }
};
</script>

<template>
  <SimpleSkeleton v-if="isLoadingSemestres" class="w-1/3" />
  <Select
      v-else
      v-model="selectedSemestre"
      :options="semestresList"
      optionLabel="libelle"
      placeholder="Sélectionner un semestre"
      class="w-full"
  ></Select>
  <ListSkeleton v-if="isLoadingGroupes || isLoadingEvents" class="w-full" />
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
      :schedules="schedules">
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
      <EdtEvent :event="event" type="departement" />
    </template>
  </vue-cal>
</template>

<style scoped>
:deep(.vuecal__event) {
  scrollbar-width: none;
  @apply rounded-xl text-sm text-black !overflow-scroll p-0 transition duration-200 ease-in-out border;
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
  @apply bg-gray-300 bg-opacity-20 py-4 rounded-md flex flex-col items-center uppercase;
}

:deep(.vuecal__weekdays-headings) {
  @apply flex justify-between items-center gap-2;
}

:deep(.vuecal--day-view .vuecal__scrollable-wrap .vuecal__scrollable .vuecal__time-column) {
  @apply p-6;
}

/* Style schedule columns based on group type */
:deep(.vuecal__schedule[class*="groupe-tp-"]) {
  background-color: rgba(144, 202, 249, 0.2); /* Light blue background for TP */
}

:deep(.vuecal__schedule[class*="groupe-td-"]) {
  background-color: rgba(129, 199, 132, 0.2); /* Light green background for TD */
}

:deep(.vuecal__schedule[class*="groupe-cm-"]) {
  background-color: rgba(255, 183, 77, 0.2); /* Light orange background for CM */
}

/* Style schedule headings */
:deep(.vuecal__schedule--heading) {
  color: rgba(0, 0, 0, 0.7);
  font-size: 0.9rem;
  font-weight: bold;
  padding: 4px;
  border-radius: 4px;
}

/* Style schedule headings based on group type */
:deep(.vuecal__schedule--heading[class*="groupe-tp-"]) {
  background-color: rgba(144, 202, 249, 0.3);
  border-bottom: 2px solid #1976D2;
}

:deep(.vuecal__schedule--heading[class*="groupe-td-"]) {
  background-color: rgba(129, 199, 132, 0.3);
  border-bottom: 2px solid #388E3C;
}

:deep(.vuecal__schedule--heading[class*="groupe-cm-"]) {
  background-color: rgba(255, 183, 77, 0.3);
  border-bottom: 2px solid #F57C00;
}

/* Style schedule headers */
:deep(.vuecal__schedule--heading.schedule-header) {
  font-size: 1.1rem;
  font-weight: bold;
  text-align: center;
  padding: 8px;
  margin-top: 8px;
  border-radius: 8px;
  grid-column: 1 / -1; /* Span all columns */
  background-color: #f5f5f5;
  border: none;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Style specific schedule headers */
:deep(.vuecal__schedule--heading.tp-header) {
  background-color: rgba(144, 202, 249, 0.5);
  color: #0d47a1;
}

:deep(.vuecal__schedule--heading.td-header) {
  background-color: rgba(129, 199, 132, 0.5);
  color: #1b5e20;
}

:deep(.vuecal__schedule--heading.cm-header) {
  background-color: rgba(255, 183, 77, 0.5);
  color: #e65100;
}

.vuecal__event {color: #fff;border: 1px solid;}
.vuecal__event.leisure {background-color: #fd9c42d9;border-color: #e9882e;}
.vuecal__event.health {background-color: #57cea9cc;border-color: #90d2be;}
.vuecal__event.sport {background-color: #ff6666d9;border-color: #eb5252;}
</style>
