<script setup xmlns="http://www.w3.org/1999/html">
import { computed, ref, onMounted } from "vue";
import { formatDateLong } from "@helpers/date.js";
import { useUsersStore } from "@stores";
import TicketMessageCard from "@/components/TicketMessageCard.vue";
import { useRouter } from 'vue-router';
import { getTicketsService, getServicesService} from '@requests';
import {showMessage} from '@/utils';
import { PermissionGuard } from "@components";
import AccordeonMessages from "@/components/AccordeonMessages.vue";
import StatsDashboards from "@/components/StatsDashboards.vue";

const router = useRouter();
const userStore = useUsersStore();
const userServices=ref([]);
const date = new Date();
const first = ref(0);
const rows = ref(5);
const ticketsList = ref([]);
const postedTicketsList = ref([]);
const receivedTicketsList = ref([]);
const ticketsNewMessageList= ref ([]);
const loading = ref(true);

const goToTicket = (id) => {
  router.push({ name: 'TicketView', params: { id: id } });
};

const goToNewTicket = () => {
  router.push({ name: 'NewTicketView'});
};

const goToAllTickets = () => {
  router.push({ name: 'MyTicketView' });
};

const paginatedTickets = computed(() => {
  return ticketsList.value.slice(first.value, first.value + rows.value);
});

const initiales = computed(
    () => `${userStore.user?.prenom?.charAt(0) || ""}${userStore.user?.nom?.charAt(0) || ""}`
);

/*const getTickets = async () => {
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

    const postedParams = { auteur: userStore.user?.id };
    postedTicketsList.value = await getTicketsService(postedParams);

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
};*/
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

    const paramsUser = {
      personnel: userStore.user.id,
    }
    userServices.value = await getServicesService(paramsUser,'/mini')

    receivedTicketsList.value = []

    for (const service of userServices.value) {
      const receivedParams = {
        service: service.id
      }
      const tickets = await getTicketsService(receivedParams)
      receivedTicketsList.value.push(...tickets)
    }

    const postedParams = { auteur: userStore.user?.id };
    postedTicketsList.value = await getTicketsService(postedParams);

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

      <StatsDashboards :tickets="ticketsList"/>

    </div>


    <div>
      <Message v-if="showMessage" severity="info" icon="pi pi-info-circle" class="mt-2 mb-10">
        Nous traitons actuellement un volume élevé de tickets. Merci de limiter vos ouvertures de tickets aux besoins essentiels afin de nous aider à réduire les délais de réponse.
      </Message>
    </div>

    <PermissionGuard permission="isScolarite" :showFallback="true">
      <div class="flex mb-8 justify-around">


        <div class="card   rounded-xl self-center ">
          <div class="text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500 font-extrabold"> Statistiques personnelles</div>
          <div class="flex items-center gap-8  ">
            <div class="flex items-center gap-3">
              <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">En cours :</span>
              <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
              {{ receivedTicketsList.filter(t => t.statut === 'En cours').length }}
            </span>
            </div>
            <div class="w-px h-6 bg-gray-200"></div>
            <div class="flex items-center gap-3">
              <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">Traités :</span>
              <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
              {{ receivedTicketsList.filter(t => t.statut === 'Clôturé').length }}
            </span>
            </div>
          </div>
        </div>

        <div class="card border border-gray-200 rounded-xl self-center">
          <div class="text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500 font-extrabold"> Statistiques du service </div>
          <div class=" flex items-center gap-8">
            <div class="flex items-center gap-3">
              <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">En cours :</span>
              <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
              {{ ticketsList.filter(t => t.statut === 'En cours').length }}
            </span>
            </div>
            <div class="w-px h-6 bg-gray-200"></div>
            <div class=" flex items-center gap-3">
              <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">Traités :</span>
              <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
              {{ ticketsList.filter(t => t.statut === 'Clôturé').length }}
            </span>
            </div>
          </div>
        </div>

        <div class="card  text-center">
          <span class=" text-gray-600 dark:text-gray-400 font-bold">Non  assignés : </span>
          <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">{{ticketsList.length}}</span>
        </div>
      </div>

      <template #fallback>
        <div class="flex justify-around mb-8">
          <div class="card text-center">
            <span class="text-violet-600 font-bold text-2xl">{{ticketsList.filter(t => t.statut === 'En attente').length}}</span>
            <p class="text-1xl">Tickets en attente</p>
          </div>
          <div class="card text-center">
            <span class="text-violet-600 font-bold text-2xl">{{ticketsList.filter(t => t.statut === 'En cours').length}}</span>
            <p class="text-1xl">Tickets en cours</p>
          </div>
          <div class="card text-center">
            <span class="text-violet-600 font-bold text-2xl">{{ticketsList.filter(t => t.statut === 'Refusé').length}}</span>
            <p class="text-1xl">Tickets refusés</p>
          </div>
        </div>
      </template>
    </PermissionGuard>

    <div class="flex h-15 w-full gap-4 mb-4">
      <Button class="w-1/2 flex-1" severity="secondary" label="Voir tous mes tickets" @click="goToAllTickets" icon="pi pi-arrow-down-left" />

      <Button class="w-1/2 flex-1" label="Créer un ticket" @click="goToNewTicket" icon="pi pi-plus" />
    </div>

    <div>
      <AccordeonMessages v-if="ticketsNewMessageList" :tickets="ticketsNewMessageList" />
    </div>


    <PermissionGuard permission="isScolarite" :showFallback="true">
      <div class="card">
        <div class="font-semibold text-xl">

          <div class="font-semibold mb-6 text-xl">Nouveaux tickets de votre service</div>

          <Carousel :value="receivedTicketsList" :numVisible="3" :numScroll="1" :responsiveOptions="responsiveOptions">

            <template #item="{ data: ticket }">
              <div class="rounded m-2 p-4">
                <div class="mb-4">
                  <TicketMessageCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer"/>
                </div>
              </div>
            </template>

          </Carousel>

        </div>
      </div>

      <template #fallback>
        <div class="card">
          <div class="font-semibold text-xl">

            <div class="font-semibold mb-6 text-xl">Derniers tickets postés</div>

            <Carousel :value="postedTicketsList" :numVisible="3" :numScroll="1">

              <template #item="{ data: ticket }">
                <div class="rounded m-2 p-4">
                  <div class="mb-4">
                    <TicketMessageCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer"/>
                  </div>
                </div>
              </template>

            </Carousel>
          </div>
        </div>
      </template>
    </PermissionGuard>


    <div class="flex flex-col items-end gap-4 w-full">

    </div>
  </div>
</template>