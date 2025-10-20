<script setup lang="ts">
import { heuresMinutesDate } from "@helpers/date";
import { computed } from "vue";
import { adjustColor, darkenColor } from "@helpers/colors";

interface Event {
  debut: string;
  fin: string;
  title: string;
  type: string;
  backgroundColor?: string;
  jour?: string;
}

const props = defineProps<{
  events: Event[];
}>();

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
  const eventsWithFormattedDates = props.events.map(event => ({
    ...event,
    debut: heuresMinutesDate(new Date(event.debut)),
    fin: heuresMinutesDate(new Date(event.fin)),
    jour: new Date(event.debut).toLocaleDateString("fr-FR", {
      weekday: "long",
      day: "numeric",
      month: "long",
      year: "numeric"
    })
  }));
  return groupBy(eventsWithFormattedDates, event => event.jour!);
});
</script>

<template>
  <div>
    <div class="flex flex-col gap-4">
      <div v-for="(events, day) in groupedEvents" :key="day">
        <h2 class="text-lg font-bold">{{ day }}</h2>
        <DataTable :value="events" class="w-full">
          <Column field="debut" header="Début" :sortable="true" />
          <Column field="fin" header="Fin" :sortable="true" />
          <Column field="title" header="Matière/Ressource/SAE" :sortable="true" />
          <Column field="type" header="Type de groupe" :sortable="true">
            <template #body="slotProps">
              <div class="rounded-md w-fit px-4 py-1 font-bold text-sm" :style="{ backgroundColor: slotProps.data.backgroundColor ? adjustColor(darkenColor(slotProps.data.backgroundColor, 60), 0, 0.2) : '' }">
                {{ slotProps.data.type }}
              </div>
            </template>
          </Column>
          <Column field="semestre.libelle" header="Semestre" :sortable="true" />
          <Column field="groupe.libelle" header="Groupe" :sortable="true" />

        </DataTable>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
