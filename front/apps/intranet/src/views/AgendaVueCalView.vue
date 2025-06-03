<script setup>
import { VueCal } from 'vue-cal'
import 'vue-cal/style'

import { ref } from 'vue'

// Référence vers le composant vue-cal
const vuecalRef = ref(null)

const events = ref([
  {
    id: 1,
    title: 'Hello1',
    start: new Date(2025, 5, 5, 10, 0), // Juin (index 5)
    end: new Date(2025, 5, 5, 11, 0),   // Juin (index 5)
    backgroundColor: '#ffcccc',
  },
  {
    id: 2,
    title: 'Hello2',
    start: new Date(2025, 5, 5, 12, 0),
    end: new Date(2025, 5, 5, 13, 30),
    backgroundColor: '#ccffcc',
  },
  {
    id: 2,
    title: 'Hello3',
    start: new Date(2025, 5, 5, 12, 0),
    end: new Date(2025, 5, 5, 13, 30),
    backgroundColor: '#ccffcc',
  },
  {
    id: 3,
    title: 'Hello4',
    start: new Date(2025, 5, 6, 14, 0),
    end: new Date(2025, 5, 6, 15, 30),
    backgroundColor: '#ccccff',
  }
]);

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
      stack-events:true
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
    <template #weekday-heading="{ label, id, date }">
      <div :class="id">{{ label }}</div>
      <strong>{{ new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }) }}</strong>
    </template>


    <!-- Vue semaine personnalisée -->
<!--    <template #week="{ week, view }">-->
<!--      <div>-->
<!--        &lt;!&ndash; En-tête des jours &ndash;&gt;-->
<!--        <div>-->
<!--          <div v-for="day in week.days"-->
<!--               :key="day.date">-->
<!--            <div>{{ day.dayOfWeek }}</div>-->
<!--            <div>{{ day.date }}</div>-->
<!--          </div>-->
<!--        </div>-->

<!--        &lt;!&ndash; Grille horaire &ndash;&gt;-->
<!--        <div class="flex flex-1">-->
<!--          &lt;!&ndash; Colonne des heures &ndash;&gt;-->
<!--          <div class="w-20 border-r">-->
<!--            <div v-for="hour in view.timeCells"-->
<!--                 :key="hour.value"-->
<!--            >-->
<!--              {{ hour.label }}-->
<!--            </div>-->
<!--          </div>-->

<!--          &lt;!&ndash; Colonnes des jours &ndash;&gt;-->
<!--          <div>-->
<!--            <div v-for="day in week.days"-->
<!--                 :key="day.date"-->
<!--            >-->
<!--              <div v-for="hour in view.timeCells"-->
<!--                   :key="hour.value"-->
<!--              >-->
<!--                &lt;!&ndash; Événements &ndash;&gt;-->
<!--                <template v-for="event in day.events" :key="event.id">-->
<!--                  <div v-if="event.start.hour === hour.value"-->
<!--                       :style="{-->
<!--                         top: `${event.start.minute}%`,-->
<!--                         height: `${event.durationInMinutes}%`,-->
<!--                       }"-->
<!--                  >-->
<!--                    <div>{{ event.title }}</div>-->
<!--                    <div>-->
<!--                      {{ event.start.formatTime }} - {{ event.end.formatTime }}-->
<!--                    </div>-->
<!--                  </div>-->
<!--                </template>-->
<!--              </div>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--      </div>-->
<!--    </template>-->

    <!-- Vue jour personnalisée -->
<!--    <template #day="{ day, view }">-->
<!--      <div class="flex flex-col h-full">-->
<!--        &lt;!&ndash; En-tête du jour &ndash;&gt;-->
<!--        <div class="p-4 text-center border-b">-->
<!--          <div class="text-sm text-gray-600">{{ day.dayOfWeek }}</div>-->
<!--          <div class="font-semibold">{{ day.date }}</div>-->
<!--        </div>-->

<!--        &lt;!&ndash; Grille horaire &ndash;&gt;-->
<!--        <div class="flex flex-1">-->
<!--          &lt;!&ndash; Colonne des heures &ndash;&gt;-->
<!--          <div class="w-20 border-r">-->
<!--            <div v-for="hour in view.timeCells"-->
<!--                 :key="hour.value"-->
<!--                 class="h-[60px] border-b flex items-center justify-center text-sm text-gray-600"-->
<!--            >-->
<!--              {{ hour.label }}-->
<!--            </div>-->
<!--          </div>-->

<!--          &lt;!&ndash; Colonne du jour &ndash;&gt;-->
<!--          <div class="flex-1">-->
<!--            <div v-for="hour in view.timeCells"-->
<!--                 :key="hour.value"-->
<!--                 class="h-[60px] border-b relative"-->
<!--            >-->
<!--              &lt;!&ndash; Événements &ndash;&gt;-->
<!--              <template v-for="event in day.events" :key="event.id">-->
<!--                <div v-if="event.start.hour === hour.value"-->
<!--                     class="absolute w-[95%] left-[2.5%] rounded-lg shadow-sm bg-blue-100 p-2"-->
<!--                     :style="{-->
<!--                       top: `${event.start.minute}%`,-->
<!--                       height: `${event.durationInMinutes}%`,-->
<!--                     }"-->
<!--                >-->
<!--                  <div class="text-sm font-semibold">{{ event.title }}</div>-->
<!--                  <div class="text-xs text-gray-600">-->
<!--                    {{ event.start.formatTime }} - {{ event.end.formatTime }}-->
<!--                  </div>-->
<!--                </div>-->
<!--              </template>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--      </div>-->
<!--    </template>-->
  </vue-cal>
</div>
</template>

<style scoped>
:deep(.vuecal__event) {
  @apply m-2 p-4 rounded-md;
}
:deep(.vuecal__weekday) {
  @apply bg-gray-300 bg-opacity-20 m-2 py-4 rounded-md flex flex-col items-center uppercase;
}
</style>
