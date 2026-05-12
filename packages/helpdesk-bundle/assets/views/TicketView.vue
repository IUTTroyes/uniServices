<script setup>
import { ref, onMounted } from 'vue';
import { tickets } from '@/mocks/messages.js';

const props = defineProps({
  id: String
});

const ticket = ref(null);
const isReplying = ref(false);
const replyText = ref("");

onMounted(() => {
  ticket.value = tickets.find(t => t.id == props.id);
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

const changerStatut = (nouveauStatut) => {

  console.log("Nouveau statut sélectionné :", nouveauStatut);
};
</script>

<template>
  <div>
    <div v-if="ticket" class="card p-6">
      <div class="pt-10 mb-6">
        <div class="flex justify-between items-start gap-4 mb-2">
          <h3 class="text-2xl font-bold text-gray-900 leading-tight">
            {{ ticket.subject }}
          </h3>
          <span
              class="px-4 py-1.5 rounded border text-lg font-medium whitespace-nowrap"
              :class="getStatutClasses(ticket.statut)"
          >
            {{ ticket.statut }}
          </span>
        </div>

        <div class="text-xl mb-12 text-gray-500 italic">
          {{ ticket.category }}
        </div>
      </div>

      <div class="text-xl text-gray-700 leading-relaxed mb-6 whitespace-pre-line">
        {{ ticket.desc }}
      </div>
      <div v-if="ticket.attachment" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 border border-blue-100 rounded text-base text-blue-800">
        <i class="pi pi-file text-sm"></i>
        <span>{{ ticket.attachment }}</span>
      </div>
      <div>
        <div v-permission="isPersonnel" class="flex justify-around pt-20">
          <SplitButton label="Assigner un personnel" severity="secondary" icon="pi pi-plus" @click="personnel" :model="items" />
          <SplitButton label="Ajouter une priorité" severity="secondary" icon="pi pi-plus" @click="priority" :model="items" />
          <Button label="Répondre" severity="info" @click="toggleReply" size="large"/>
        </div>
        <div v-if="isReplying" class="mt-4 p-4 rounded bg-gray-50">
          <h4 class="font-bold mb-2">Votre réponse :</h4>
          <Textarea v-model="replyText" class="w-full mb-3" rows="5" placeholder="Écrivez votre message ici..." />
          <div class="pb-6">
            <FileUpload ref="fileupload" mode="basic" name="demo[]" url="/api/upload" accept="image/*" :maxFileSize="1000000" @upload="onUpload" />
          </div>
          <div class="flex gap-2">
            <Button label="Envoyer" severity="info" size="large" />
            <Button label="Annuler" severity="secondary" variant="outlined" size="large" @click="isReplying = false" />
          </div>
        </div>
        <div v-permission="isPersonnel" class="flex justify-around pt-20">
          <Button label="Clôturer" @click="cloturer" size="large"/>
          <div class="flex gap-4 items-center">
            <SplitButton label="Changer le statut" @click="changerStatut(ticket.statut)" :model="statuts" size="large"/>
          </div>
          <Button label="Refuser" severity="danger" variant="outlined" size="large"/>
        </div>
      </div>
    </div>
    <div v-else class="p-20 text-center text-xl text-gray-500">
      Chargement du ticket...
    </div>
  </div>
</template>