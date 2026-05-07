<script setup>
import { computed,ref } from "vue";
import { ArticleSkeleton } from "@components";
import { formatDateLong } from "@helpers/date.js";
import { useUsersStore } from "@stores";
import TicketCard from "@/assets/components/TicketCard.vue";

const userStore = useUsersStore();
const date = new Date();

const initiales = computed(
  () => `${userStore.user?.prenom?.charAt(0) || ""}${userStore.user?.nom?.charAt(0) || ""}`
);
const tickets = ref([
  {
    id: 1,
    subject: 'Problème de vidéoprojecteur - Salle B204',
    category: 'Matériel informatique',
    statut: 'Nouveau',
    desc: 'Bonjour, le vidéoprojecteur ne s\'allume plus. Le voyant clignote en rouge. J\'ai déjà essayé de débrancher et rebrancher, mais rien ne change.',
    attachment: 'photo_erreur.jpg'
  },
  {
    id: 2,
    subject: 'Demande de badges pour les nouveaux arrivants',
    category: 'Scolarité',
    statut: 'En cours',
    desc: 'Nous avons besoin de 3 nouveaux badges pour les intervenants qui arrivent lundi prochain. Merci de nous indiquer quand ils seront prêts.',
    attachment: null
  },
  {
    id: 3,
    subject: 'Fuite d\'eau signalée au 2ème étage',
    category: 'Maintenance',
    statut: 'Urgent',
    desc: 'Une fuite importante a été détectée dans les sanitaires du personnel au deuxième étage du bâtiment principal.',
    attachment: 'fuite.png'
  },
  {
    id: 4,
    subject: 'Accès Wi-Fi bloqué pour les tablettes',
    category: 'Réseau',
    statut: 'En attente',
    desc: 'Les tablettes de la classe mobile n\'arrivent plus à se connecter au réseau Wi-Fi de l\'IUT depuis la mise à jour de sécurité.',
    attachment: 'logs_connexion.txt'
  }
]);
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

    <div class="bg-white border border-gray-200 rounded-xl self-center p-5">
      <div class="flex items-center gap-8 px-6 py-2">
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-500 font-bold">Tickets Totaux :</span>
          <span class="text-2xl font-bold text-purple-600">1</span>
        </div>
        <div class="w-px h-6 bg-gray-200"></div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-500 font-bold">En cours :</span>
          <span class="text-2xl font-bold text-purple-600">1</span>
        </div>
        <div class="w-px h-6 bg-gray-200"></div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-500 font-bold">Traités :</span>
          <span class="text-2xl font-bold text-purple-600">1</span>
        </div>
      </div>
    </div>
  </div>
  <div class="flex justify-end px-10 mb-5">
    <Button class="w-100" label="Créer un ticket"  icon="pi pi-plus" />
  </div>
  <div class="card">
    <Message icon="pi pi-info-circle" class="mt-2 mb-10">Nous traitons actuellement un volume élevé de tickets. Merci de limiter vos ouvertures de tickets aux besoins essentiels afin de nous aider à réduire les délais de réponse.
    </Message>
    <div >
      <h2>Mes derniers tickets</h2>
    </div>
    <div class="p-6">
      <div v-for="ticket in tickets" :key="ticket.id">
        <TicketCard :ticket="ticket" />
      </div>
    </div>
  </div>
  </div>
</template>

<style scoped>

</style>
