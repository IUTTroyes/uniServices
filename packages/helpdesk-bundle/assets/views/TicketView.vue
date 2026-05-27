<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { ValidatedInput } from "@components";
import { createMessageService } from '@requests/helpdesk_services/messageService.js';
import { getTicketsService } from '@requests'; // Importation du service de récupération
import { PermissionGuard } from '@components';

const route = useRoute();

const props = defineProps({
  id: String
});

const ticket = ref(null);
const isReplying = ref(false);
const replyText = ref("");
const loading = ref(true);

const items = ref([
  { label: 'Option 1', icon: 'pi pi-refresh' },
  { label: 'Option 2', icon: 'pi pi-times' }
]);

const personnel = () => {
  console.log("Action personnel cliquée");
};

const priority = () => {
  console.log("Action priorité cliquée");
};

const cloturer = () => {
  console.log("Action clôturer cliquée");
};

const onUpload = (event) => {
  console.log("Fichier téléversé", event);
};

const ticketIri = computed(() => {
  const id = props.id || route.params?.id;
  return id ? `/api/helpdesk_tickets/${id}` : null;
});

const fetchTicketDetails = async () => {
  const id = props.id || route.params?.id;
  if (!id) return;

  try {
    loading.value = true;
    // Appel de l'API pour récupérer le ticket spécifique
    const response = await getTicketsService({}, `/${id}`);
    ticket.value = response;
  } catch (error) {
    console.error('Erreur lors de la récupération du ticket:', error);
  } finally {
    loading.value = false;
  }
};

const sendMessage = async () => {
  const messageText = replyText.value && typeof replyText.value === 'object'
      ? replyText.value.value
      : replyText.value;

  if (!messageText || !messageText.trim() || !ticketIri.value) return;

  const payload = {
    content: messageText.trim(),
    ticket: ticketIri.value
  };

  try {
    await createMessageService(payload, true);
    replyText.value = "";
    isReplying.value = false;
    // Recharger les données du ticket pour afficher le nouveau message si nécessaire
    await fetchTicketDetails();
  } catch (error) {
    console.error('Erreur lors de l\'envoi du message', error);
  }
};

onMounted(async () => {
  await fetchTicketDetails();
});

const toggleReply = () => {
  isReplying.value = !isReplying.value;
};

const getStatutClasses = (status) => {
  const map = {
    'Nouveau': 'bg-blue-50 text-blue-700 border-blue-200',
    'En cours': 'bg-orange-50 text-orange-700 border-orange-200',
    'En attente': 'bg-yellow-50 text-yellow-700 border-yellow-200',
    'Urgent': 'bg-red-50 text-red-700 border-red-200',
    'Traité': 'bg-green-50 text-green-700 border-green-200',
  };
  return map[status] || 'bg-gray-50 text-gray-700 border-gray-200';
};

const statuts = [
  {
    label: 'Nouveau',
    icon: 'pi pi-plus-circle',
    command: () => changerStatut('Nouveau')
  },
  {
    label: 'En cours',
    icon: 'pi pi-spinner',
    command: () => changerStatut('En cours')
  },
  {
    label: 'En attente',
    icon: 'pi pi-clock',
    command: () => changerStatut('En attente')
  },
  {
    label: 'Traité',
    icon: 'pi pi-check-circle',
    command: () => changerStatut('Traité')
  },
  {
    label: 'Urgent',
    icon: 'pi pi-exclamation-triangle',
    command: () => changerStatut('Urgent')
  }
];

const changerStatut = (nouveauStatut) => {
  console.log("Nouveau statut sélectionné :", nouveauStatut);
};
</script>

<template>
  <div>
    <div v-if="loading" class="p-20 text-center text-xl text-gray-500">
      Chargement du ticket...
    </div>

    <div v-else-if="ticket" class="card p-6">
      <div class="pt-10 mb-6">
        <div class="flex justify-between items-start gap-4 mb-2">
          <h3 class="text-2xl font-bold text-gray-900 leading-tight">
            {{ ticket.sujet || ticket.subject }}
          </h3>
          <span
              class="px-4 py-1.5 rounded border text-lg font-medium whitespace-nowrap"
              :class="getStatutClasses(ticket.statut)"
          >
            {{ ticket.statut }}
          </span>
        </div>

        <div class="text-xl mb-12 text-gray-500 italic">
          {{ ticket.helpdeskCategorie?.libelle || ticket.category }}
        </div>
      </div>

      <div class="text-xl text-gray-700 leading-relaxed mb-6 whitespace-pre-line">
        {{ ticket.description }}
      </div>

      <div v-if="ticket.pieceJointe" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 border border-blue-100 rounded text-base text-blue-800">
        <i class="pi pi-file text-sm"></i>
        <span>{{ ticket.pieceJointe }}</span>
      </div>

      <div v-if="ticket.messages && ticket.messages.length > 0" class="mt-10 border-t pt-6">
        <h4 class="text-lg font-bold mb-4">Historique des échanges</h4>
        <div class="flex flex-col gap-4">
          <div v-for="msg in ticket.messages" :key="msg.id" class="p-4 rounded-xl border bg-white dark:bg-zinc-800">
            <div class="flex justify-between text-sm text-gray-400 mb-2">
              <span class="font-semibold text-gray-600 dark:text-gray-300">{{ msg.auteur?.prenom }} {{ msg.auteur?.nom }}</span>
              <span>{{ new Date(msg.createdAt).toLocaleString() }}</span>
            </div>
            <div class="text-base text-gray-700 dark:text-gray-200">{{ msg.content }}</div>
          </div>
        </div>
      </div>

      <div>
        <div v-permission="isPersonnel" class="flex justify-around pt-20">
          <SplitButton label="Assigner un personnel" severity="secondary" icon="pi pi-plus" @click="personnel" :model="items" />
          <SplitButton label="Ajouter une priorité" severity="secondary" icon="pi pi-plus" @click="priority" :model="items" />
          <Button label="Répondre" severity="info" @click="toggleReply" size="large"/>
        </div>

        <div v-if="isReplying" class="mt-4 p-4 rounded bg-gray-50">
          <form @submit.prevent="sendMessage()">
            <h4 class="font-bold mb-2">Votre réponse :</h4>
            <ValidatedInput
                v-model="replyText"
                name="message"
                type="text"
                :rules="[]"
                label="Message"
            ></ValidatedInput>
            <div class="pb-6">
              <FileUpload ref="fileupload" mode="basic" name="demo[]" url="/api/upload" accept="image/*" :maxFileSize="1000000" @upload="onUpload" />
            </div>
            <div class="flex gap-2">
              <Button label="Envoyer" type="submit" severity="info" size="large" />
              <Button label="Annuler" severity="secondary" variant="outlined" size="large" @click="isReplying = false" />
            </div>
          </form>
        </div>

        <div class="flex justify-around pt-20">
          <PermissionGuard permission="isPersonnel">
            <Button label="Clôturer" @click="cloturer" size="large"/>
            <div class="flex gap-4 items-center">
              <SplitButton label="Changer le statut" @click="changerStatut(ticket.statut)" :model="statuts" size="large"/>
            </div>
            <Button label="Refuser" severity="danger" variant="outlined" size="large"/>
          </PermissionGuard>
        </div>
      </div>
    </div>

    <div v-else class="p-20 text-center text-xl text-gray-500">
      Ticket introuvable.
    </div>
  </div>
</template>