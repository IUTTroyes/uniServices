<script setup>
import { computed,ref } from "vue";
import { formatDateLong } from "@helpers/date.js";
import { useUsersStore } from "@stores";
import TicketCard from "@/components/TicketCard.vue";
import {PermissionGuard} from "@components";
import { useRouter } from 'vue-router';
import {tickets} from '@/mocks/messages.js';

const router = useRouter();
const userStore = useUsersStore();
const date = new Date();
const checked = ref(false);

const goToTicket = (id) => {
  router.push({ name: 'TicketView', params: { id: id } });
};
const goToNewTicket=()=>{
  router.push({ name: 'NewTicketView' });
}

const shouldShowMessage = computed(() => {
  return checked.value || isScolarite.value;
});

const initiales = computed(
  () => `${userStore.user?.prenom?.charAt(0) || ""}${userStore.user?.nom?.charAt(0) || ""}`
);

</script>

<template>
<div>
  <div class="m-5 mb-10 flex items-center justify-between gap-8">

    <div v-if="!userStore.isLoading" class="flex items-center">
      <div class="w-20 h-20 bg-violet-400 rounded-full flex items-center justify-center shrink-0">
        <template v-if="userStore.userPhoto">
          <img :src="userStore.userPhoto" alt="photo de profil" class="rounded-full" />
        </template>
        <template v-else>
          <span class="text-gray-700 text-xl">{{ initiales }}</span>
        </template>
      </div>
      <div class="ml-4">
        <h2 class="text-2xl! mb-0! font-bold flex items-center gap-2">
          <span class="font-light">Bonjour,</span> {{ userStore.user.prenom }}
        </h2>
        <small class="text-gray-500">{{ formatDateLong(date) }}</small>
      </div>
    </div>

    <div class=" border border-gray-200 rounded-xl self-center p-5">
      <div class="flex items-center gap-8 px-6 py-2">
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">Tickets Totaux :</span>
          <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">1</span>
        </div>
        <div class="w-px h-6 bg-gray-200"></div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">En cours :</span>
          <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">1</span>
        </div>
        <div class="w-px h-6 bg-gray-200"></div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">Traités :</span>
          <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">1</span>
        </div>
      </div>
    </div>
  </div>
  <div class="flex justify-end px-10 mb-5">
    <Button class="w-100" label="Créer un ticket" @click="goToNewTicket()" icon="pi pi-plus" />
  </div>

  <div class="card">

    <Message severity="info" icon="pi pi-info-circle" class="mt-2 mb-10">
      <PermissionGuard permission="isScolarite">
      <div class="flex items-center gap-2 mb-4 border-b border-blue-100 pb-2">
        <Checkbox id="checkbox" v-model="checked" binary />
        <label for="checkbox" class="font-bold text-sm">Afficher ce message pour tous les utilisateurs</label>
      </div>
      </PermissionGuard>
        <span v-if="!checked && isScolarite" class="block mb-1 text-xs font-bold text-orange-500 uppercase">Aperçu (Masqué pour les autres) :</span>
        Nous traitons actuellement un volume élevé de tickets. Merci de limiter vos ouvertures de tickets aux besoins essentiels afin de nous aider à réduire les délais de réponse.
    </Message>
    <div >
      <div class="font-semibold text-xl">Mes derniers Tickets</div>
    </div>
    <div class="p-6">
      <div v-for="ticket in tickets" :key="ticket.id">
        <TicketCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer hover:shadow-md transition-shadow"/>
      </div>
    </div>
  </div>
  </div>
</template>

<style scoped>

</style>
