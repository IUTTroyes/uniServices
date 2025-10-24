<script setup>
import {onMounted, ref} from 'vue';
import EdtEvent from './EdtEvent.vue';
import {getEdtEventsService, getEtudiantScolaritesService, getSemaineUniversitaireService} from "@requests";
import {useUsersStore} from "@stores";
import {getISOWeekNumber} from "@helpers/date.js";
import {adjustColor, colorNameToRgb, darkenColor} from "@helpers/colors.js";
import {PhotoUser, ErrorView} from "@components";

const usersStore = useUsersStore();
const user = usersStore.user;
const departement = usersStore.departementDefaut;
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const allEvents = ref([]);
const sortedEvents = ref([]);
const weekUnivNumber = ref(0);
const hasError = ref(false);
const groupes = ref([]);

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
  const startDate = new Date(event.debut);
  const endDate = new Date(event.fin);

  const start = new Date(startDate.getTime() + startDate.getTimezoneOffset() * 60000);
  const end = new Date(endDate.getTime() + endDate.getTimezoneOffset() * 60000);
  return now >= start && now <= end;
};

const getEtudiantGroupes = async () => {
  try {
    const scol = await getEtudiantScolaritesService(user.id, true);

    if (scol && scol.length > 0) {
      user.scolarites = scol;
      // Récupère tous les groupes de chaque scolariteSemestre de chaque scolarite
      user.groupes = scol.flatMap(s =>
          (s.scolariteSemestre || []).flatMap(ss => ss.groupes || [])
      );
      groupes.value = user.groupes;
    } else {
      user.scolarites = [];
      user.groupes = [];
    }
  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors de la récupération des scolarités de l\'étudiant :', error);
  }
};

const getEvents = async (date = new Date()) => {
  try {
    if (usersStore.isEtudiant) {
      await getEtudiantGroupes();
    }
    const personnel = usersStore.isPersonnel ? user : null;

    const formattedDate = date.toISOString().split('T')[0];
    const currentTime = date.toISOString();

    const params = {
      personnel: personnel?.id || null,
      departement: departement?.id || null,
      anneeUniversitaire: anneeUniv.id || null,
      semaineFormation: weekUnivNumber.value || null,
      groupe: groupes.value.map(g => g.id) || null,
      'day': formattedDate,
    };
    const response = await getEdtEventsService(params);

    if (response && response.length > 0) {
      allEvents.value = response
          .filter(event => new Date(event.fin) > new Date(currentTime)) // Exclure les événements déjà terminés
          .map(event => {
            let eventColor;
            if (event.groupe) {
              switch (event.groupe.type) {
                  // couleurs comme dans Celcat
                case 'CM':
                  eventColor = adjustColor(colorNameToRgb(event.couleur), 0.1, 0.2);
                  break;
                case 'TD':
                  eventColor = adjustColor(colorNameToRgb(event.couleur), 0.3, 0.2);
                  break;
                case 'TP':
                  eventColor = adjustColor(colorNameToRgb(event.couleur), 0, 0.2);
                  break;
                default:
                  eventColor = adjustColor(colorNameToRgb(event.couleur), 0.8, 0.2);
              }
            }

            const startDate = new Date(event.debut);
            const endDate = new Date(event.fin);

            return {
              ...event,
              isToday: isToday(event.debut),
              title: event.codeModule + ' - ' + event.libModule,
              start: new Date(startDate.getTime() + startDate.getTimezoneOffset() * 60000),
              end: new Date(endDate.getTime() + endDate.getTimezoneOffset() * 60000),
              backgroundColor: adjustColor(colorNameToRgb(event.couleur), 1, 0.2),
              colorFocus: adjustColor(darkenColor(adjustColor(colorNameToRgb(event.couleur), 1, 0.2), 40), 0, 0.2),
              type: event.type,
              groupe: event.groupe || '**',
            };
          });

      // Trier les événements par heure de début
      allEvents.value.sort((a, b) => new Date(a.debut) - new Date(b.debut));
    }

    console.log('allEvents', allEvents.value);
    sortEvents();
  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors de la récupération des événements EDT :', error);
  }
};

const sortEvents = () => {
  if (allEvents.value.length === 0) {
    sortedEvents.value = [
      {
        text: "Aucun cours",
        isFirst: true,
        backgroundColor: "#f0f0f0",
        colorFocus: "#d0d0d0",
        dayoff: true,
      }
    ];
    return;
  }

  const ongoingEventIndex = allEvents.value.findIndex(isEventOngoing);

  if (ongoingEventIndex !== -1) {
    // Si un événement est en cours, on le prend ainsi que le suivant (s'il existe)
    const ongoingEvent = allEvents.value[ongoingEventIndex];
    const nextEvent = allEvents.value[ongoingEventIndex + 1];
    sortedEvents.value = [
      { ...ongoingEvent, isFirst: true },
      ...(nextEvent ? [{ ...nextEvent, isFirst: false }] : [])
    ];
  } else {
    // Si aucun événement en cours, on affiche "aucun événement en cours" et le prochain événement
    const nextEvent = allEvents.value[0];
    sortedEvents.value = [
      {
        text: "Aucun cours",
        isFirst: true,
        backgroundColor: "#f0f0f0",
        colorFocus: "#d0d0d0",
        dayoff: false,
      },
      ...(nextEvent ? [{ ...nextEvent, isFirst: false }] : [])
    ];
  }
};

onMounted(async () => {
  const today = new Date();
  await getWeekUnivNumber(today);
  await getEvents();
});

const selectedEvent = ref(null)
const visible = ref(false)

const openDialog = ( event ) => {
  if (event.text === "Aucun cours") {
    return;
  }
  selectedEvent.value = event
  visible.value = true
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

  <ErrorView v-if="hasError" message="Une erreur est survenue lors de la récupération des événements de l'emploi du temps." />
  <div v-else class="flex flex-row justify-between gap-4">
    <div v-for="(event, index) in sortedEvents" :key="index" class="w-full">
      <EdtEvent :event="event" type="jour" @click="openDialog(event)" />
    </div>
  </div>
</template>

<style scoped>

</style>
