<script setup>
import { VueCal } from 'vue-cal'
import 'vue-cal/style'

import { ref } from 'vue'

// Référence vers le composant vue-cal
const vuecalRef = ref(null)

// todo: un switch entre cours et agenda + ajouter un event
</script>

<template>
<div class="card">
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
          <div v-html="view.title" class="font-bold text-xl flex flex-col items-center"></div>
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
              {{ viewId }}
            </Button>
          </div>

          <Button
              icon="pi pi-calendar"
              @click="view.goToToday()"
              class="p-button-text"
              v-tooltip.bottom="'Aller à aujourd\'hui'"
          />


        </div>
      </div>
    </template>

    <!-- Vue semaine personnalisée -->
    <template #week="{ week, view }">
      <div class="flex flex-col h-full">
        <!-- En-tête des jours -->
        <div class="grid grid-cols-5 border-b">
          <div v-for="day in week.days"
               :key="day.date"
               class="p-4 text-center border-r last:border-r-0"
          >
            <div class="text-sm text-gray-600">{{ day.dayOfWeek }}</div>
            <div class="font-semibold">{{ day.date }}</div>
          </div>
        </div>

        <!-- Grille horaire -->
        <div class="flex flex-1">
          <!-- Colonne des heures -->
          <div class="w-20 border-r">
            <div v-for="hour in view.timeCells"
                 :key="hour.value"
                 class="h-[60px] border-b flex items-center justify-center text-sm text-gray-600"
            >
              {{ hour.label }}
            </div>
          </div>

          <!-- Colonnes des jours -->
          <div class="grid grid-cols-5 flex-1">
            <div v-for="day in week.days"
                 :key="day.date"
                 class="border-r last:border-r-0"
            >
              <div v-for="hour in view.timeCells"
                   :key="hour.value"
                   class="h-[60px] border-b relative"
              >
                <!-- Événements -->
                <template v-for="event in day.events" :key="event.id">
                  <div v-if="event.start.hour === hour.value"
                       class="absolute w-[95%] left-[2.5%] rounded-lg shadow-sm bg-blue-100 p-2"
                       :style="{
                         top: `${event.start.minute}%`,
                         height: `${event.durationInMinutes}%`,
                       }"
                  >
                    <div class="text-sm font-semibold">{{ event.title }}</div>
                    <div class="text-xs text-gray-600">
                      {{ event.start.formatTime }} - {{ event.end.formatTime }}
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Vue jour personnalisée -->
    <template #day="{ day, view }">
      <div class="flex flex-col h-full">
        <!-- En-tête du jour -->
        <div class="p-4 text-center border-b">
          <div class="text-sm text-gray-600">{{ day.dayOfWeek }}</div>
          <div class="font-semibold">{{ day.date }}</div>
        </div>

        <!-- Grille horaire -->
        <div class="flex flex-1">
          <!-- Colonne des heures -->
          <div class="w-20 border-r">
            <div v-for="hour in view.timeCells"
                 :key="hour.value"
                 class="h-[60px] border-b flex items-center justify-center text-sm text-gray-600"
            >
              {{ hour.label }}
            </div>
          </div>

          <!-- Colonne du jour -->
          <div class="flex-1">
            <div v-for="hour in view.timeCells"
                 :key="hour.value"
                 class="h-[60px] border-b relative"
            >
              <!-- Événements -->
              <template v-for="event in day.events" :key="event.id">
                <div v-if="event.start.hour === hour.value"
                     class="absolute w-[95%] left-[2.5%] rounded-lg shadow-sm bg-blue-100 p-2"
                     :style="{
                       top: `${event.start.minute}%`,
                       height: `${event.durationInMinutes}%`,
                     }"
                >
                  <div class="text-sm font-semibold">{{ event.title }}</div>
                  <div class="text-xs text-gray-600">
                    {{ event.start.formatTime }} - {{ event.end.formatTime }}
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>
    </template>
  </vue-cal>
</div>
</template>

<style scoped>
/* Pour supprimer les styles par défaut de vue-cal si nécessaire */
:deep(.vuecal__bg-cell) {
  @apply bg-red-500;
}

:deep(.vuecal__cell) {
  @apply bg-transparent;
}

/* Ajoutez d'autres styles personnalisés si nécessaire */
</style>
