<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { ValidatedInput,PermissionGuard} from "@components";
import {useUsersStore} from '@stores';
import {getPersonnelsService,createMessageService,getTicketService,updateTicketStatutService,getMessagesService} from '@requests';
import {useConfirm} from 'primevue/useconfirm';
import {getStatutsClasses,getPriorityClasses,priorities,updatePriority} from "@/utils";


const props = defineProps({
  id: String
});

const ticket = ref(null);
const personnelList= ref([]);
const isReplying = ref(false);
const replyText = ref("");
const loading = ref(true);
const confirm=useConfirm();
const userStore=useUsersStore();
const user=computed(()=>userStore.user)

const assignesOptions = computed(() => {
  return personnelList.value.map(p => ({
    label: `${p.prenom} ${p.nom}`,
    value: `/api/personnels/${p.id}`
  }))
})

const getPersonnelsDuService = async (serviceId) => {
  if (!serviceId) return;
  try{
    const params = {
      service: serviceId
    }
    personnelList.value = await getPersonnelsService(params)
  } catch (error) {
    console.error ('Erreur lors du chargement des personnels')
  }
}

const updateAssigne = async (id,personnelIri) => {
  try{
    const data = {
      assigne:personnelIri
    }
    await updateTicketStatutService(id, data, true);
  }
  catch (error){
    console.error('Erreur lors de la mise à jour du personnel assigné',error);
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


const getMessages = async () =>{
  try{
    const params = {
      ticket: props.id
    }
    const response = await getMessagesService(params, `/simple-ticket-message`);
    if (response && response['member']) {
      ticket.value.messages = response['member'];
    } else if (Array.isArray(response)) {
      ticket.value.messages = response;
    } else {
      ticket.value.messages = [];
    }
  }
  catch (error){
    console.error('Erreur lors de la récupération des messages',error);
  }
}

const ticketIri = computed(() => {
  return props.id ? `/api/helpdesk_tickets/${props.id}` : null;
});

const getTicket = async () => {
  if (!props.id) return;
  try {
    loading.value = true;
    ticket.value = await getTicketService(props.id,{},'/simple-ticket')
    const serviceId = ticket.value.helpdeskCategorie?.service?.id;
    if (serviceId) {
      await getPersonnelsDuService(serviceId);
    }
    await getMessages();
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
    ticket: ticketIri.value,
    auteur:user.value ? `/api/personnels/${user.value.id}` : null,
  };
  console.log('Envoi du message avec le payload:', payload);
  try {
    await createMessageService(payload, true);
    replyText.value = "";
    isReplying.value = false;
    await getTicket();
  } catch (error) {
    console.error('Erreur lors de l\'envoi du message', error);
  }
};

const toggleReply = () => {
  isReplying.value = !isReplying.value;
};

onMounted(async () => {
  await getTicket();
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
              :class="getStatutsClasses(ticket.statut)"
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

      <p>{{ticket.helpdeskMessages?.content}}</p>

      <div v-if="ticket.messages && ticket.messages.length > 0" class="mt-6">
        <h4 class="text-lg font-bold mb-4">Historique des échanges</h4>

        <div v-for="message in ticket.messages" :key="message.id" class="mb-4 p-4 bg-gray-50 dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700">

          <div class="flex justify-between items-center mb-2">
      <span class="font-bold text-sm text-blue-600">
        {{ message.auteur?.display || 'Auteur inconnu' }}
      </span>
          </div>
          <p class="text-gray-700 dark:text-gray-200 whitespace-pre-line">{{ message.content }}</p>
        </div>
      </div>

      <div>
        <PermissionGuard permission="isPersonnelService">
        <div class="flex justify-around pt-20">
          <ValidatedInput
              v-model="ticket.assigne"
              :options="assignesOptions"
              optionLabel="label"
              optionValue="value"
              name="assigne"
              type="select"
              label="Assigner un personnel"
              :rules="[]"
              placeholder="Sélectionnez un personnel"
              class="w-full md:w-56"
              @update:model-value="updateAssigne(ticket.id, ticket.assigne)"
          >
            <template #value="valueProps">
              <div v-if="valueProps.value" class="flex items-center gap-2">
                <i class="pi pi-user text-blue-500"></i>
                <span>
        {{ assignesOptions.find(p => p.value === valueProps.value)?.label || 'Personnel assigné' }}
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

          <ValidatedInput
              v-model="ticket.priority"
              :options="priorities"
              optionLabel="label"
              optionValue="value"
              name="priority"
              type="select"
              label="Priorité"
              :rules="[]"
              placeholder="Ajouter une priorité"
              class="w-full md:w-56"
              @update:model-value="updatePriority(ticket.id, ticket.priority)"
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

            <div class="flex gap-4 items-center">
              <Button
                  v-for="statutCible in ticket.transitionsAutorisees"
                  :key="statutCible"
                  :label="`Passer à : ${statutCible}`"
                  :severity="statutCible === 'Refusé' ? 'danger' : 'primary'"
                  @click="changerStatut(statutCible)"
              />
            </div>
        </div>
        </PermissionGuard>
      </div>
    </div>

    <div v-else class="p-20 text-center text-xl text-gray-500">
      Ticket introuvable.
    </div>

  </div>
</template>
