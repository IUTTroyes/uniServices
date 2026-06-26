<script setup>
import {ref, onMounted} from 'vue';
import {FilterMatchMode} from '@primevue/core/api';
import { useRouter } from 'vue-router';
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue'
import ButtonDelete from '@components/components/Buttons/ButtonDelete.vue'
import {getTicketsService, deleteTicketService, deleteMessageService, getPersonnelsService, getServicesService} from '@requests';
import {getStatutsClasses,getPriorityClasses,priorities,updatePriority,updateAssigne} from "@/utils";
import { ValidatedInput} from "@components";
import {useUsersStore} from "@stores";

const props = defineProps({
  id: String
});

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const userStore = useUsersStore();
const router = useRouter();
const isLoading = ref(true);
const skeletonItems = ref(new Array(5));
const ticketsList = ref([]);
const personnelsParService = ref({});

const modifierTicket = (ticket) => {
  router.push({ name: 'TicketView', params: { id: ticket.id } });
};

const getPersonnelsDuService = async (serviceId) => {
  if (!serviceId || personnelsParService.value[serviceId]) return;
  try {
    const params = { service: serviceId };
    const response = await getPersonnelsService(params);
    const members = response?.['member'] || (Array.isArray(response) ? response : []);

    personnelsParService.value[serviceId] = members.map(p => ({
      label: `${p.prenom} ${p.nom}`,
      value: `/api/personnels/${p.id}`
    }));
  } catch (error) {
    console.error(`Erreur lors du chargement des personnels du service ${serviceId}`);
  }
};

const supprimerTicket = async (id) => {
  try {
    await deleteTicketService(id, true);
    ticketsList.value = ticketsList.value.filter(t => t.id !== id);
  }
  catch (error) {
    console.error("Erreur lors de la suppression du ticket :", error);
  }
}

const getTickets = async () => {
  try {
    isLoading.value = true;

    const paramsUser = {
      personnel: userStore.user.id,
    };
    const userServices = await getServicesService(paramsUser, '/mini');

    ticketsList.value = [];

    const serviceRequests = userServices.map(async (service) => {
      const receivedParams = { service: service.id };
      return await getTicketsService(receivedParams);
    });

    const results = await Promise.all(serviceRequests);

    results.forEach(tickets => {
      if (Array.isArray(tickets)) {
        ticketsList.value.push(...tickets);
      } else if (tickets && tickets['member']) {
        ticketsList.value.push(...tickets['member']);
      }
    });

    const serviceIds = [...new Set(
        ticketsList.value
            .map(t => t.helpdeskCategorie?.service?.id)
            .filter(id => id !== undefined && id !== null)
    )];
    await Promise.all(serviceIds.map(id => getPersonnelsDuService(id)));

  } catch (error) {
    console.error('Impossible de charger les tickets du service:', error);
    ticketsList.value = [];
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  getTickets();
});
</script>

<template>
  <div class="card">
    <div>
      <Toolbar style="border:none">
        <template #end>
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText
                v-model="filters['global'].value"
                placeholder="Rechercher un ticket..."
            />
          </IconField>
        </template>
      </Toolbar>
    </div>

    <DataTable
        :value="isLoading ? skeletonItems : ticketsList"
        paginator
        :rows="10"
        :rowsPerPageOptions="[10, 50, 100]"
        stripedRows
        showGridlines
        tableStyle="min-width: 50rem"
        v-model:filters="filters"
        :globalFilterFields="['sujet', 'subject', 'auteur.display', 'helpdeskCategorie.libelle', 'category', 'statut']"
    >

      <Column field="statut" header="Statut" sortable style="width: 15%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="80%" height="1.5rem" />
          <template v-else>
            <div
                class="inline-flex items-center px-3 py-1 rounded-lg border text-xs font-bold uppercase tracking-wider"
                :class="getStatutsClasses(slotProps.data.statut)"
            >
              {{ slotProps.data.statut }}
            </div>
          </template>
        </template>
      </Column>

      <Column field="auteur" header="Nom" sortable style="width: 20%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="60%" />
          <span v-else>
            {{ slotProps.data.auteur.display || 'Auteur inconnu' }}
          </span>
        </template>
      </Column>

      <Column field="helpdeskCategorie" header="Catégorie" sortable style="width: 20%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="80%" />
          <span v-else>
            {{ slotProps.data.helpdeskCategorie?.libelle || slotProps.data.category }}
          </span>
        </template>
      </Column>

      <Column field="sujet" header="Sujet" sortable style="width: 25%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="90%" />
          <span v-else>
            {{ slotProps.data.sujet || slotProps.data.subject }}
          </span>
        </template>
      </Column>

      <Column field="priority" header="Priorité" sortable style="width: 15%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="100%" height="1.5rem" />
          <template v-else>
            <ValidatedInput
                v-model="slotProps.data.priority"
                :options="priorities"
                optionLabel="label"
                optionValue="value"
                type="select"
                placeholder="Ajouter une priorité"
                class="w-full md:w-56 font-bold"
                @update:model-value="updatePriority(slotProps.data.id, slotProps.data.priority)"
            >
              <template #value="valueProps">
                <div v-if="valueProps.value" class="flex items-center gap-2">
                  <i :class="getPriorityClasses(valueProps.value)"></i>
                  <span>
        {{ priorities.find(p => p.value === valueProps.value)?.label }}
                  </span>
                </div>
                <span v-else>
      {{ valueProps.placeholder }}
    </span>
              </template>

              <template #option="optionProps">
                <div class="flex items-center gap-2">
                  <i :class="getPriorityClasses(optionProps.option.value)"></i>
                  <span>{{ optionProps.option.label }}</span>
                </div>
              </template>
            </ValidatedInput>
          </template>
        </template>
      </Column>

      <Column header="Actions" style="width: 10%">
        <template #body="slotProps">
          <div v-if="isLoading" class="flex gap-2">
            <Skeleton shape="circle" size="2rem" />
            <Skeleton shape="circle" size="2rem" />
          </div>
          <div v-else class="flex gap-2">
            <ButtonEdit tooltip="Modifier" @click="modifierTicket(slotProps.data)"/>
            <ButtonDelete tooltip="Supprimer" @confirm-delete="supprimerTicket(slotProps.data.id)"/>
          </div>
        </template>
      </Column>

      <Column header="Assigner un personnel" style="width: 10%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="100%" height="2rem" />
          <div v-else @click.stop>
            <ValidatedInput
                v-model="slotProps.data.assigne"
                :options="personnelsParService[slotProps.data.helpdeskCategorie?.service?.id] || []"
                optionLabel="label"
                optionValue="value"
                :name="'assigne_' + slotProps.data.id"
                type="select"
                placeholder="Sélectionnez un personnel"
                class="w-full md:w-56"
                @update:model-value="updateAssigne(slotProps.data.id, slotProps.data.assigne)"
            >
              <template #value="valueProps">
                <div v-if="valueProps.value" class="flex items-center gap-2">
                  <i class="pi pi-user text-blue-500"></i>
                  <span>
                    {{ (personnelsParService[slotProps.data.helpdeskCategorie?.service?.id] || []).find(p => p.value === valueProps.value)?.label || valueProps.data.assigne .display }}
                  </span>
                </div>
                <span v-else>
                  {{ valueProps.placeholder }}
                </span>
              </template>

              <template #option="optionProps">
                <div class="flex items-center gap-2">
                  <i class="pi pi-user text-gray-400"></i>
                  <span>{{ optionProps.option.label }}</span>
                </div>
              </template>
            </ValidatedInput>
          </div>
        </template>
      </Column>
    </DataTable>
  </div>
</template>