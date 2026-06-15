<script setup>

import TicketCard from "@/components/TicketCard.vue";
import { useUsersStore } from "@stores";
import {computed, ref,onMounted} from "vue";
import { useRouter } from 'vue-router';
import {PermissionGuard, ValidatedInput} from "@components";
import {TabGroup, TabPanels} from "@headlessui/vue";
import DatePicker from 'primevue/datepicker';
import CascadeSelect from 'primevue/cascadeselect';
import {getMessagesService,getServicesService, getTicketsService, updateTicketStatutService} from "@requests";


const router = useRouter();
const userStore = useUsersStore();
const date = new Date();
const services=ref([]);
const selectedService=ref(null);
const selectedCategorie=ref(null);
const ticketsList=ref([]);
const postedTicketsList=ref([]);
const loading = ref(true);

const goToTicket = (id) => {
  router.push({ name: 'TicketView', params: { id: id } });
};

const initiales = computed(
    () => `${userStore.user?.prenom?.charAt(0) || ""}${userStore.user?.nom?.charAt(0) || ""}`
);

const rootCategories = computed(() => {
  if (!selectedService.value?.helpdeskCategories) return [];
  // On ne garde que les catégories qui n'ont pas de parent
  return selectedService.value.helpdeskCategories.filter(cat => !cat.parent);
});

const paginatedTickets = computed(() => {
  return ticketsList.value.slice(first.value, first.value + rows.value);
});

const getMessages = async () =>{

}

const getServices= async()=>{
  try{
    services.value=await getServicesService({},'/form_ticket')
  }
  catch(error){
    console.error('Erreur dans getServices',error);
  }
  finally {
    console.log(services.value)
  }
}
onMounted(async()=>{
  await getServices();
  await fetchTickets();
})

const fetchTickets = async () => {
  try {
    loading.value = true;
    const postedTickets= {
      auteur:userStore.user?.id,
    }
    const response = await getTicketsService();

    if (response && response['member']) {
      ticketsList.value = response['member'];
    } else if (Array.isArray(response)) {
      ticketsList.value = response;
    } else {
      ticketsList.value = [];
    }

    const responsePostedTickets = await getTicketsService(postedTickets);

    if (responsePostedTickets && responsePostedTickets['member']) {
      postedTicketsList.value = responsePostedTickets['member'];
    } else if (Array.isArray(responsePostedTickets)) {
      postedTicketsList.value = responsePostedTickets;
    } else {
      postedTicketsList.value = [];
    }
  } catch (error) {
    console.error('Impossible de charger les tickets:', error);
    ticketsList.value = [];
  } finally {
    loading.value = false;
  }
};


</script>

<template>
<div class="card">
  <Panel
      header="Filtrer par:"
      :pt="{
        root: '!border !border-violet-400 overflow-hidden',
        header: '!bg-violet-100 dark:!bg-violet-500  !border-none',
        content: 'bg-violet-100 dark:bg-violet-500'
    }"
  >
      <div class="flex justify-center w-full py-4">

        <div class="flex flex-row gap-10 items-start">
        <div class="flex flex-col">
          <label for="creation" class="mb-1 text-sm font-medium">Date de création</label>
          <DatePicker v-model="buttondisplay" showIcon fluid :showOnFocus="false" />
        </div>

        <div class="flex flex-col">
          <ValidatedInput
              v-model="selectedService"
              :options="(services.map(service=>({label:service.libelle,value:service})))"
              name="services"
              type="select"
              label="Services"
              placeholder="Sélectionnez un service"
              :rules="[]"
              class=""
              :show-clear="true"
          ></ValidatedInput>
        </div>

        <PermissionGuard permission="isPersonnel">
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Catégories</label>
            <CascadeSelect v-model="selectedCategorie"
                           :options="rootCategories"
                           optionLabel="libelle"
                           optionGroupLabel="libelle"
                           :optionGroupChildren="['enfants']"
                           optionValue="id"
                           class="w-full"
                           placeholder="Sélectionnez une catégorie"
                           :disabled="!selectedService"
            />
          </div>
        </PermissionGuard>

        <div class="flex flex-col">
          <ValidatedInput
              v-model="selectedStatut"
              :options="(services.map(service=>({label:service.libelle,value:service})))"
              name="Statut"
              type="select"
              label="Statut"
              placeholder="Sélectionnez un statut"
              :rules="[]"
              class=""
              :show-clear="true"
          ></ValidatedInput>
        </div>
        <PermissionGuard permission="isPersonnel">
          <div class="flex flex-col">
            <ValidatedInput
                v-model="selectedPersonnel"
                :options="personnels"
                name="assignes"
                type="select"
                label="Assignés"
                placeholder="Sélectionnez un personnel"
                :rules="[]"
                class=""
                :show-clear="true"
            ></ValidatedInput>
          </div>
        </PermissionGuard>
      </div>
    </div>
  </Panel>
  <Tabs value="0">
    <TabList class="mb-10">
      <Tab value="0">Tickets Postés</Tab>
      <PermissionGuard permission="isPersonnelService">
        <Tab value="1">Tickets Reçus</Tab>
      </PermissionGuard>
    </TabList>
<TabGroup>
    <TabPanels>
      <TabPanel value="0">
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
        <div v-else-if="postedTicketsList.length === 0" class="text-center p-10 text-xl text-gray-500">
          Aucun ticket trouvé.
        </div>
        <div v-else class="p-6">
          <div v-for="ticket in postedTicketsList" :key="ticket.id">
            <TicketCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer hover:shadow-md transition-shadow"/>
          </div>
        </div>
      </TabPanel>

      <TabPanel value="1">
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
        <div v-else-if="receivedTicketsList.length === 0" class="text-center p-10 text-xl text-gray-500">
          Aucun ticket trouvé.
        </div>
        <div v-else class="p-6">
          <div v-for="ticket in receivedTicketsList" :key="ticket.id">
            <TicketCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer hover:shadow-md transition-shadow"/>
          </div>
        </div>
      </TabPanel>
    </TabPanels>
</TabGroup>
  </Tabs>
</div>
</template>

<style scoped>
.bg-primary-light {
  background-color: var(--p-tag-primary-background);
  border: 1px solid var(--p-tag-primary-background);
  color: var(--primary-color);
}
</style>