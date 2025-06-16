<script setup>
import {VueCal} from 'vue-cal'
import 'vue-cal/style'

import {onMounted, ref, watch, nextTick} from 'vue'
import {getPersonnelEdtWeekEventsService, getSemaineUniversitaireService} from "@requests";
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

function getISOWeekNumber(date) {
  const tempDate = new Date(date.getTime());
  tempDate.setHours(0, 0, 0, 0);
  tempDate.setDate(tempDate.getDate() + 3 - ((tempDate.getDay() + 6) % 7));
  const week1 = new Date(tempDate.getFullYear(), 0, 4);
  return 1 + Math.round(((tempDate - week1) / 86400000 - 3 + ((week1.getDay() + 6) % 7)) / 7);
}

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

function darkenColor(color, amount) {
  const [r, g, b] = color.match(/\d+/g).map(Number);
  return `rgb(${Math.max(r - amount, 0)}, ${Math.max(g - amount, 0)}, ${Math.max(b - amount, 0)})`;
}

function adjustColor(color, lightenAmount, reduceSaturationAmount) {
  const ctx = document.createElement('canvas').getContext('2d');
  ctx.fillStyle = color;
  const rgb = ctx.fillStyle.startsWith('#') ? hexToRgb(ctx.fillStyle) : ctx.fillStyle;

  const [r, g, b] = rgb.match(/\d+/g).map(Number);
  const hsl = rgbToHsl(r, g, b);

  hsl[1] = Math.max(hsl[1] - reduceSaturationAmount, 0.5);
  hsl[2] = Math.min(hsl[2] + lightenAmount, 0.95);

  return hslToRgb(hsl[0], hsl[1], hsl[2]);
}

function rgbToHsl(r, g, b) {
  r /= 255; g /= 255; b /= 255;
  const max = Math.max(r, g, b), min = Math.min(r, g, b);
  let h, s, l = (max + min) / 2;

  if (max === min) {
    h = s = 0; // Couleur neutre
  } else {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) {
      case r: h = (g - b) / d + (g < b ? 6 : 0); break;
      case g: h = (b - r) / d + 2; break;
      case b: h = (r - g) / d + 4; break;
    }
    h /= 6;
  }

  return [h, s, l];
}

function hslToRgb(h, s, l) {
  let r, g, b;

  if (s === 0) {
    r = g = b = l; // Couleur neutre
  } else {
    const hue2rgb = (p, q, t) => {
      if (t < 0) t += 1;
      if (t > 1) t -= 1;
      if (t < 1 / 6) return p + (q - p) * 6 * t;
      if (t < 1 / 2) return q;
      if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
      return p;
    };

    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    r = hue2rgb(p, q, h + 1 / 3);
    g = hue2rgb(p, q, h);
    b = hue2rgb(p, q, h - 1 / 3);
  }

  return `rgb(${Math.round(r * 255)}, ${Math.round(g * 255)}, ${Math.round(b * 255)})`;
}

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
  return `rgb(${(num >> 16) & 255}, ${(num >> 8) & 255}, ${num & 255}, 1)`;
}

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
          groupe: event.libGroupe || 'no grp.',
          personnel: event.personnel,
          intervenantPhoto: event.personnel.photoName ?? null,
        };
      });

      events.value = mappedEvents.map(event => ({
        ...event,
        title: detectOverlap(event, mappedEvents) ? event.codeModule : event.title,
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
</script>

<template>

  <Dialog v-model:visible="visible" header="Détails d'un cours" class="!bg-gray-50 dark:!bg-gray-800 !border-2 !border-primary-500" :style="{ width: '25vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
    <div>
      {{selectedEvent.title}}
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
      <div v-if="event.ongoing">
        <Tag value="Événement en cours" class="absolute right-2 bottom-2 !text-white !bg-black"/>
      </div>
      <div class="rounded-lg !h-full">
        <div class="p-2 flex flex-col gap-1">
          <div class="title font-bold">{{ event.title }}</div>
          <div>
            {{ event.location }} | {{ event.type }} {{ event.groupe }} | <span class="opacity-70">
            {{ event.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }} - {{ event.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
          </span>
          </div>
          <div class="flex items-center gap-2">
            <PhotoUser :user-photo="event.personnel.photoName" class="!w-8 border-2 border-black"/>
            <div class="text-sm">{{ event.libPersonnel }}</div>
          </div>
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
