<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import ButtonEdit from '@components/components/Buttons/ButtonEdit.vue'
import ButtonDelete from '@components/components/Buttons/ButtonDelete.vue'
import {getTicketsService,updateTicketStatutService,deleteTicketService, deleteMessageService} from '@requests';

const router = useRouter();
const isLoading = ref(true);
const skeletonItems = ref(new Array(5));
const ticketsList = ref([]);

const priorities = ref([
  { label: 'Basse', value: 'BASSE' },
  { label: 'Moyenne', value: 'MOYENNE' },
  { label: 'Haute', value: 'HAUTE' },
  { label: 'Critique', value: 'CRITIQUE' }
]);

const getStatutClasses = (statut) => {
  switch (statut) {
    case 'À traiter': return 'bg-blue-100 text-blue-700 border-blue-200';
    case 'En cours': return 'bg-orange-100 text-orange-700 border-orange-200';
    case 'En attente': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
    case 'Refusé': return 'bg-red-100 text-red-700 border-red-200';
    case 'Clôturé': return 'bg-green-100 text-green-700 border-green-200';
    case 'Accepté': return 'bg-blue-100 text-blue-700 border-blue-200';
    default:          return 'bg-gray-100 text-gray-700 border-gray-200';
  }
};

const getPriorityClasses = (priority) => {
  switch (priority) {
    case 'CRITIQUE': return 'pi pi-exclamation-triangle text-red-600';
    case 'HAUTE':    return 'pi pi-angle-double-up text-orange-500';
    case 'MOYENNE':  return 'pi pi-angle-up text-blue-500';
    case 'BASSE':    return 'pi pi-angle-down text-green-500';
    default:         return 'pi pi-minus text-gray-400';
  }
};

const modifierTicket = (ticket) => {
  router.push({ name: 'TicketView', params: { id: ticket.id } });
};

const supprimerTicket = async (id) => {
  try {
    await deleteMessageService(id,true)
    await deleteTicketService(id, true);
    ticketsList.value = ticketsList.value.filter(t => t.id !== id);
  }
  catch (error) {
    console.error("Erreur lors de la suppression du ticket :", error);
  }
}

const updatePriority = async (id,newPriority) => {
  try{
    const data={priority:newPriority}
    await updateTicketStatutService(id, data, true);
  }
  catch (error){
    console.error('Erreur lors de la mise à jour de la priorité',error);
    await getTickets();
  }
}

const getTickets = async () => {
  try {
    isLoading.value = true;
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
            <InputText placeholder="Search" />
          </IconField>
        </template>
      </Toolbar>
    </div>

    <DataTable :value="isLoading ? skeletonItems : ticketsList" paginator :rows="10"
               :rowsPerPageOptions="[10, 50, 100]" stripedRows showGridlines
               tableStyle="min-width: 50rem">

      <Column field="statut" header="Statut" sortable style="width: 15%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="80%" height="1.5rem" />
          <template v-else>
            <div
                class="inline-flex items-center px-3 py-1 rounded-lg border text-xs font-bold uppercase tracking-wider"
                :class="getStatutClasses(slotProps.data.statut)"
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
            <Select
                v-model="slotProps.data.priority"
                :options="priorities"
                optionLabel="label"
                optionValue="value"
                placeholder="Ajouter une priorité"
                class="w-full md:w-56 font-bold"
                @change="updatePriority(slotProps.data.id, slotProps.data.priority)"
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
            </Select>
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
        <template #body="">
          <Select placeholder="Selectionnez un personnel"></Select>
        </template>
      </Column>
    </DataTable>
  </div>
</template>