<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent, PermissionGuard, ListSkeleton } from '@components';
import { useMessageStore } from '../stores/messageStore';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import SelectButton from 'primevue/selectbutton';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';

const router = useRouter();
const messageStore = useMessageStore();

const searchInput = ref('');
const isMarkingAllRead = ref(false);
const filterOptions = [
  { label: 'Tous', value: 'all' },
  { label: 'Non lus', value: 'unread' },
  { label: 'Prioritaires', value: 'urgent' },
  { label: 'Archivés', value: 'archived' },
];

const handleMarkAllAsRead = async () => {
  isMarkingAllRead.value = true;
  try {
    await messageStore.markAllAsRead();
  } catch (err) {
    console.error(err);
  } finally {
    isMarkingAllRead.value = false;
  }
};

onMounted(async () => {
  await messageStore.fetchInbox();
});

const onFilterChange = async (val) => {
  messageStore.activeFilter = val;
  await messageStore.fetchInbox();
};

let searchTimeout = null;
watch(searchInput, (newVal) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    messageStore.searchQuery = newVal;
    messageStore.fetchInbox();
  }, 300);
});

const formatDate = (isoString) => {
  if (!isoString) return '';
  const d = new Date(isoString);
  const now = new Date();
  const isToday = d.toDateString() === now.toDateString();
  if (isToday) {
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  }
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
};

const getInitiales = (nom, prenom) => {
  const p = (prenom || '').charAt(0).toUpperCase();
  const n = (nom || '').charAt(0).toUpperCase();
  return `${p}${n}` || '?';
};
</script>

<template>
  <HeaderComponent
    icon="pi pi-inbox"
    titre="Messagerie interne"
    description="Consultez les annonces, consignes et messages officiels de vos enseignants et de votre département."
    :show-back="false"
    color="teal"
  >
    <template #actions>
      <PermissionGuard permission="isPersonnel">
        <Button
          label="Nouveau message"
          icon="pi pi-pencil"
          severity="primary"
          @click="router.push('/message/nouveau')"
        />
        <Button
          label="Messages envoyés"
          icon="pi pi-send"
          severity="secondary"
          outlined
          @click="router.push('/message/envoyes')"
        />
      </PermissionGuard>
      <Button
        icon="pi pi-refresh"
        severity="secondary"
        text
        rounded
        aria-label="Actualiser"
        @click="messageStore.fetchInbox()"
      />
    </template>
  </HeaderComponent>

  <!-- Filtres et Recherche -->
  <div class="card mb-6 p-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <SelectButton
          v-model="messageStore.activeFilter"
          :options="filterOptions"
          option-label="label"
          option-value="value"
          @change="onFilterChange(messageStore.activeFilter)"
          :allow-empty="false"
        />
        <Badge
          v-if="messageStore.unreadCount > 0 && messageStore.activeFilter === 'unread'"
          :value="messageStore.unreadCount"
          severity="danger"
        />
        <Button
          v-if="messageStore.unreadCount > 0"
          label="Tout marquer comme lu"
          icon="pi pi-check-double"
          severity="secondary"
          outlined
          size="small"
          class="ml-2 text-xs"
          :loading="isMarkingAllRead"
          @click="handleMarkAllAsRead"
        />
      </div>

      <div class="relative w-full sm:w-72">
        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <InputText
          v-model="searchInput"
          placeholder="Rechercher un message ou un enseignant..."
          class="w-full pl-9!"
        />
      </div>
    </div>
  </div>

  <!-- Liste des messages -->
  <div v-if="messageStore.isLoading" class="card p-4 space-y-4">
    <ListSkeleton />
  </div>

  <div v-else-if="messageStore.messages.length === 0" class="card py-16 text-center">
    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-surface-100 dark:bg-surface-800 flex items-center justify-center">
      <i class="pi pi-inbox text-3xl text-gray-400" />
    </div>
    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">
      Aucun message dans cette boîte
    </h3>
    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
      {{ messageStore.activeFilter === 'unread' ? 'Vous avez lu tous vos messages.' : 'Aucune communication reçue pour le moment.' }}
    </p>
  </div>

  <div v-else class="space-y-3">
    <div
      v-for="msg in messageStore.messages"
      :key="msg.id"
      class="card p-4 transition-all duration-150 cursor-pointer hover:shadow-md border-l-4"
      :class="[
        !msg.isLu ? 'border-l-primary bg-primary-50/20 dark:bg-primary-950/20 font-medium' : 'border-l-transparent bg-surface-0 dark:bg-surface-900',
        msg.prioritaire ? 'border-l-red-500!' : ''
      ]"
      @click="router.push(`/message/${msg.id}`)"
    >
      <div class="flex items-start justify-between gap-4">
        <!-- Avatar + Contenu résumé -->
        <div class="flex items-start gap-3 flex-1 min-w-0">
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shrink-0"
            :class="!msg.isLu ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'bg-surface-200 text-surface-600 dark:bg-surface-800 dark:text-surface-400'"
          >
            {{ getInitiales(msg.expediteur?.nom, msg.expediteur?.prenom) }}
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1">
              <span class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ msg.expediteur?.display || 'Personnel' }}
              </span>

              <span
                v-if="msg.cibleLibelle"
                class="text-xs px-2 py-0.5 rounded bg-surface-100 text-surface-700 dark:bg-surface-800 dark:text-surface-300"
              >
                {{ msg.cibleLibelle }}
              </span>

              <Tag
                v-if="msg.isCopie"
                severity="secondary"
                value="En copie (CC)"
                class="text-[10px]"
              />

              <span
                v-if="msg.prioritaire"
                class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300 font-semibold flex items-center gap-1"
              >
                <i class="pi pi-exclamation-circle text-[10px]" /> Prioritaire
              </span>

              <span
                v-if="msg.hasAttachments"
                class="text-xs px-2 py-0.5 rounded bg-surface-100 text-surface-600 dark:bg-surface-800 dark:text-surface-300 flex items-center gap-1"
                title="Pièce(s) jointe(s)"
              >
                <i class="pi pi-paperclip text-[10px]" /> PJ
              </span>

              <span
                v-if="!msg.isLu"
                class="w-2 h-2 rounded-full bg-primary"
                title="Non lu"
              />
            </div>

            <h4 class="text-base text-gray-900 dark:text-white mb-1 truncate" :class="{ 'font-bold': !msg.isLu }">
              {{ msg.sujet }}
            </h4>

            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
              {{ msg.contenu?.replace(/<[^>]*>?/gm, '') }}
            </p>
          </div>
        </div>

        <!-- Date et Actions rapides -->
        <div class="flex flex-col items-end gap-2 shrink-0">
          <span class="text-xs text-gray-400">
            {{ formatDate(msg.createdAt) }}
          </span>

          <div class="flex items-center gap-1" @click.stop>
            <Button
              :icon="msg.isLu ? 'pi pi-envelope' : 'pi pi-envelope-open'"
              severity="secondary"
              text
              rounded
              size="small"
              :tooltip="msg.isLu ? 'Marquer comme non lu' : 'Marquer comme lu'"
              @click="messageStore.toggleRead(msg.id, !msg.isLu)"
            />
            <Button
              :icon="msg.isArchive ? 'pi pi-inbox' : 'pi pi-folder'"
              severity="secondary"
              text
              rounded
              size="small"
              :tooltip="msg.isArchive ? 'Désarchiver' : 'Archiver'"
              @click="messageStore.toggleArchive(msg.id, !msg.isArchive)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>

