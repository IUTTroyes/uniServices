<script setup>
import { computed } from 'vue';
import { adjustColor, darkenColor } from "@helpers/colors.js";
import { PhotoUser } from "@components";
import { useUsersStore } from "@stores/user_stores/userStore.js";

const props = defineProps({
  event: {
    type: Object,
    required: true
  },
  type: {
    type: String,
    required: true,
    validator: (value) => ['perso', 'jour', 'departement'].includes(value)
  }
});

const userStore = useUsersStore();

const user = computed(() => userStore.user);

const formattedTime = computed(() => {
  if (!props.event.start || !props.event.end) return '';

  const startTime = props.event.start instanceof Date
    ? props.event.start.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    : props.event.debut?.split('T')[1]?.slice(0, 5) || '';

  const endTime = props.event.end instanceof Date
    ? props.event.end.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    : props.event.fin?.split('T')[1]?.slice(0, 5) || '';

  return `${startTime} - ${endTime}`;
});

// Computed property to determine if the event is today
const isToday = computed(() => {
  if (!props.event.start && !props.event.debut) return false;

  const eventDate = props.event.start instanceof Date
    ? props.event.start
    : new Date(props.event.debut);

  const today = new Date();
  return today.toDateString() === eventDate.toDateString();
});
</script>

<template>
  <!-- EdtPerso Event Template -->
  <div v-if="type === 'perso'" class="rounded-lg !h-full">
    <div class="p-2 flex flex-col justify-between h-full gap-1">
      <div>
        <div class="title font-black">{{ event.title }}</div>
        <div class="flex gap-1 items-center">
          <Badge :style="{ backgroundColor: event.backgroundColor ? adjustColor(darkenColor(event.backgroundColor, 60), 0, 0.2) : '' }">
            {{ event.type }}
          </Badge>
          {{ event.semestre?.libelle }} | {{ event.groupe?.libelle }}
        </div>
        <div>{{ event.location }}</div>
      </div>
      <div v-if="event.overlap" class="flex flex-col gap-2">
        <div>{{ formattedTime }}</div>
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
          <div class="opacity-60">{{ formattedTime }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- EdtJour Event Template -->
  <div v-else-if="type === 'jour'"
       :class="['flex flex-row justify-between items-start rounded-lg h-full relative text-black',
               {'border-4': event.isFirst && event.text !== 'Aucun cours'},
               {'text-color palm': event.text === 'Aucun cours'}]"
       :style="{ borderColor: event.colorFocus, backgroundColor: event.color }">
    <div v-if="event.text !== 'Aucun cours'" class="flex flex-col justify-center items-center p-4 rounded-l-md h-full" :style="{ backgroundColor: event.colorFocus }">
      <div v-if="!isToday" class="text-sm font-bold flex flex-col items-center">
        <span>{{ new Date(event.debut).toLocaleDateString('fr-FR', {weekday: 'long'}) }}</span>
        <span>{{ new Date(event.debut).toLocaleDateString('fr-FR') }}</span>
      </div>
      <div v-else class="text-sm font-bold">Aujourd'hui</div>
      <div class="text-xl font-black">{{ event.debut?.split('T')[1]?.slice(0, 5) }}</div>
      <div class="text-xl font-black">{{ event.fin?.split('T')[1]?.slice(0, 5) }}</div>
    </div>
    <div class="flex flex-col justify-center p-4 gap-2">
      <div class="text-lg font-bold">{{ event.text }}</div>
      <div v-if="event.text !== 'Aucun cours'">
        <div>{{ event.semestre }} | <span class="font-bold">{{ event.groupe }}</span></div>
        <div class="text-lg font-bold">{{ event.salle }}</div>
      </div>
    </div>
    <Tag v-if="event.isFirst" value="Événement en cours" class="absolute right-2 bottom-2 !text-white !bg-black"/>
    <Tag v-else-if="!event.isFirst && event.text !== 'Aucun cours'" value="Prochain évènement" class="absolute right-2 bottom-2 !text-white !bg-black"/>
  </div>

  <!-- EdtDepartement Event Template -->
  <div v-else-if="type === 'departement'" class="rounded-lg !h-full">
    <div class="p-2 flex flex-col justify-between h-full gap-1">
      <div>
        <div class="flex justify-between items-start gap-1">
          <div class="title font-black">{{ event.title }}</div>
          <div v-if="user.id === event.personnel.id">
            <Badge severity="primary">Vous</Badge>
          </div>
        </div>
        <div class="flex gap-1 items-center">
          <Badge :style="{ backgroundColor: event.backgroundColor ? adjustColor(darkenColor(event.backgroundColor, 100), 0.1, 0) : '' }">
            {{ event.type }}
          </Badge>
          {{ event.groupe?.libelle }}
          |
          <div>{{ event.location }}</div>
        </div>
      </div>
      <div v-if="event.overlap" class="flex flex-col gap-2">
        <div>{{ formattedTime }}</div>
        <div class="flex items-center gap-2 text-xs">
          <PhotoUser :user-photo="event.intervenantPhoto" class="!w-6 border border-gray-400"/>
          {{ event.personnel?.displayCourt || 'Inconnu' }}
        </div>
      </div>
      <div v-else class="flex justify-between items-center flex-wrap gap-2">
        <div class="flex items-center gap-2 text-xs">
          <PhotoUser :user-photo="event.intervenantPhoto" class="!w-6 border border-gray-400" />
          {{ event.personnel?.displayCourt || 'Inconnu' }}
        </div>
        <div class="flex flex-col items-center">
          <Badge v-if="event.evaluation" severity="danger" class="uppercase">éval.</Badge>
          <div class="opacity-60">{{ formattedTime }}</div>
        </div>
      </div>

      <div v-if="user.id === event.personnel.id" class="flex gap-2">
        <Button icon="pi pi-list" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Appel" size="small" v-tooltip.top="'Faire l\'appel'"></Button>
        <Button icon="pi pi-check-circle" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Tous présents" size="small" v-tooltip.top="'Marquer tout le monde présents'"></Button>
        <Button icon="pi pi-book" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Plan de cours" size="small" v-tooltip.top="'Voir le plan de cours'"></Button>
      </div>
    </div>
  </div>

  <!-- EdtEtudiant Event Template -->
  <div v-else-if="type === 'etudiant'" class="rounded-lg !h-full">
    <div class="p-2 flex flex-col justify-between h-full gap-1">
      <div>
        <div class="title font-black">{{ event.title }}</div>
        <div class="flex gap-1 items-center">
          <Badge class="!text-black" :style="{ backgroundColor: event.backgroundColor ? adjustColor(darkenColor(event.backgroundColor, 60), 0, 0.2) : '' }">
            {{ event.type }}
          </Badge>
<!--          {{ event.semestre?.libelle }} | -->
          {{ event.groupe?.libelle }}
        </div>
        <div>{{ event.location }}</div>
      </div>
      <div v-if="event.overlap" class="flex flex-col gap-2">
        <div>{{ formattedTime }}</div>
        <div class="flex items-center gap-2 text-xs">
          <PhotoUser :user-photo="event.intervenantPhoto" class="!w-6 border border-gray-400"/>
          {{ event.personnel?.display || 'Inconnu' }}
        </div>
      </div>
      <div v-else class="flex justify-between items-center flex-wrap gap-2">
        <div class="flex items-center gap-2 text-xs">
          <PhotoUser :user-photo="event.intervenantPhoto" class="!w-6 border border-gray-400" />
          {{ event.personnel?.display || 'Inconnu' }}
        </div>
        <div class="flex flex-col items-center">
          <Badge v-if="event.evaluation" severity="danger" class="uppercase">éval.</Badge>
          <div class="opacity-60">{{ formattedTime }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
