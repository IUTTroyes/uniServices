<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { HeaderComponent, CardSkeleton, ArticleSkeleton } from '@components';
import { useMessageStore } from '../stores/messageStore';
import messageService from '../requests/messageService';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const route = useRoute();
const router = useRouter();
const messageStore = useMessageStore();

const messageId = parseInt(route.params.id, 10);
const message = ref(null);
const isLoading = ref(true);
const stats = ref(null);
const showStatsDialog = ref(false);

onMounted(async () => {
  try {
    message.value = await messageStore.fetchMessage(messageId);
    if (message.value?.isAuthor) {
      loadStats();
    }
  } catch (err) {
    console.error('Erreur chargement message:', err);
  } finally {
    isLoading.value = false;
  }
});

const loadStats = async () => {
  try {
    const res = await messageService.getSentMessageStats(messageId);
    stats.value = res.data;
  } catch (e) {
    console.error('Erreur stats message:', e);
  }
};

const formatDate = (isoString) => {
  if (!isoString) return '-';
  const d = new Date(isoString);
  return d.toLocaleString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const toggleRead = async () => {
  if (!message.value) return;
  await messageStore.toggleRead(messageId, !message.value.isLu);
  message.value.isLu = !message.value.isLu;
};

const toggleArchive = async () => {
  if (!message.value) return;
  await messageStore.toggleArchive(messageId, !message.value.isArchive);
  message.value.isArchive = !message.value.isArchive;
  router.push('/message');
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'Ko', 'Mo', 'Go'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const getFileIcon = (mimeType, filename) => {
  const f = (filename || '').toLowerCase();
  const m = (mimeType || '').toLowerCase();
  if (m.includes('pdf') || f.endsWith('.pdf')) return 'pi pi-file-pdf text-red-500';
  if (m.includes('word') || f.match(/\.(doc|docx)$/)) return 'pi pi-file-word text-blue-500';
  if (m.includes('excel') || m.includes('spreadsheet') || f.match(/\.(xls|xlsx|csv)$/)) return 'pi pi-file-excel text-emerald-500';
  if (m.includes('image') || f.match(/\.(jpg|jpeg|png|gif|webp)$/)) return 'pi pi-image text-purple-500';
  if (m.includes('zip') || f.match(/\.(zip|tar|gz|rar)$/)) return 'pi pi-folder text-amber-500';
  return 'pi pi-file text-surface-500';
};
</script>

<template>
  <HeaderComponent
    icon="pi pi-envelope-open"
    titre="Détail du message"
    description="Consultez le contenu et les informations de diffusion de ce message."
    :show-back="true"
    back-url="/message"
    color="teal"
  >
    <template #actions>
      <div v-if="message && !isLoading" class="flex items-center gap-2">
        <Button
          v-if="message.isAuthor"
          label="Dupliquer"
          icon="pi pi-copy"
          severity="secondary"
          outlined
          size="small"
          @click="router.push(`/message/nouveau?duplicate=${message.id}`)"
        />
        <Button
          :label="message.isLu ? 'Marquer comme non lu' : 'Marquer comme lu'"
          :icon="message.isLu ? 'pi pi-envelope' : 'pi pi-envelope-open'"
          severity="secondary"
          outlined
          size="small"
          @click="toggleRead"
        />
        <Button
          :label="message.isArchive ? 'Désarchiver' : 'Archiver'"
          :icon="message.isArchive ? 'pi pi-inbox' : 'pi pi-folder'"
          severity="secondary"
          text
          size="small"
          @click="toggleArchive"
        />
      </div>
    </template>
  </HeaderComponent>

  <!-- État de chargement avec Skeleton conforme -->
  <div v-if="isLoading" class="space-y-6">
    <div class="card p-6">
      <CardSkeleton />
    </div>
    <div class="card p-6">
      <ArticleSkeleton />
    </div>
  </div>

  <!-- Message introuvable ou erreur -->
  <div v-else-if="!message" class="card py-16 text-center">
    <i class="pi pi-exclamation-circle text-4xl text-red-500 mb-2" />
    <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-50">
      Message introuvable ou accès refusé
    </h3>
    <p class="text-sm text-surface-500 mt-1">
      Ce message n'existe pas ou vous n'avez pas l'autorisation de le consulter.
    </p>
    <Button
      label="Retour à la boîte de réception"
      icon="pi pi-arrow-left"
      class="mt-4"
      severity="primary"
      @click="router.push('/message')"
    />
  </div>

  <!-- Affichage du message -->
  <div v-else class="space-y-6">
    <div class="card p-6 space-y-6">
      <!-- Objet du message mis en valeur -->
      <div class="space-y-2">
        <div class="flex flex-wrap items-center gap-2">
          <Tag v-if="message.prioritaire" severity="danger" icon="pi pi-exclamation-triangle" value="Prioritaire" />
          <Tag v-if="message.isCopie" severity="secondary" icon="pi pi-copy" value="En copie (CC)" />
          <Tag v-if="message.cibleLibelle" severity="info" icon="pi pi-users" :value="message.cibleLibelle" />
          <Tag v-if="message.copieEmail" severity="secondary" icon="pi pi-envelope" value="Notification par email" />
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-surface-900 dark:text-surface-50 tracking-tight">
          {{ message.sujet }}
        </h2>
      </div>

      <!-- Informations Expéditeur & Date -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-surface-200 dark:border-surface-800 gap-4">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-full bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300 flex items-center justify-center font-bold text-base shadow-xs">
            {{ (message.expediteur?.prenom?.charAt(0) || '') + (message.expediteur?.nom?.charAt(0) || '') }}
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-semibold text-surface-900 dark:text-surface-50 text-sm sm:text-base">
                {{ message.expediteur?.display || 'Expéditeur' }}
              </span>
              <span v-if="message.expediteur?.email" class="text-xs text-surface-500 hidden sm:inline">
                &lt;{{ message.expediteur.email }}&gt;
              </span>
            </div>
            <p class="text-xs text-surface-500 m-0">
              Diffusé le {{ formatDate(message.createdAt) }}
            </p>
          </div>
        </div>

        <div v-if="message.dateLecture" class="text-xs text-surface-500 sm:text-right">
          <span class="block">Lu le {{ formatDate(message.dateLecture) }}</span>
        </div>
      </div>

      <!-- Alerte si prioritaire -->
      <div v-if="message.prioritaire" class="p-3 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 text-red-800 dark:text-red-300 flex items-center gap-2 text-sm">
        <i class="pi pi-exclamation-triangle text-base shrink-0" />
        <span>Ce message a été marqué comme prioritaire / urgent par l'expéditeur.</span>
      </div>

      <!-- Contenu du message -->
      <div class="py-2 text-surface-800 dark:text-surface-200 text-base leading-relaxed whitespace-pre-line prose dark:prose-invert max-w-none">
        <div v-html="message.contenu" />
      </div>

      <!-- Pièces jointes attachées -->
      <div v-if="message.piecesJointes && message.piecesJointes.length > 0" class="pt-4 border-t border-surface-200 dark:border-surface-800 space-y-3">
        <h4 class="text-sm font-semibold text-surface-900 dark:text-surface-50 flex items-center gap-2 m-0">
          <i class="pi pi-paperclip text-teal-600 dark:text-teal-400" />
          Pièce(s) jointe(s) ({{ message.piecesJointes.length }})
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div
            v-for="pj in message.piecesJointes"
            :key="pj.id"
            class="flex items-center justify-between p-3 rounded-lg border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800/50 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors"
          >
            <div class="flex items-center gap-2.5 min-w-0">
              <i :class="getFileIcon(pj.mimeType, pj.nomOriginal)" class="text-xl shrink-0" />
              <div class="min-w-0">
                <p class="text-xs font-semibold text-surface-900 dark:text-surface-50 truncate m-0" :title="pj.nomOriginal">
                  {{ pj.nomOriginal }}
                </p>
                <span class="text-[10px] text-surface-500">
                  {{ formatFileSize(pj.taille) }}
                </span>
              </div>
            </div>
            <a
              :href="pj.url"
              target="_blank"
              download
              class="p-button p-component p-button-sm p-button-secondary p-button-text p-button-icon-only shrink-0"
              title="Télécharger / Ouvrir la pièce jointe"
            >
              <i class="pi pi-download" />
            </a>
          </div>
        </div>
      </div>

      <!-- Note informative -->
      <div class="pt-4 border-t border-surface-200 dark:border-surface-800 text-xs text-surface-500 flex items-center gap-2">
        <i class="pi pi-info-circle text-xs shrink-0" />
        <span>
          Ce canal officiel est à communication descendante. Pour échanger avec l'expéditeur, utilisez votre messagerie universitaire ou le HelpDesk.
        </span>
      </div>
    </div>

    <!-- Section Suivi de lecture (visible uniquement par l'auteur émetteur) -->
    <div v-if="message.isAuthor && stats" class="card p-6 space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h3 class="text-base font-bold text-surface-900 dark:text-surface-50 mb-1 flex items-center gap-2">
            <i class="pi pi-chart-pie text-teal-600 dark:text-teal-400" />
            Accusés de lecture ({{ stats.readCount }} / {{ stats.total }})
          </h3>
          <p class="text-xs text-surface-500 m-0">Suivez l'ouverture de votre message par les destinataires.</p>
        </div>
        <Button
          label="Voir la liste des lecteurs"
          icon="pi pi-list"
          severity="secondary"
          size="small"
          outlined
          @click="showStatsDialog = true"
        />
      </div>

      <div class="space-y-1.5">
        <div class="flex justify-between text-xs font-semibold text-surface-600 dark:text-surface-400">
          <span>Taux d'ouverture global</span>
          <span>{{ stats.percent }}%</span>
        </div>
        <ProgressBar :value="stats.percent" :show-value="false" class="h-2.5 rounded-full" />
      </div>
    </div>
  </div>

  <!-- Dialogue Détail des lecteurs -->
  <Dialog
    v-model:visible="showStatsDialog"
    header="Suivi nominatif des ouvertures"
    :modal="true"
    :style="{ width: '52rem' }"
    :breakpoints="{ '960px': '80vw', '641px': '95vw' }"
  >
    <div v-if="stats">
      <DataTable :value="stats.readers" paginator :rows="10" striped-rows size="small">
        <Column field="nom" header="Destinataire" sortable>
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <span class="font-semibold text-sm">{{ data.nom }} {{ data.prenom }}</span>
              <Tag v-if="data.isCopie" severity="secondary" value="CC" class="text-[10px]" />
            </div>
          </template>
        </Column>
        <Column field="email" header="Email">
          <template #body="{ data }">
            <span class="text-xs text-surface-500 font-mono">{{ data.email || '-' }}</span>
          </template>
        </Column>
        <Column field="isLu" header="État" sortable>
          <template #body="{ data }">
            <Tag :severity="data.isLu ? 'success' : 'warn'" :value="data.isLu ? 'Lu' : 'Non lu'" />
          </template>
        </Column>
        <Column field="dateLecture" header="Date d'ouverture" sortable>
          <template #body="{ data }">
            <span class="text-xs text-surface-600 dark:text-surface-400">{{ formatDate(data.dateLecture) }}</span>
          </template>
        </Column>
      </DataTable>
    </div>
  </Dialog>
</template>
