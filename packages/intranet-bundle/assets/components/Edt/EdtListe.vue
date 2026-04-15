<script setup lang="ts">
import {heuresMinutesDate} from "@helpers/date";
import {computed} from "vue";
import {adjustColor, darkenColor} from "@helpers/colors";
import {useUsersStore} from "@stores/user_stores/userStore";

interface Event {
  debut: string;
  fin: string;
  title: string;
  type: string;
  backgroundColor?: string;
  jour?: string;
  jourDate?: Date;
}

const props = defineProps<{
  events: Event[];
  type: string;
}>();

const userStore = useUsersStore();

const user = computed(() => userStore.user);

// Fonction pour regrouper les événements par jour
const groupBy = (array: Event[], key: (item: Event) => string) => {
  return array.reduce((result, item) => {
    const group = key(item);
    if (!result[group]) {
      result[group] = [];
    }
    result[group].push(item);
    return result;
  }, {} as Record<string, Event[]>);
};

const groupedEvents = computed<Record<string, Event[]>>(() => {
  const eventsWithFormattedDates = props.events.map(event => {
    const debutDate = new Date(event.debut);
    return {
      ...event,
      debut: heuresMinutesDate(debutDate),
      fin: heuresMinutesDate(new Date(event.fin)),
      jourDate: debutDate, // Store the original date for sorting
      jour: debutDate.toLocaleDateString("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric"
      })
    };
  });

  // Group events by day
  const grouped = groupBy(eventsWithFormattedDates, event => event.jour!);

  // Convert to array, sort by date, and convert back to object
  return Object.entries(grouped).map(([day, events]) => {
        // Use the first event's date for sorting (all events in a group have the same day)
        return {day, events, date: events[0].jourDate};
      })
      .sort((a, b) => a.date.getTime() - b.date.getTime())
      .reduce((result, {day, events}) => {
        result[day] = events;
        return result;
      }, {} as Record<string, Event[]>);
});

function getBadgeSeverity(type) {
  const badgeMapping = {
    ressource: 'primary',
    sae: 'warn',
    matiere: 'success',
  };

  return badgeMapping[type] || 'info'; // Valeur par défaut si le type est inconnu
}

const rowClass = (data) => {
  if (props.type === 'personnel') {
    return {};
  }
  if (props.type === 'departement') {
    return [{ '!bg-primary-500 !bg-opacity-20': data.personnel.id === user.value.id }];
  }
};

const todayString = new Date().toLocaleDateString("fr-FR", {
  weekday: "long",
  day: "numeric",
  month: "long",
  year: "numeric"
});
</script>

<template>
  <div>
    <div class="flex flex-col gap-4">
      <div v-for="(events, day) in groupedEvents" :key="day" :class="{ 'border-2 border-primary rounded-lg': day === todayString }">
        <div class="w-full flex justify-center bg-neutral-300 bg-opacity-20 p-4 text-lg font-bold">{{ day }}</div>
        <DataTable :value="events" class="w-full" striped-rows :rowClass="rowClass">
          <Column field="debut" header="Début" :sortable="true" />
          <Column field="fin" header="Fin" :sortable="true" />
          <Column field="enseignement.type" header="Type d'enseignement" :sortable="true">
            <template #body="slotProps">
              <Badge :severity="getBadgeSeverity(slotProps.data.enseignement.type)" class="uppercase">
                {{ slotProps.data.enseignement.type }}
              </Badge>
            </template>
          </Column>
          <Column field="title" header="Enseignement" :sortable="true" />
          <Column field="type" header="Type de groupe" :sortable="true">
            <template #body="slotProps">
              <Badge class="rounded-md w-fit px-4 py-1 font-bold text-sm" :style="{ backgroundColor: slotProps.data.backgroundColor ? adjustColor(darkenColor(slotProps.data.backgroundColor, 60), 0, 0.2) : '' }">
                {{ slotProps.data.type }}
              </Badge>
            </template>
          </Column>
          <Column field="semestre.libelle" header="Semestre" :sortable="true" />
          <Column field="groupe.libelle" header="Groupe" :sortable="true" />
          <Column field="salle" header="Salle" :sortable="true" />
          <Column v-if="props.type !== 'personnel'" field="personnel.display" header="Enseignant.e" :sortable="true" />
          <Column v-if="props.type === 'personnel'" header="Actions" :sortable="false">
            <template #body="slotProps">
              <div class="flex gap-2">
                <Button icon="pi pi-list" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Appel" size="small" v-tooltip.top="'Faire l\'appel'"></Button>
                <Button icon="pi pi-check-circle" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Tous présents" size="small" v-tooltip.top="'Marquer tout le monde présents'"></Button>
                <Button icon="pi pi-book" class="!bg-white !bg-opacity-50 !text-black hover:!bg-opacity-100" rounded aria-label="Plan de cours" size="small" v-tooltip.top="'Voir le plan de cours'"></Button>
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
