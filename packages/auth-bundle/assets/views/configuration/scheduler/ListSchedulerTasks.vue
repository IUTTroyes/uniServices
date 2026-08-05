<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@helpers/axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

const tasks = ref<any[]>([]);
const loading = ref(true);

const loadTasks = async () => {
  loading.value = true;
  try {
    const response = await api.get('/api/scheduler_tasks');
    tasks.value = response.data['member'] || response.data['hydra:member'] || response.data || [];
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger la liste des tâches.',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
};

const toggleActive = async (task: any) => {
  try {
    await api.patch(`/api/scheduler_tasks/${task.id}`, 
      { active: task.active },
      { headers: { 'Content-Type': 'application/merge-patch+json' } }
    );
    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: `La tâche "${task.name}" a été ${task.active ? 'activée' : 'désactivée'}.`,
      life: 3000
    });
  } catch (error) {
    // Revert switch state on error
    task.active = !task.active;
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de modifier le statut de la tâche.',
      life: 3000
    });
  }
};

const deleteTask = (task: any) => {
  confirm.require({
    message: `Voulez-vous vraiment supprimer définitivement la tâche planifiée "${task.name}" ?`,
    header: 'Confirmation de suppression',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Oui, supprimer',
    rejectLabel: 'Annuler',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await api.delete(`/api/scheduler_tasks/${task.id}`);
        tasks.value = tasks.value.filter(t => t.id !== task.id);
        toast.add({
          severity: 'success',
          summary: 'Supprimée',
          detail: 'La tâche planifiée a été supprimée avec succès.',
          life: 3000
        });
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Erreur',
          detail: 'Impossible de supprimer la tâche planifiée.',
          life: 3000
        });
      }
    }
  });
};

const formatDate = (dateStr: string | null) => {
  if (!dateStr) return 'Jamais';
  const d = new Date(dateStr);
  return d.toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  loadTasks();
});
</script>

<template>
  <div class="h-full p-4">
    <Toast />
    <ConfirmDialog />

    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
          <i class="pi pi-calendar text-primary text-3xl"></i>
          Planificateur de tâches
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
          Gérez l'ensemble des tâches de console planifiées au format Cron.
        </p>
      </div>
      <Button 
        label="Nouvelle tâche" 
        icon="pi pi-plus" 
        severity="primary" 
        @click="router.push('/auth/configuration/scheduler/new')" 
      />
    </div>

    <!-- Alert / Tip box -->
    <div class="mb-6 bg-blue-50 dark:bg-slate-800 border border-blue-200 dark:border-slate-700 rounded-lg p-4 text-sm text-blue-800 dark:text-blue-300 flex items-start gap-3">
      <i class="pi pi-info-circle text-lg mt-0.5"></i>
      <div>
        <span class="font-semibold">Note d'exécution :</span>
        Le planificateur utilise le worker messenger par défaut de Symfony. Pour démarrer l'exécution en arrière-plan, lancez la commande :
        <code class="px-2 py-1 bg-blue-100 dark:bg-slate-900 rounded font-mono text-xs text-blue-900 dark:text-blue-200 ml-1">
          php bin/console messenger:consume scheduler_default
        </code>
      </div>
    </div>

    <div class="card p-4 shadow-sm border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-xl">
      <DataTable :value="tasks" :loading="loading" responsiveLayout="scroll" :rows="10" :paginator="true"
                 paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                 currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} tâches">
        <template #empty>
          <div class="text-center py-8 text-slate-400">
            <i class="pi pi-calendar-times text-4xl mb-3"></i>
            <p>Aucune tâche planifiée enregistrée.</p>
          </div>
        </template>

        <Column field="name" header="Tâche" sortable class="font-medium text-slate-800 dark:text-white">
          <template #body="{ data }">
            <div>
              <div class="font-semibold">{{ data.name }}</div>
              <div class="text-xs text-slate-400 mt-0.5">{{ data.description || 'Aucune description' }}</div>
            </div>
          </template>
        </Column>

        <Column field="command" header="Commande Symfony" class="font-mono text-xs text-slate-600 dark:text-slate-300">
          <template #body="{ data }">
            <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded font-mono">
              {{ data.command }}
              <span v-if="data.arguments && data.arguments.length" class="text-slate-400">
                {{ data.arguments.join(' ') }}
              </span>
            </span>
          </template>
        </Column>

        <Column field="cronExpression" header="Récurrence (Cron)" sortable class="font-mono text-sm text-slate-700 dark:text-slate-300"></Column>

        <Column field="active" header="Statut" sortable style="width: 10%">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <ToggleSwitch v-model="data.active" @change="toggleActive(data)" />
              <Tag :severity="data.active ? 'success' : 'secondary'" :value="data.active ? 'Actif' : 'Inactif'" />
            </div>
          </template>
        </Column>

        <Column field="lastRun" header="Dernière exécution" sortable>
          <template #body="{ data }">
            <span class="text-sm">{{ formatDate(data.lastRun) }}</span>
          </template>
        </Column>

        <Column field="nextRun" header="Prochaine exécution" sortable>
          <template #body="{ data }">
            <span class="text-sm font-semibold text-primary">{{ formatDate(data.nextRun) }}</span>
          </template>
        </Column>

        <Column header="Actions" class="text-right" style="width: 10%">
          <template #body="{ data }">
            <div class="flex gap-2 justify-end">
              <Button icon="pi pi-pencil" severity="secondary" rounded @click="router.push(`/auth/configuration/scheduler/${data.id}/edit`)" />
              <Button icon="pi pi-trash" severity="danger" rounded @click="deleteTask(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>
