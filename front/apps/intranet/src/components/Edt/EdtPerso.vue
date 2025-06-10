<script setup>
import {VueCal} from 'vue-cal'
import 'vue-cal/style'

import {onMounted, ref, watch} from 'vue'
import {getPersonnelEdtWeekEventsService} from "@requests";
import {useUsersStore} from "@stores";

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

const events1 = ref([
  {
    id: 1,
    title: 'Hello',
    start: new Date(2025, 5, 5, 9, 30),
    end: new Date(2025, 5, 5, 11, 0),
    backgroundColor: 'rgba(255,204,204)',
    location: 'H001',
  },
  {
    id: 2,
    title: 'Hello1',
    start: new Date(2025, 5, 5, 10, 0),
    end: new Date(2025, 5, 5, 11, 0),
    backgroundColor: 'rgba(204,237,255)',
    location: 'H001',
  },
  {
    id: 3,
    title: 'Hello2',
    start: new Date(2025, 5, 5, 12, 0),
    end: new Date(2025, 5, 5, 13, 30),
    backgroundColor: 'rgba(204,255,204)',
    location: 'H001',
  },
  {
    id: 4,
    title: 'Hello3',
    start: new Date(2025, 5, 5, 12, 0),
    end: new Date(2025, 5, 5, 13, 30),
    backgroundColor: 'rgba(255,233,204)',
    location: 'H001',
  },
  {
    id: 5,
    title: 'Hello4',
    start: new Date(2025, 5, 6, 14, 0),
    end: new Date(2025, 5, 6, 15, 30),
    backgroundColor: 'rgba(204,204,255)',
    location: 'H001',
  },
  {
    id: 6,
    title: 'Hello5',
    start: new Date(2025, 5, 4, 8, 0),
    end: new Date(2025, 5, 4, 11, 0),
    backgroundColor: 'rgba(204,204,255)',
    location: 'H001',
  },
  {
    id: 7,
    title: 'Hello6',
    start: new Date(2025, 5, 2, 8, 0),
    end: new Date(2025, 5, 2, 11, 0),
    backgroundColor: 'rgba(204,204,255)',
    location: 'H001',
  },
  {
    id: 8,
    title: 'Hello7',
    start: new Date(2025, 5, 5, 8, 0),
    end: new Date(2025, 5, 5, 9, 30),
    backgroundColor: 'rgba(255,233,204)',
    location: 'H001',
  }
]);

// todo: pourquoi je dois faire un +2 pour avoir le même que la V3 ?
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
  weekUnivNumber.value = Math.floor(diffInDays / 7) + 2;
  return weekUnivNumber;
};

function darkenColor(color, amount) {
  const [r, g, b] = color.match(/\d+/g).map(Number);
  return `rgb(${Math.max(r - amount, 0)}, ${Math.max(g - amount, 0)}, ${Math.max(b - amount, 0)})`;
}

// todo: un switch entre cours et agenda + ajouter un event

// Watcher pour mettre à jour le numéro de semaine universitaire lorsque la vue change
watch (() => vuecalRef.value?.view?.start, (newValue) => {
  if (newValue) {
    getWeekUnivNumber(new Date(newValue));
    getEventsPersonnelWeek()
  }
}, { immediate: true });

// méthode pour transformer une couleur de son nom textuel (exemple : 'red') en son code RGB
function colorNameToRgb(colorName) {
  const ctx = document.createElement('canvas').getContext('2d');
  ctx.fillStyle = colorName;
  // La valeur retournée est sous la forme 'rgb(r, g, b)' ou 'rgba(r, g, b, a)'
  return ctx.fillStyle.startsWith('#')
    ? hexToRgb(ctx.fillStyle)
    : ctx.fillStyle;
}

// Helper pour convertir un hex en rgb
function hexToRgb(hex) {
  let c = hex.substring(1);
  if (c.length === 3) c = c.split('').map(x => x + x).join('');
  const num = parseInt(c, 16);
  return `rgb(${(num >> 16) & 255}, ${(num >> 8) & 255}, ${num & 255}, 0.3)`;
}

const getEventsPersonnelWeek = async () => {
  try {
    const response = await getPersonnelEdtWeekEventsService(weekUnivNumber.value, personnel.id, anneeUniv.id, departement.id);
    if (response && response.length > 0) {
      console.log("response", response);
      events.value = response.map(event => ({
        ...event,
        start: new Date(event.debut),
        end: new Date(event.fin),
        backgroundColor: colorNameToRgb(event.couleur),
        location: event.salle,
        title: event.codeModule + ' - ' + event.libModule,
        type: event.type,
        groupe: event.libGroupe || 'no grp.',
      }));
    } else {
      events.value = [];
    }
  } catch (error) {
    console.error('Error fetching events:', error);
  }
};

onMounted(() => {
  getEventsPersonnelWeek();
});
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
        :stack-events="false"
        :views="['day', 'week']"
        :default-view="'week'"
        :theme="false"
        diy
        :events="events"
    >
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
        <div class="border-t-4 rounded-lg dark:text-white" :style="{ borderTopColor: darkenColor(event.backgroundColor, 50) }">
          <div class="p-4">
            <div class="title font-bold">{{ event.title }}</div>
            <div class="time">
              {{ event.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }} - {{ event.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
            </div>
            <div v-if="event.location" class="location">
              <i class="mdi mdi-map-marker"></i> {{ event.location }}
            </div>
            <div v-if="event.type" class="type">
              <i class="pi pi-users"></i> {{ event.type }} {{ event.groupe }}
            </div>
          </div>
        </div>
      </template>
    </vue-cal>
</template>

<style scoped>
:deep(.vuecal__event) {
  @apply rounded-md text-sm text-black;
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
