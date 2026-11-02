<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent, EmptyState } from '@components';
import { useMessageStore } from '../stores/messageStore';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';

const router = useRouter();
const messageStore = useMessageStore();

onMounted(async () => {
  await messageStore.fetchSentMessages();
});

const formatDate = (isoString) => {
  if (!isoString) return '-';
  const d = new Date(isoString);
  return d.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<template>
  <HeaderComponent
    icon="pi pi-send"
    titre="Messages envoyés"
    description="Consultez l'historique de vos diffusions et suivez le taux d'ouverture par les étudiants."
    :show-back="true"
    back-url="/message"
    color="teal"
  >
    <template #actions>
      <Button
        label="Nouveau message"
        icon="pi pi-pencil"
        severity="primary"
        @click="router.push('/message/nouveau')"
      />
      <Button
        label="Boîte de réception"
        icon="pi pi-inbox"
        severity="secondary"
        outlined
        @click="router.push('/message')"
      />
    </template>
  </HeaderComponent>

  <div class="card p-4">
    <DataTable
      :value="messageStore.sentMessages"
      :loading="messageStore.isLoading"
      paginator
      :rows="15"
      striped-rows
      responsive-layout="scroll"
      row-hover
      class="cursor-pointer"
      @row-click="e => router.push(`/message/${e.data.id}`)"
    >
      <template #empty>
        <div class="p-6">
          <EmptyState
            title="Aucun message envoyé"
            description="Vous n'avez diffusé aucune communication officielle pour le moment."
            icon="pi pi-send"
            color="emerald"
            action-label="Rédiger un nouveau message"
            action-icon="pi pi-pencil"
            @action="router.push('/message/nouveau')"
          />
        </div>
      </template>

      <Column field="sujet" header="Sujet" sortable>
        <template #body="{ data }">
          <div class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <span>{{ data.sujet }}</span>
            <Tag v-if="data.prioritaire" severity="danger" value="Urgent" size="small" />
            <i v-if="data.hasAttachments" class="pi pi-paperclip text-xs text-surface-400" title="Contient des pièces jointes" />
          </div>
          <p class="text-xs text-gray-500 m-0 line-clamp-1">{{ data.contenu }}</p>
        </template>
      </Column>

      <Column field="cibleLibelle" header="Destinataires" sortable>
        <template #body="{ data }">
          <Tag severity="info" icon="pi pi-users" :value="data.cibleLibelle || data.typeCible" />
        </template>
      </Column>

      <Column field="createdAt" header="Date d'envoi" sortable>
        <template #body="{ data }">
          <span class="text-xs text-gray-600 dark:text-gray-400">{{ formatDate(data.createdAt) }}</span>
        </template>
      </Column>

      <Column header="Taux d'ouverture" sortable field="percent" style="min-width: 180px;">
        <template #body="{ data }">
          <div class="space-y-1">
            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 font-medium">
              <span>{{ data.readCount }} / {{ data.totalDestinataires }}</span>
              <span>{{ data.percent }}%</span>
            </div>
            <ProgressBar :value="data.percent" :show-value="false" class="h-2 rounded-full" />
          </div>
        </template>
      </Column>

      <Column header="" style="width: 90px;">
        <template #body="{ data }">
          <div class="flex items-center gap-1">
            <Button
              icon="pi pi-copy"
              severity="secondary"
              text
              rounded
              size="small"
              title="Dupliquer ce message"
              @click.stop="router.push(`/message/nouveau?duplicate=${data.id}`)"
            />
            <Button
              icon="pi pi-chevron-right"
              severity="secondary"
              text
              rounded
              size="small"
              title="Voir le détail"
              @click.stop="router.push(`/message/${data.id}`)"
            />
          </div>
        </template>
      </Column>
    </DataTable>
  </div>
</template>

