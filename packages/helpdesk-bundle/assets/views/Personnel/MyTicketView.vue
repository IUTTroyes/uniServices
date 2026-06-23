<script setup>
import TicketCard from "@/components/TicketCard.vue";
import { useUsersStore } from "@stores";
import { computed, ref, onMounted } from "vue";
import { useRouter } from 'vue-router';
import { PermissionGuard, ValidatedInput } from "@components";
import { TabGroup, TabPanels } from "@headlessui/vue";
import DatePicker from 'primevue/datepicker';
import CascadeSelect from 'primevue/cascadeselect';
import {
  getPersonnelsService,
  getServicesService,
  getTicketsService,
} from "@requests";
import { FilterMatchMode } from "@primevue/core/api";


const router = useRouter();
const userStore = useUsersStore();
const services = ref([]);
const selectedService = ref(null);
const selectedCategorie = ref(null);
const selectedStatut = ref(null);
const selectedPersonnel = ref(null);
const ticketsList = ref([]);
const loading = ref(true);
const personnelList = ref([]);
const statutsOptions = ref([]);
const buttondisplay = ref(null);
const userServices = ref([]);
const postedTickets = ref([]);
const receivedTickets = ref([]);

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  'ticket.created': { value: null, matchMode: FilterMatchMode.EQUALS }
});

const assignesOptions = computed(() => {
  return personnelList.value.map(p => ({
    label: `${p.prenom} ${p.nom}`,
    value: `/api/personnels/${p.id}`
  }));
});

const filteredPostedTickets = computed(() => {
  const search = filters.value.global.value?.toLowerCase().trim();
  const date = filters.value['ticket.created'].value;


  if (!search && !date) return postedTickets.value;

  return postedTickets.value.filter(ticket => {

    const matchesSearch = !search || (
        ticket.subject?.toLowerCase().includes(search) ||
        ticket.description?.toLowerCase().includes(search) ||
        ticket.statut?.toLowerCase().includes(search) ||
        ticket.helpdeskCategorie?.libelle?.toLowerCase().includes(search)
    );

    const matchesDate = !date || (() => {
      if (!ticket.created) return false;

      const pad = (n) => String(n).padStart(2, '0');

      const filterDate = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

      const ticketDate = ticket.created.split('T')[0];

      return ticketDate === filterDate;
    })();

    return matchesSearch && matchesDate;
  });
});


const filteredReceivedTickets = computed(() => {
  const search = filters.value.global.value?.toLowerCase().trim();
  if (!search) return receivedTickets.value;

  return receivedTickets.value.filter(ticket => {
    return (
        ticket.subject?.toLowerCase().includes(search) ||
        ticket.description?.toLowerCase().includes(search) ||
        ticket.statut?.toLowerCase().includes(search) ||
        ticket.helpdeskCategorie?.libelle?.toLowerCase().includes(search) ||
        ticket.auteur?.display?.toLowerCase().includes(search)
    );
  });
});

const goToTicket = (id) => {
  router.push({ name: 'TicketView', params: { id: id } });
};

const initiales = computed(
    () => `${userStore.user?.prenom?.charAt(0) || ""}${userStore.user?.nom?.charAt(0) || ""}`
);

const rootCategories = computed(() => {
  if (!selectedService.value?.helpdeskCategories) return [];
  return selectedService.value.helpdeskCategories.filter(cat => !cat.parent);
});

const getServices = async () => {
  try {
    services.value = await getServicesService({}, '/form_ticket');
  } catch (error) {
    console.error('Erreur dans getServices', error);
  }
};

