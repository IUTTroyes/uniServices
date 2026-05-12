<script setup>

import TicketCard from "@/assets/components/TicketCard.vue";
import { useUsersStore } from "@stores";
import {computed, ref} from "vue";
import {tickets} from '@/mocks/messages.js';
import { useRouter } from 'vue-router';

const router = useRouter();
const userStore = useUsersStore();
const date = new Date();

const goToTicket = (id) => {
  router.push({ name: 'TicketView', params: { id: id } });
};

const initiales = computed(
    () => `${userStore.user?.prenom?.charAt(0) || ""}${userStore.user?.nom?.charAt(0) || ""}`
);
</script>

<template>
<div class="card">
  <Panel
      header="Filtrer par:"
      :pt="{
        root: '!border !border-violet-400 overflow-hidden',
        header: '!bg-violet-100 !border-none',
        content: '!bg-violet-100'
    }"
  >
      <div class="flex justify-center w-full py-4">

      <div class="flex flex-row gap-30 items-end">

        <div class="flex flex-col">
          <label for="creation" class="mb-1 text-sm font-medium">Date de création</label>
          <Select id="creation" v-model="selectedCreated" :options="dates"  optionLabel="name" class="w-48" />
        </div>
        <div class="flex flex-col">
          <label for="category" class="mb-1 text-sm font-medium">Catégorie</label>
          <Select id="category" v-model="selectedCategory" :options="categories" optionLabel="name" class="w-48" />
        </div>
        <div class="flex flex-col">
          <label for="statut" class="mb-1 text-sm font-medium">Statut</label>
          <Select id="statut" v-model="selectedStatut" :options="statut" optionLabel="name" class="w-48" />
        </div>
        <div v-permission="isPersonnel" class="flex flex-col">
          <label for="assigne" class="mb-1 text-sm font-medium">Assignés</label>
          <Select id="assigne" v-model="selectedAssigne" :options="assigne" optionLabel="name" class="w-48" />
        </div>
      </div>
    </div>
  </Panel>
  <div class="font-semibold text-xl mt-6">Mes Tickets</div>
  <div class="p-6">
    <div v-for="ticket in tickets" :key="ticket.id">
      <TicketCard
          :ticket="ticket"
          @click="goToTicket(ticket.id)"
          class="cursor-pointer hover:shadow-md transition-shadow"
      />
    </div>
  </div>
</div>
</template>

<style scoped>

</style>