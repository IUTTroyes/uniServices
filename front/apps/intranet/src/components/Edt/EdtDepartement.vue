<script setup>
import {VueCal} from 'vue-cal';
import 'vue-cal/style';

import {reactive, ref} from 'vue';

// Référence vers le composant vue-cal
const vuecalRef = ref(null)

const viewTranslations = {
  day: 'JOUR',
  week: 'SEMAINE',
};

const stringToDate = (dateString) => {
  return new Date(dateString);
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

const getWeekUnivNumber = (date) => {
  const startUnivYear = new Date(date.getFullYear(), 8, 1); // 1er septembre
  if (date < startUnivYear) {
    startUnivYear.setFullYear(startUnivYear.getFullYear() - 1);
  }
  // Ajuster pour que la semaine commence le lundi
  const dayOfWeek = (date.getDay() + 6) % 7; // 0 = lundi, 6 = dimanche
  const monday = new Date(date);
  monday.setDate(date.getDate() - dayOfWeek);

  const diffInDays = Math.floor((monday - startUnivYear) / (1000 * 60 * 60 * 24));
  return Math.floor(diffInDays / 7) + 1;
};
</script>

<template>
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
            <span class="text-md text-muted-color">Semaine de formation : {{ getWeekUnivNumber(view.start) }}</span>
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
  @apply p-0;
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
