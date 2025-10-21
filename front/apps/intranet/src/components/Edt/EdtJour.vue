<script setup>
import {onMounted, ref} from 'vue';
import EdtEvent from './EdtEvent.vue';
import {getEdtEventsService, getSemaineUniversitaireService} from "@requests";
import {useUsersStore} from "@stores";
import {getISOWeekNumber} from "@helpers/date.js";

const usersStore = useUsersStore();
const personnel = usersStore.user;
const departement = usersStore.departementDefaut;
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };

const allEvents = ref([]);
const sortedEvents = ref([]);
const weekUnivNumber = ref(0);

const getWeekUnivNumber = async (date) => {
  const calendarWeekNumber = getISOWeekNumber(date); // Calcule le numéro de semaine ISO
  try {
    const response = await getSemaineUniversitaireService(calendarWeekNumber, anneeUniv.id);
    weekUnivNumber.value = response[0]?.semaineFormation || 0; // Définit la semaine de formation
  } catch (error) {
    console.error('Erreur lors de la récupération du numéro de semaine universitaire :', error);
  }
};

const isToday = (dateString) => {
  const today = new Date();
  const date = new Date(dateString);
  return today.toDateString() === date.toDateString();
};

const isEventOngoing = (event) => {
  const now = new Date();
  const start = new Date(event.debut);
  const end = new Date(event.fin);

  console.log('now', now);
  console.log('start', start);
  console.log('end', end);

  return now >= start && now <= end;
};

const getEvents = async (date = new Date()) => {
  try {
    // Format the date as YYYY-MM-DD for the day filter
    const formattedDate = date.toISOString().split('T')[0];

    const params = {
      personnel: personnel?.id || null,
      departement: departement?.id || null,
      anneeUniversitaire: anneeUniv.id || null,
      semaineFormation: weekUnivNumber.value || null,
      'day': formattedDate, // Use the new day filter
      'itemsPerPage': 2 // Use itemsPerPage instead of limit
    };
    const response = await getEdtEventsService(params);
    console.log('API Response:', response);
    allEvents.value = response;

    sortEvents();
  } catch (error) {
    console.error('Erreur lors de la récupération des événements EDT :', error);
  }
};

const sortEvents = () => {
  const ongoingEvent = allEvents.value.find(isEventOngoing);

  if (ongoingEvent) {
    // If there's an ongoing event, show it first, then the next event
    sortedEvents.value = [
      { ...ongoingEvent, isFirst: true },
      ...allEvents.value
        .filter(event => event !== ongoingEvent)
        .slice(0, 1) // Take only one more event
        .map(event => ({ ...event, isFirst: false }))
    ];
  } else {
    // If no ongoing event, show the fallback content and the next events
    sortedEvents.value = [
      { text: 'Aucun cours', color: 'rgba(223,223,223,0.2)', colorFocus: '#FFFFFF', isFirst: true },
      ...allEvents.value
        .slice(0, 1) // Take only one event
        .map(event => ({ ...event, isFirst: false }))
    ];
  }
};

onMounted(async () => {
  const today = new Date();
  await getWeekUnivNumber(today);
  await getEvents();
});
</script>

<template>
    <div class="flex flex-row justify-between gap-4">
        <div v-for="(event, index) in sortedEvents" :key="index" class="w-full">
            <EdtEvent :event="event" type="jour" />
        </div>
    </div>
</template>

<style scoped>
/* Styles moved to EdtEvent.vue */
</style>
