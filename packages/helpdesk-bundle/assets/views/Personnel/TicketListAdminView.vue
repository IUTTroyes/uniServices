<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { tickets as mockTickets } from '@/mocks/messages.js';

const router = useRouter();
const isLoading = ref(true);
const skeletonItems = ref(new Array(5));
const tickets = ref([...mockTickets]);

const getPriorityClasses = (priority) => {
  switch (priority) {
    case 'Critique': return 'bg-red-100 text-red-700 border-red-200';
    case 'Haute':    return 'bg-orange-100 text-orange-700 border-orange-200';
    case 'Moyenne':  return 'bg-blue-100 text-blue-700 border-blue-200';
    case 'Basse':    return 'bg-green-100 text-green-700 border-green-200';
    default:         return 'bg-gray-100 text-gray-700 border-gray-200';
  }
};

const statuts = [
  {
    label: 'Nouveau',
    icon: 'pi pi-plus-circle',
    class: 'bg-blue-100 text-blue-700 border-blue-200',
    command: () => changerStatut('Nouveau')
  },
  {
    label: 'En cours',
    icon: 'pi pi-spinner',
    class: 'bg-orange-100 text-orange-700 border-orange-200',
    command: () => changerStatut('En cours')
  },
  {
    label: 'En attente',
    icon: 'pi pi-clock',
    class:'bg-yellow-100 text-yellow-700 border-yellow-200',
    command: () => changerStatut('En attente')
  },
  {
    label: 'Traité',
    icon: 'pi pi-check-circle',
    class:'bg-red-100 text-red-700 border-red-200',
    command: () => changerStatut('Traité')
  },
  {
    label: 'Urgent',
    icon: 'pi pi-exclamation-triangle',
    class: 'bg-green-100 text-green-700 border-green-200',
    command: () => changerStatut('Urgent')
  }
];

const getStatutClasses = (statut) => {
  switch (statut) {
    case 'Nouveau':   return 'bg-blue-100 text-blue-700 border-blue-200';
    case 'En cours':  return 'bg-orange-100 text-orange-700 border-orange-200';
    case 'En attente': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
    case 'Urgent':    return 'bg-red-100 text-red-700 border-red-200';
    case 'Traité':    return 'bg-green-100 text-green-700 border-green-200';
    default:          return 'bg-gray-100 text-gray-700 border-gray-200';
  }
};

const modifierTicket = (ticket) => {
  router.push({ name: 'TicketView', params: { id: ticket.id } });
};

const supprimerTicket = (id) => {
  const confirmation = confirm("Êtes-vous sûr de vouloir supprimer ce ticket ?");
  if (confirmation) {
    tickets.value = tickets.value.filter(t => t.id !== id);
    console.log(`Ticket ${id} supprimé.`);
  }
};
onMounted(() => {
  setTimeout(() => {
    isLoading.value = false;
  }, 2000);
});
</script>

<template>
  <div class="card">
    <DataTable :value="isLoading ? skeletonItems : tickets" paginator :rows="10"
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

      <Column field="author" header="Nom" sortable style="width: 20%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="60%" />
          <span v-else>{{ slotProps.data.author }}</span>
        </template>
      </Column>

      <Column field="category" header="Catégorie" sortable style="width: 20%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="80%" />
          <span v-else>{{ slotProps.data.category }}</span>
        </template>
      </Column>

      <Column field="statut" header="Statut" sortable style="width: 15%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="50%" />
          <span v-else>{{ slotProps.data.statut }}</span>
        </template>
      </Column>

      <Column field="subject" header="Sujet" sortable style="width: 25%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="90%" />
          <span v-else>{{ slotProps.data.subject }}</span>
        </template>
      </Column>

      <Column field="priority" header="Priorité" sortable style="width: 15%">
        <template #body="slotProps">
          <Skeleton v-if="isLoading" width="100%" height="1.5rem" />
          <template v-else>
            <div v-if="slotProps.data.priority"
                 class="inline-flex items-center px-3 py-1 rounded-lg border text-xs font-bold uppercase tracking-wider"
                 :class="getPriorityClasses(slotProps.data.priority)">
              {{ slotProps.data.priority }}
            </div>
            <span v-else class="text-gray-400 italic text-sm">Non définie</span>
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
            <Button icon="pi pi-pencil" severity="secondary" rounded outlined @click="modifierTicket(slotProps.data)"/>
            <Button icon="pi pi-trash" severity="danger" rounded outlined @click="supprimerTicket(slotProps.data.id)"/>
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

<style scoped>

</style>