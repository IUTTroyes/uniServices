<script setup>
import { computed, ref } from 'vue';
import { adjustColor, darkenColor } from "@helpers/colors.js";
import { PhotoUser } from "@components";
import { useUsersStore } from "@stores/user_stores/userStore.js";
import EventAppel from "./EventAppel.vue";

defineOptions({ inheritAttrs: false });

const props = defineProps({
  event: {
    type: Object,
    required: true
  },
  type: {
    type: String,
    required: true,
    validator: (value) => ['perso', 'jour', 'departement', 'etudiant'].includes(value)
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

// GÉRER DEUX DIALOGUES DISTINCTS
const dialogAppelVisible = ref(false);
const dialogPlanVisible = ref(false);
const selectedEvent = ref(null);

const openDialog = (dialogType, ev) => {
  selectedEvent.value = ev || props.event;
  if (dialogType === 'appel') {
    dialogAppelVisible.value = true;
  } else if (dialogType === 'plan') {
    dialogPlanVisible.value = true;
  } else {
    console.log(`Open dialog for event type: ${dialogType}`);
  }
};
</script>

<template>
  <!-- EdtPerso Event Template -->
  <div v-if="type === 'perso'" class="rounded-lg !h-full" v-on="$attrs">
    <div class="p-2 flex flex-col justify-between h-full gap-1">
      <div>
        <div class="title font-black">{{ event.title }}</div>
        <div class="flex gap-1 items-center">
          <Badge :style="{ backgroundColor: event.backgroundColor ? adjustColor(darkenColor(event.typeColor, 60), 0, 0.2) : '' }" class="!text-white">
            {{ event.type }}
          </Badge>
          {{ event.semestre?.libelle }} | {{ event.groupe?.libelle }}
        </div>
        <div>{{ event.location }}</div>
      </div>
      <div v-if="event.overlap" class="flex flex-col gap-2">
        <div>{{ formattedTime }}</div>
        <div class="flex gap-2">
          <Button icon="pi pi-list" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Appel" size="small" v-tooltip.top="'Faire l\'appel'" @click.stop="openDialog('appel', event)"></Button>
          <Button icon="pi pi-check-circle" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Tous présents" size="small" v-tooltip.top="'Marquer tout le monde présents'" @click.stop></Button>
          <Button icon="pi pi-book" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Plan de cours" size="small" v-tooltip.top="'Voir le plan de cours'" @click.stop="openDialog('plan', event)"></Button>
        </div>
      </div>
      <div v-else class="flex justify-between items-center flex-wrap gap-2">
        <div class="flex gap-2">
          <Button icon="pi pi-list" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Appel" size="small" v-tooltip.top="'Faire l\'appel'" @click.stop="openDialog('appel', event)"></Button>
          <Button icon="pi pi-check-circle" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Tous présents" size="small" v-tooltip.top="'Marquer tout le monde présents'" @click.stop></Button>
          <Button icon="pi pi-book" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Plan de cours" size="small" v-tooltip.top="'Voir le plan de cours'" @click.stop="openDialog('plan', event)"></Button>
        </div>
        <div class="flex flex-col items-center">
          <Badge v-if="event.evaluation" severity="danger" class="uppercase">éval.</Badge>
          <div class="opacity-60">{{ formattedTime }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- EdtJour Event Template -->
  <div v-else-if="type === 'jour'" class="h-full min-h-32" v-on="$attrs">
    <div v-if="event.text === 'Aucun cours'" class="flex flex-row justify-start items-start rounded-xl h-full relative text-black text-color palm bg-neutral-400 bg-opacity-20">
      <div class="flex flex-col justify-center p-4 gap-2">
        <div v-if="event.dayoff" class="text-lg font-bold">Aucun événement aujourd'hui</div>
        <div v-else class="text-lg font-bold">Aucun événement en cours</div>
      </div>
      <Tag v-if="event.isFirst" value="Événement en cours" class="absolute right-2 bottom-2 !text-white !bg-black"/>
    </div>
    <div v-else
         :class="['flex flex-row justify-start items-start rounded-xl h-full relative text-black transition duration-200 ease-in-out border-4 !border-transparent hover:!border-4 hover:!border-primary-500 hover:transition hover:duration-200 hover:ease-in-out hover:cursor-pointer hover:shadow-md',
               {'!border-4 !border-primary-300': event.isFirst},
               {'text-color palm': event.text === 'Aucun cours'}]"
         :style="{ borderColor: event.colorFocus, backgroundColor: event.backgroundColor }">
      <div class="flex flex-col justify-center items-center p-4 rounded-l-lg h-full" :style="{ backgroundColor: event.colorFocus }">
        <div v-if="!isToday" class="text-sm font-bold flex flex-col items-center">
          <span>{{ new Date(event.debut).toLocaleDateString('fr-FR', {weekday: 'long'}) }}</span>
          <span>{{ new Date(event.debut).toLocaleDateString('fr-FR') }}</span>
        </div>
        <div v-else class="text-sm font-bold">Aujourd'hui</div>
        <div class="text-xl font-black">{{ event.debut?.split('T')[1]?.slice(0, 5) }}</div>
        <div class="text-xl font-black">{{ event.fin?.split('T')[1]?.slice(0, 5) }}</div>
      </div>
      <div class="flex flex-col justify-center p-4 gap-2">
        <div class="text-lg font-bold">{{ event.title || event.text }}</div>
        <div class="flex gap-1 items-center">
          <Badge :style="{ backgroundColor: event.backgroundColor ? adjustColor(darkenColor(event.colorFocus, 60), 0, 0.2) : '' }" class="!text-white">
            {{ event.type }}
          </Badge>
          {{ event.semestre?.libelle }} | {{ event.groupe?.libelle }}
        </div>
        <div>
          {{ event.salle }}
        </div>
        <div class="flex gap-2">
          <Button icon="pi pi-list" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Appel" size="small" v-tooltip.top="'Faire l\'appel'" @click.stop="openDialog('appel', event)"></Button>
          <Button icon="pi pi-check-circle" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Tous présents" size="small" v-tooltip.top="'Marquer tout le monde présents'" @click.stop></Button>
          <Button icon="pi pi-book" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Plan de cours" size="small" v-tooltip.top="'Voir le plan de cours'" @click.stop="openDialog('plan', event)"></Button>
        </div>
      </div>
      <Tag v-if="event.isFirst" value="Événement en cours" class="absolute right-2 bottom-2 !text-white" :style="{ backgroundColor: event.backgroundColor ? adjustColor(darkenColor(event.colorFocus, 60), 0, 0.2) : '' }"/>
      <Tag v-else-if="!event.isFirst" value="Prochain évènement" class="absolute right-2 bottom-2 !text-black" :style="{ backgroundColor: event.colorFocus }"/>
    </div>
  </div>

  <!-- EdtDepartement Event Template -->
  <div v-else-if="type === 'departement'" class="rounded-lg !h-full" v-on="$attrs">
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

      <div v-if="user.id === event.personnel.id" class="flex gap-2 z-20">
        <Button icon="pi pi-list" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Appel" size="small" v-tooltip.top="'Faire l\'appel'" @click="openDialog('appel', event)"></Button>
        <Button icon="pi pi-check-circle" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Tous présents" size="small" v-tooltip.top="'Marquer tout le monde présents'" @click.stop></Button>
        <Button icon="pi pi-book" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Plan de cours" size="small" v-tooltip.top="'Voir le plan de cours'" @click="openDialog('plan', event)"></Button>
      </div>
    </div>
  </div>

  <!-- EdtEtudiant Event Template -->
  <div v-else-if="type === 'etudiant'" class="rounded-lg !h-full" v-on="$attrs">
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

  <!-- DIALOG POUR L'APPEL -->
  <Dialog header="Faire l'appel"
          v-model:visible="dialogAppelVisible"
          :modal="true"
          closable
          :style="{ width: '70vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
    <EventAppel :event="event" />
  </Dialog>

  <!-- DIALOG POUR LE PLAN DE COURS -->
  <Dialog header="Plan de cours"
          v-model:visible="dialogPlanVisible"
          :modal="true"
          closable
          :style="{ width: '70vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
    <div>
      <p>Contenu du plan de cours pour l'événement {{ selectedEvent ? selectedEvent.title : '' }}</p>
      <!-- Ajouter ici l'aperçu du plan de cours ou embed -->
    </div>
  </Dialog>
</template>

<style scoped>
.palm {
  background-image: url("@/assets/illu/palm.svg");
  background-size: 50%;
  background-repeat: no-repeat;
  background-position: right;
}
</style>
