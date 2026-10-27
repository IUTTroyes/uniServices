<script setup xmlns="http://www.w3.org/1999/html">
import { ref, onMounted } from "vue";
import { formatDateLong } from "@helpers/date.js";
import { useUsersStore } from "@stores";
import TicketMessageCard from "@/components/TicketMessageCard.vue";
import { useRouter } from 'vue-router';
import { getTicketsService } from '@requests';
import AccordeonMessages from "@/components/AccordeonMessages.vue";
import { HeaderComponent, Kpi, Card, EmptyState, ActionButtonVertical } from "@components";

const router = useRouter();
const userStore = useUsersStore();
const date = new Date();



const ticketsList = ref([]);
const ticketsNewMessageList= ref ([]);
const loading = ref(true);

const goToTicket = (id) => {
  router.push({ name: 'TicketView', params: { id: id } });
};

const goToNewTicket = () => {
  router.push({ name: 'NewTicketView' });
};

const getTickets = async () => {
  try {
    loading.value = true;
    const params= {
      auteur: userStore.user?.id,
      latest:6,
    }
    const paramsMessages= {
      hasRecentMessage:true,
    }
    const response = await getTicketsService(params);

    if (response && response['member']) {
      ticketsList.value = response['member'];
    } else if (Array.isArray(response)) {
      ticketsList.value = response;
    } else {
      ticketsList.value = [];
    }

    const responseNewMessage = await getTicketsService(paramsMessages);

    if (responseNewMessage && responseNewMessage['member']) {
      ticketsNewMessageList.value = responseNewMessage['member'];
    } else if (Array.isArray(responseNewMessage)) {
      ticketsNewMessageList.value = responseNewMessage;
    } else {
      ticketsNewMessageList.value = [];
    }


  } catch (error) {
    console.error('Impossible de charger les tickets:', error);
    ticketsList.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  getTickets();

});
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent titre="Assistance" :description="`Bonjour${userStore.user?.prenom ? ', ' + userStore.user.prenom : ''} · ${formatDateLong(date)}`" icon="pi pi-question-circle" color="orange" :show-back="false">
      <template #actions>
        <ActionButtonVertical icon="pi pi-plus" label="Créer un ticket" severity="primary" @click="goToNewTicket" />
      </template>
    </HeaderComponent>

    <Message severity="info" icon="pi pi-info-circle">
      Nous traitons actuellement un volume élevé de tickets. Merci de limiter vos ouvertures de tickets aux besoins essentiels afin de nous aider à réduire les délais de réponse.
    </Message>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <Kpi label="Tickets récents" :value="ticketsList.length" icon="pi pi-ticket" color="orange" :loading="loading" />
      <Kpi label="En attente" :value="ticketsList.filter(t => t.statut === 'En attente').length" icon="pi pi-clock" color="amber" :loading="loading" />
      <Kpi label="En cours" :value="ticketsList.filter(t => t.statut === 'En cours').length" icon="pi pi-spinner" color="blue" :loading="loading" />
      <Kpi label="Refusés" :value="ticketsList.filter(t => t.statut === 'Refusé').length" icon="pi pi-times-circle" color="red" :loading="loading" />
    </div>

    <AccordeonMessages v-if="ticketsNewMessageList.length" :tickets="ticketsNewMessageList" />

    <Card title="Derniers tickets postés" icon="pi pi-ticket" color="orange">
      <div v-if="loading" role="status" class="py-6 text-muted-color">Chargement des tickets…</div>
      <EmptyState v-else-if="!ticketsList.length" title="Aucun ticket récent" description="Créez un ticket pour contacter l’assistance." icon="pi pi-ticket" compact />
      <Carousel v-else :value="ticketsList" :numVisible="3" :numScroll="1" :responsiveOptions="[{ breakpoint: '1200px', numVisible: 2, numScroll: 1 }, { breakpoint: '640px', numVisible: 1, numScroll: 1 }]">
        <template #item="{ data: ticket }">
          <div class="p-2">
            <TicketMessageCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer" />
          </div>
        </template>
      </Carousel>
    </Card>
  </div>
</template>
