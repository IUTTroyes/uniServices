<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { ValidatedInput } from "@components";
import { createMessageService } from '@requests/helpdesk_services/messageService.js';
import { getTicketService } from '@requests';
import {updateTicketStatutService} from '@requests';
import { PermissionGuard } from '@components';
import {useConfirm} from 'primevue/useconfirm';

const router = useRouter();

const props = defineProps({
  id: String
});

const ticket = ref(null);
const isReplying = ref(false);
const replyText = ref("");
const loading = ref(true);
const confirm=useConfirm();

const getStatutClasses = (statut) => {
  switch (statut) {
    case 'À traiter':   return 'bg-blue-100 text-blue-700 border-blue-200';
    case 'En cours':  return 'bg-orange-100 text-orange-700 border-orange-200';
    case 'En attente': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
    case 'Refusé':    return 'bg-red-100 text-red-700 border-red-200';
    case 'Clôturé':    return 'bg-green-100 text-green-700 border-green-200';
    case 'Accepté':    return 'bg-black-100 text-green-700 border-green-200';
    default:          return 'bg-gray-100 text-gray-700 border-gray-200';
  }
};

const getPriorityClasses = (priority) => {
  switch (priority) {
    case 'Critique': return 'bg-red-100 text-red-700 border-red-200';
    case 'Haute':    return 'bg-orange-100 text-orange-700 border-orange-200';
    case 'Moyenne':  return 'bg-blue-100 text-blue-700 border-blue-200';
    case 'Basse':    return 'bg-green-100 text-green-700 border-green-200';
    default:         return 'bg-gray-100 text-gray-700 border-gray-200';
  }
};

const priorities = ref([
  { label: 'Basse', value: 'BASSE' },
  { label: 'Moyenne', value: 'MOYENNE' },
  { label: 'Haute', value: 'HAUTE' },
  { label: 'Critique', value: 'CRITIQUE' }
]);

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

const changerStatut = (nouveauStatut) => {
  const data = { statut: nouveauStatut };

  confirm.require({
    message: `Êtes-vous sûr de vouloir passer ce ticket au statut "${nouveauStatut}" ?`,
    header: 'Changement de statut',
    icon: 'pi pi-refresh',
    rejectProps: {
      label: 'Annuler',
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: 'Confirmer',
      severity: 'primary'
    },
    accept: async () => {
      try {
        await updateTicketStatutService(ticket.value.id, data, true);
        ticket.value.statut = nouveauStatut;
        /*await router.push({ name: 'Dashboard' });*/
      }
      catch (error) {
        console.error("Erreur lors du changement de statut :", error);
      }
    }
  });
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
    ticket.value=await getTicketService(id)
    console.log(ticket.value)
  }
  catch (error) {
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
    await fetchTicketDetails();
  } catch (error) {
    console.error('Erreur lors de l\'envoi du message', error);
  }
};

const toggleReply = () => {
  isReplying.value = !isReplying.value;
};

onMounted(async () => {
  await fetchTicketDetails();
});

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

      <div class="text-xl text-gray-700 dark:text-white leading-relaxed mb-6 whitespace-pre-line">
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
          <Select v-model="selectedPersonnel" :options="personnels" optionLabel="name" placeholder="Assigner un personnel" class="w-full md:w-70" />

          <Select
              v-model="ticket.priority"
              :options="priorities"
              optionLabel="label"
              optionValue="value"
              placeholder="Ajouter une priorité"
              class="w-full md:w-56 "
              :class="getPriorityClasses(ticket.priority)"
              @change="updatePriority(ticket.id, ticket.priority)"
          />

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

            <div class="flex gap-4 items-center">
              <Button
                  v-for="statutCible in ticket.transitionsAutorisees"
                  :key="statutCible"
                  :label="`Passer à : ${statutCible}`"
                  :severity="statutCible === 'Refusé' ? 'danger' : 'primary'"
                  @click="changerStatut(statutCible)"
              />
            </div>

          </PermissionGuard>
        </div>
      </div>
    </div>

    <div v-else class="p-20 text-center text-xl text-gray-500">
      Ticket introuvable.
    </div>

  </div>
</template>