const getTickets = async () => {
  try {
    loading.value = true;

    const postedParams = { auteur: userStore.user?.id };
    postedTickets.value = await getTicketsService(postedParams);

    const params = { personnel: userStore.user.id };
    userServices.value = await getServicesService(params, '/mini');

    receivedTickets.value = [];
    for (const service of userServices.value) {
      const tickets = await getTicketsService({ service: service.id });
      receivedTickets.value.push(...tickets);
    }

  } catch (error) {
    console.error('Impossible de charger les tickets:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await getServices();
  await getTickets();
});
</script>

<template>
  <div class="card">
    <Panel
        header="Filtrer par:"
        :pt="{
      root: '!border !border-violet-400 overflow-hidden',
      header: '!bg-violet-100 dark:!bg-violet-500 !border-none',
      content: 'bg-violet-100 dark:bg-violet-500'
    }"
    >
      <div class="flex justify-center w-full py-4">
        <div class="flex flex-row gap-10 items-start">

          <div class="flex flex-col">
            <label for="creation" class="mb-1 text-sm font-medium">Date de création</label>
            <div class="flex items-center gap-2">
              <DatePicker
                  v-model="filters['ticket.created'].value"
                  showIcon
                  fluid
                  :showOnFocus="false"
                  dateFormat="dd/mm/yy"
              />
              <Button
                  v-if="filters['ticket.created'].value"
                  icon="pi pi-times"
                  severity="secondary"
                  text
                  rounded
                  @click="filters['ticket.created'].value = null"
                  v-tooltip="'Effacer la date'"
              />
            </div>
          </div>

          <div class="flex flex-col">
            <ValidatedInput
                v-model="selectedService"
                :options="services.map(service => ({ label: service.libelle, value: service }))"
                name="services"
                type="select"
                label="Services"
                placeholder="Sélectionnez un service"
                :rules="[]"
                :show-clear="true"
            />
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Catégories</label>
            <CascadeSelect
                v-model="selectedCategorie"
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

          <div class="flex flex-col">
            <ValidatedInput
                v-model="selectedStatut"
                :options="statutsOptions"
                name="Statut"
                type="select"
                label="Statut"
                placeholder="Sélectionnez un statut"
                :rules="[]"
                :show-clear="true"
            />
          </div>

          <PermissionGuard permission="isPersonnelService">
            <div class="flex flex-col">
              <ValidatedInput
                  v-model="selectedPersonnel"
                  :options="assignesOptions"
                  optionLabel="label"
                  optionValue="value"
                  name="assigne"
                  type="select"
                  label="Assignés"
                  :rules="[]"
                  placeholder="Sélectionnez un personnel"
                  class="w-full md:w-56"
                  :show-clear="true"
              >
                <template #value="valueProps">
                  <div v-if="valueProps.value" class="flex items-center gap-2">
                    <i class="pi pi-user text-blue-500"></i>
                    <span>{{ assignesOptions.find(p => p.value === valueProps.value)?.label }}</span>
                  </div>
                  <span v-else>{{ valueProps.placeholder }}</span>
                </template>
                <template #option="optionProps">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-user text-gray-400"></i>
                    <span>{{ optionProps.option.label }}</span>
                  </div>
                </template>
              </ValidatedInput>
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
            <Toolbar style="border:none">
              <template #start>
                <div class="font-semibold text-xl">Tickets Postés</div>
              </template>
              <template #end>
                <IconField>
                  <InputIcon><i class="pi pi-search" /></InputIcon>
                  <InputText
                      v-model="filters['global'].value"
                      placeholder="Rechercher un ticket..."
                  />
                </IconField>
              </template>
            </Toolbar>

            <div v-if="loading" class="text-center p-10 text-xl">
              Chargement des tickets...
            </div>
            <div v-else-if="filteredPostedTickets.length === 0" class="text-center p-10 text-xl text-gray-500">
              Aucun ticket trouvé.
            </div>
            <div v-else class="p-6">
              <div v-for="ticket in filteredPostedTickets" :key="ticket.id">
                <TicketCard
                    :ticket="ticket"
                    @click="goToTicket(ticket.id)"
                    class="cursor-pointer hover:shadow-md transition-shadow"
                />
              </div>
            </div>
          </TabPanel>

          <!-- Tickets Reçus -->
          <TabPanel value="1">
            <Toolbar style="border:none">
              <template #start>
                <div class="font-semibold text-xl">Tickets Reçus</div>
              </template>
              <template #end>
                <IconField>
                  <InputIcon><i class="pi pi-search" /></InputIcon>
                  <InputText
                      v-model="filters.global.value"
                      placeholder="Rechercher un ticket..."
                  />
                </IconField>
              </template>
            </Toolbar>

            <div v-if="loading" class="text-center p-10 text-xl">
              Chargement des tickets...
            </div>
            <div v-else-if="filteredReceivedTickets.length === 0" class="text-center p-10 text-xl text-gray-500">
              Aucun ticket trouvé.
            </div>
            <div v-else class="p-6">
              <div v-for="ticket in filteredReceivedTickets" :key="ticket.id">
                <TicketCard
                    :ticket="ticket"
                    @click="goToTicket(ticket.id)"
                    class="cursor-pointer hover:shadow-md transition-shadow"
                />
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