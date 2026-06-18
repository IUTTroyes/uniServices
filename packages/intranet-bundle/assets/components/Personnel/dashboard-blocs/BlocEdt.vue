<script setup>
const emploiDuTemps = [
{heure: "08:00", type: "TD", cours: "Informatique S2", salle: "Salle B204", color: "blue"},
{heure: "10:00", type: "Cours", cours: "Réseaux", salle: "Amphi A", color: "green"},
{heure: "14:00", type: "TP", cours: "Linux", salle: "Salle Info 3", color: "purple"}
];
</script>

<template>
  <div class="mb-0! flex-1 card">
    <div class="mb-4 flex items-center gap-2 border-t border-surface-200 pt-4">
      <i class="pi pi-calendar text-violet-600"></i>
    </div>

    <div class="flex flex-col gap-3">
      <div
        v-for="item in emploiDuTemps"
        :key="`${item.heure}-${item.cours}`"
        class="flex items-center gap-3"
      >
        <span
          :class="{
            'rounded px-2 py-1 text-xs font-semibold': true,
            'bg-red-100 text-red-700': item.type === 'Cours',
            'bg-blue-100 text-blue-700': item.type === 'TD',
            'bg-green-100 text-green-700': item.type === 'TP',
          }"
        >
          {{ item.heure }}
        </span>
        <span class="flex-1">{{ item.cours }}</span>
        <i class="pi pi-map-marker text-surface-400"></i>
        <span class="text-surface-500">{{ item.salle }}</span>
      </div>
    </div>

    <button
      type="button"
      class="mt-4 w-full"
    >
      <i class="pi pi-calendar"></i>
      Voir l'emploi du temps
    </button>
  </div>
</template>

<style scoped></style>
