<script setup>
import {VueCal} from 'vue-cal';
import 'vue-cal/style';

import {nextTick, onMounted, reactive, ref, watch} from 'vue';

// Référence vers le composant vue-cal
const vuecalRef = ref(null)

const viewTranslations = {
  day: 'JOUR',
  week: 'SEMAINE',
};

const schedules = ref( [
  { id: 1, class: 'mom', label: 'Mom' },
  { id: 2, class: 'dad', label: 'Dad', hide: false },
  { id: 3, class: 'kid1', label: 'Kid 1' },
  { id: 4, class: 'kid2', label: 'Kid 2' },
  { id: 5, class: 'kid3', label: 'Kid 3' }
]);

const events = ref([
  {
    start: new Date(2025, 5, 5, 8, 0), // Juin (index 5)
    end: new Date(2025, 5, 5, 11, 0),   // Juin (index 5)
    title: 'Doctor appointment',
    backgroundColor: 'rgba(255,204,204,0.5)',
    location: 'H001',
    schedule: 1
  },
  {
    start: new Date(2025, 5, 5, 10, 0), // Juin (index 5)
    end: new Date(2025, 5, 5, 11, 0),   // Juin (index 5)
    title: 'Dentist appointment',
    backgroundColor: 'rgba(255,204,204,0.5)',
    location: 'H001',
    schedule: 2
  },
  {
    start: new Date(2025, 5, 5, 12, 0),
    end: new Date(2025, 5, 5, 13, 30),
    title: 'Cross-fit',
    backgroundColor: 'rgba(255,204,204,0.5)',
    location: 'H001',
    schedule: 1
  }
])

//---------------------------------
//---------------------------------
//---------------------------------
//---------------------------------
import { useSemestreStore, useUsersStore } from '@stores';
import { SimpleSkeleton } from '@components';
import {getISOWeekNumber} from "@helpers/date";
import {getSemestreEdtWeekEventsService, getSemaineUniversitaireService} from "@requests";
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
    console.log('semestres', semestresList.value);
  }
};

watch(selectedSemestre, async (newValue) => {
  schedules.value = (newValue.groupes || [])
    .filter(groupe => groupe.type === 'TP')
    .map(groupe => ({
      id: groupe.id,
      class: `groupe-${groupe.id}`,
      label: groupe.libelle,
    }));

  getEventsDepartementWeek()
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

const getEventsDepartementWeek = async () => {
  if (!selectedSemestre.value) {
    console.warn('Aucun semestre sélectionné, la requête ne sera pas exécutée.');
    return;
  }

  try {
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
  <vue-cal
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
      <div class="custom-event-content">
        <div class="title font-bold">{{ event.title }}</div>
        <div class="time">
          {{ event.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }} - {{ event.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
        </div>
        <div v-if="event.location" class="location">
          <i class="mdi mdi-map-marker"></i> {{ event.location }}
        </div>
      </div>
    </template>
  </vue-cal>
</template>

<style scoped>
:deep(.vuecal__event) {
  @apply p-4 rounded-md text-sm;
}
:deep(.vuecal__body) {
  @apply gap-2;
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

.vuecal__schedule.dad {background-color: rgba(221, 238, 255, 0.5);}
.vuecal__schedule.mom {background-color: rgba(255, 232, 251, 0.5);}
.vuecal__schedule.kid1 {background-color: rgba(221, 255, 239, 0.5);}
.vuecal__schedule.kid2 {background-color: rgba(255, 250, 196, 0.5);}
.vuecal__schedule.kid3 {background-color: rgba(255, 206, 178, 0.5);}
.vuecal__schedule--heading {color: rgba(0, 0, 0, 0.5);font-size: 26px;}

.vuecal__event {color: #fff;border: 1px solid;}
.vuecal__event.leisure {background-color: #fd9c42d9;border-color: #e9882e;}
.vuecal__event.health {background-color: #57cea9cc;border-color: #90d2be;}
.vuecal__event.sport {background-color: #ff6666d9;border-color: #eb5252;}
</style>
