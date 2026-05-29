<script setup xmlns="http://www.w3.org/1999/html">
import { computed, ref, onMounted } from "vue";
import { formatDateLong } from "@helpers/date.js";
import { useUsersStore } from "@stores";
import TicketCard from "@/components/TicketCard.vue";
import TicketMessageCard from "@/components/TicketMessageCard.vue";
import AccordeonMessagesVue from "@/components/AccordeonMessagesVue.vue";
import { PermissionGuard } from "@components";
import { useRouter } from 'vue-router';
import { getTicketsService } from '@requests';

const router = useRouter();
const userStore = useUsersStore();
const date = new Date();
const checked = ref(false);
const first = ref(0);
const rows = ref(5);
const ticketsList = ref([]);
const loading = ref(true);

const goToTicket = (id) => {
  router.push({ name: 'TicketView', params: { id: id } });
};

const goToNewTicket = () => {
  router.push({ name: 'NewTicketView' });
};

const onPageChange = (event) => {
  first.value = event.first;
};

const paginatedTickets = computed(() => {
  return ticketsList.value.slice(first.value, first.value + rows.value);
});

const initiales = computed(
    () => `${userStore.user?.prenom?.charAt(0) || ""}${userStore.user?.nom?.charAt(0) || ""}`
);

const fetchTickets = async () => {
  try {
    loading.value = true;
    const response = await getTicketsService();

    if (response && response['member']) {
      ticketsList.value = response['member'];
    } else if (Array.isArray(response)) {
      ticketsList.value = response;
    } else {
      ticketsList.value = [];
    }
  } catch (error) {
    console.error('Impossible de charger les tickets:', error);
    ticketsList.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchTickets();
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

      <div class="border border-gray-200 rounded-xl self-center p-5">
        <div class="flex items-center gap-8 px-6 py-2">
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">Tickets Totaux :</span>
            <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">{{ ticketsList.length }}</span>
          </div>
          <div class="w-px h-6 bg-gray-200"></div>
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">En cours :</span>
            <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
              {{ ticketsList.filter(t => t.statut === 'En cours').length }}
            </span>
          </div>
          <div class="w-px h-6 bg-gray-200"></div>
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">Traités :</span>
            <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
              {{ ticketsList.filter(t => t.statut === 'Traité').length }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-end px-10 mb-5">
      <Button class="w-100" label="Créer un ticket" @click="goToNewTicket()" icon="pi pi-plus" />
    </div>

<div class="flex mb-8 justify-around">
    <div class="border border-gray-200 rounded-xl self-center p-5">
      <div class="flex items-center gap-8 px-6 py-2">
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">En cours :</span>
          <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
              {{ ticketsList.filter(t => t.statut === 'En cours').length }}
            </span>
        </div>
        <div class="w-px h-6 bg-gray-200"></div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">Traités :</span>
          <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
              {{ ticketsList.filter(t => t.statut === 'Traité').length }}
            </span>
        </div>
      </div>
    </div>

    <div class="border border-gray-200 rounded-xl self-center p-5">
      <div class="flex items-center gap-8 px-6 py-2">
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">En cours :</span>
          <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
              {{ ticketsList.filter(t => t.statut === 'En cours').length }}
            </span>
        </div>
        <div class="w-px h-6 bg-gray-200"></div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600 dark:text-gray-400 font-bold">Traités :</span>
          <span class="text-2xl font-bold text-purple-600 dark:text-violet-400">
              {{ ticketsList.filter(t => t.statut === 'Traité').length }}
            </span>
        </div>
      </div>
    </div>
</div>

    <div>
      <Message severity="info" icon="pi pi-info-circle" class="mt-2 mb-10">
        <PermissionGuard permission="isScolarite">
          <div class="flex items-center gap-2 mb-4 border-b border-blue-100 pb-2">
            <Checkbox id="checkbox" v-model="checked" binary />
            <label for="checkbox" class="font-bold text-sm">Afficher ce message pour tous les utilisateurs</label>
          </div>
        </PermissionGuard>
        Nous traitons actuellement un volume élevé de tickets. Merci de limiter vos ouvertures de tickets aux besoins essentiels afin de nous aider à réduire les délais de réponse.
      </Message>
    </div>

    <div class="flex mb-8">

      <div class="card text-center">
        <div class="font-bold text-violet-600 text-2xl">{{ticketsList.length}}</div>
        <p class="text-2xl">Tickets non  assignés</p>
      </div>
    </div>

    <div>
      <AccordeonMessagesVue v-if="ticketsList" :tickets="ticketsList" />
    </div>


    <div class="card">
      <div class="font-semibold text-xl">

        <div class="font-semibold mb-6 text-xl">Nouveaux tickets de votre service</div>

        <Carousel :value="ticketsList" :numVisible="3" :numScroll="1" :responsiveOptions="responsiveOptions">

          <template #item="{ data: ticket }">
            <div class="border border-surface-200 dark:border-surface-700 rounded m-2 p-4">
              <div class="mb-4">
                <TicketMessageCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer"/>
              </div>
            </div>
          </template>

        </Carousel>

      </div>
     </div>

    <div class="card">
      <Tabs value="0">
        <TabList class="mb-10">
          <Tab value="0">Mes derniers Tickets</Tab>
          <Tab value="1">Tickets Postés</Tab>
          <PermissionGuard permission="isPersonnelService">
            <Tab value="2">Tickets Reçus</Tab>
          </PermissionGuard>
        </TabList>

        <TabPanels>
          <TabPanel value="0">
            <div>
              <Toolbar style="border:none">
                <template #start>
                  <div class="font-semibold text-xl">Mes derniers Tickets</div>
                </template>
                <template #end>
                  <IconField>
                    <InputIcon>
                      <i class="pi pi-search" />
                    </InputIcon>
                    <InputText placeholder="Search" />
                  </IconField>
                </template>
              </Toolbar>
            </div>

            <div v-if="loading" class="text-center p-10 text-xl">
              Chargement des tickets...
            </div>
            <div v-else-if="ticketsList.length === 0" class="text-center p-10 text-xl text-gray-500">
              Aucun ticket trouvé.
            </div>
            <div v-else class="p-6">
              <div v-for="ticket in paginatedTickets" :key="ticket.id">
                <TicketCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer hover:shadow-md transition-shadow"/>
              </div>
              <Paginator :first="first" :rows="rows" :totalRecords="ticketsList.length" @page="onPageChange" template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" class="mt-4 bg-transparent border-none" />
            </div>
          </TabPanel>

          <TabPanel value="1">
            <div>
              <Toolbar style="border:none">
                <template #start>
                  <div class="font-semibold text-xl">Tickets Postés</div>
                </template>
                <template #end>
                  <IconField>
                    <InputIcon>
                      <i class="pi pi-search" />
                    </InputIcon>
                    <InputText placeholder="Search" />
                  </IconField>
                </template>
              </Toolbar>
            </div>

            <div v-if="loading" class="text-center p-10 text-xl">
              Chargement des tickets...
            </div>
            <div v-else-if="ticketsList.length === 0" class="text-center p-10 text-xl text-gray-500">
              Aucun ticket trouvé.
            </div>
            <div v-else class="p-6">
              <div v-for="ticket in ticketsList" :key="ticket.id">
                <TicketCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer hover:shadow-md transition-shadow"/>
              </div>
            </div>
          </TabPanel>

          <TabPanel value="2">
            <div>
              <Toolbar style="border:none">
                <template #start>
                  <div class="font-semibold text-xl">Tickets Reçus</div>
                </template>
                <template #end>
                  <IconField>
                    <InputIcon>
                      <i class="pi pi-search" />
                    </InputIcon>
                    <InputText placeholder="Search" />
                  </IconField>
                </template>
              </Toolbar>
            </div>

            <div v-if="loading" class="text-center p-10 text-xl">
              Chargement des tickets...
            </div>
            <div v-else-if="ticketsList.length === 0" class="text-center p-10 text-xl text-gray-500">
              Aucun ticket trouvé.
            </div>
            <div v-else class="p-6">
              <div v-for="ticket in ticketsList" :key="ticket.id">
                <TicketCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer hover:shadow-md transition-shadow"/>
              </div>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>
  </div>
</template>