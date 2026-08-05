<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '@helpers/axios';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';

const router = useRouter();
const route = useRoute();
const toast = useToast();

const isEdit = computed(() => !!route.params.id);
const loading = ref(false);
const saving = ref(false);

const task = ref({
  name: '',
  description: '',
  command: '',
  arguments: [] as string[],
  cronExpression: '0 0 * * *',
  active: true
});

const argumentsText = ref('');
const availableCommands = ref<any[]>([]);

// Presets pour l'expression Cron
const cronPresets = [
  { label: 'Toutes les minutes', value: '* * * * *' },
  { label: 'Toutes les 5 minutes', value: '*/5 * * * *' },
  { label: 'Toutes les heures', value: '0 * * * *' },
  { label: 'Tous les jours à minuit', value: '0 0 * * *' },
  { label: 'Tous les jours à 2h du matin', value: '0 2 * * *' },
  { label: 'Tous les lundis à minuit', value: '0 0 * * 1' }
];

const loadAvailableCommands = async () => {
  try {
    const response = await api.get('/api/scheduler/available-commands');
    availableCommands.value = response.data || [];
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger la liste des commandes disponibles.',
      life: 3000
    });
  }
};

const loadTask = async () => {
  if (!isEdit.value) return;
  loading.value = true;
  try {
    const response = await api.get(`/api/scheduler_tasks/${route.params.id}`);
    const data = response.data;
    task.value = {
      name: data.name || '',
      description: data.description || '',
      command: data.command || '',
      arguments: data.arguments || [],
      cronExpression: data.cronExpression || '0 0 * * *',
      active: data.active !== false
    };
    argumentsText.value = (data.arguments || []).join(' ');
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de charger la tâche planifiée.',
      life: 3000
    });
    router.push('/auth/configuration/scheduler');
  } finally {
    loading.value = false;
  }
};

const setCronPreset = (expr: string) => {
  task.value.cronExpression = expr;
};

const saveTask = async () => {
  if (!task.value.name.trim()) {
    toast.add({ severity: 'warn', summary: 'Validation', detail: 'Veuillez saisir un nom de tâche.', life: 3000 });
    return;
  }
  if (!task.value.command) {
    toast.add({ severity: 'warn', summary: 'Validation', detail: 'Veuillez sélectionner une commande.', life: 3000 });
    return;
  }
  if (!task.value.cronExpression.trim()) {
    toast.add({ severity: 'warn', summary: 'Validation', detail: 'Veuillez saisir une expression Cron.', life: 3000 });
    return;
  }

  saving.value = true;

  // Process arguments text into array of strings
  const parsedArgs = argumentsText.value.trim() 
    ? argumentsText.value.trim().split(/\s+/)
    : [];

  const payload = {
    name: task.value.name,
    description: task.value.description,
    command: task.value.command,
    arguments: parsedArgs,
    cronExpression: task.value.cronExpression,
    active: task.value.active
  };

  try {
    if (isEdit.value) {
      await api.patch(
        `/api/scheduler_tasks/${route.params.id}`, 
        payload,
        { headers: { 'Content-Type': 'application/merge-patch+json' } }
      );
      toast.add({ severity: 'success', summary: 'Succès', detail: 'Tâche planifiée mise à jour.', life: 3000 });
    } else {
      await api.post(
        '/api/scheduler_tasks', 
        payload,
        { headers: { 'Content-Type': 'application/ld+json' } }
      );
      toast.add({ severity: 'success', summary: 'Succès', detail: 'Tâche planifiée créée.', life: 3000 });
    }
    
    // Attendre un peu avant redirection
    setTimeout(() => {
      router.push('/auth/configuration/scheduler');
    }, 1000);
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur est survenue lors de l\'enregistrement.',
      life: 3000
    });
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await Promise.all([
    loadAvailableCommands(),
    loadTask()
  ]);
});
</script>

<template>
  <div class="h-full p-4 max-w-4xl mx-auto">
    <Toast />

    <div class="flex items-center gap-4 mb-6">
      <Button icon="pi pi-arrow-left" severity="secondary" rounded @click="router.push('/auth/configuration/scheduler')" />
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
          <i class="pi pi-calendar text-primary text-3xl"></i>
          {{ isEdit ? 'Modifier la tâche' : 'Nouvelle tâche planifiée' }}
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
          {{ isEdit ? 'Modifiez la configuration de la tâche en BDD.' : 'Ajoutez une nouvelle commande Symfony au planificateur.' }}
        </p>
      </div>
    </div>

    <div v-if="loading" class="card p-8 text-center bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
      <p class="mt-4 text-slate-500">Chargement de la tâche...</p>
    </div>

    <div v-else class="card p-6 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-6">
      <!-- Nom de la tâche -->
      <div class="flex flex-col gap-2">
        <label for="task-name" class="font-semibold text-slate-700 dark:text-slate-300">Nom de la tâche</label>
        <InputText id="task-name" v-model="task.name" placeholder="Ex: Relance des Questionnaires de fin d'année" class="w-full" />
      </div>

      <!-- Description -->
      <div class="flex flex-col gap-2">
        <label for="task-description" class="font-semibold text-slate-700 dark:text-slate-300">Description</label>
        <Textarea id="task-description" v-model="task.description" rows="3" placeholder="Saisissez une courte description de son rôle..." class="w-full" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Commande Symfony -->
        <div class="flex flex-col gap-2">
          <label for="task-command" class="font-semibold text-slate-700 dark:text-slate-300">Commande Symfony</label>
          <Dropdown 
            id="task-command" 
            v-model="task.command" 
            :options="availableCommands" 
            optionLabel="name" 
            optionValue="name" 
            placeholder="Sélectionnez une commande console" 
            class="w-full" 
          >
            <template #option="slotProps">
              <div>
                <div class="font-semibold font-mono text-sm">{{ slotProps.option.name }}</div>
                <div class="text-xs text-slate-400">{{ slotProps.option.description }}</div>
              </div>
            </template>
          </Dropdown>
        </div>

        <!-- Arguments & Options -->
        <div class="flex flex-col gap-2">
          <label for="task-arguments" class="font-semibold text-slate-700 dark:text-slate-300">Arguments & Options</label>
          <InputText id="task-arguments" v-model="argumentsText" placeholder="Ex: --days=3 -vv" class="w-full font-mono text-sm" />
          <small class="text-slate-400">Séparez les arguments et options par des espaces simples.</small>
        </div>
      </div>

      <!-- Planification Cron -->
      <div class="flex flex-col gap-2">
        <label for="task-cron" class="font-semibold text-slate-700 dark:text-slate-300">Expression Cron</label>
        <div class="flex gap-4">
          <InputText id="task-cron" v-model="task.cronExpression" placeholder="Ex: 0 2 * * *" class="w-full font-mono" />
        </div>
        
        <!-- Description du codage cron -->
        <div class="flex flex-col gap-1 p-3 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Structure d'une expression Cron (5 champs séparés par des espaces) :</span>
          <div class="grid grid-cols-5 text-center font-mono text-xs text-slate-600 dark:text-slate-300 mt-2 bg-slate-100 dark:bg-slate-900 rounded p-2">
            <div>
              <div class="font-bold text-primary">*</div>
              <div class="text-[10px] text-slate-400 mt-1">Minute<br>(0 - 59)</div>
            </div>
            <div>
              <div class="font-bold text-primary">*</div>
              <div class="text-[10px] text-slate-400 mt-1">Heure<br>(0 - 23)</div>
            </div>
            <div>
              <div class="font-bold text-primary">*</div>
              <div class="text-[10px] text-slate-400 mt-1">Jour du mois<br>(1 - 31)</div>
            </div>
            <div>
              <div class="font-bold text-primary">*</div>
              <div class="text-[10px] text-slate-400 mt-1">Mois<br>(1 - 12)</div>
            </div>
            <div>
              <div class="font-bold text-primary">*</div>
              <div class="text-[10px] text-slate-400 mt-1">Jour de la sem.<br>(0 - 6) (0=Dim)</div>
            </div>
          </div>
        </div>
        
        <!-- Préréglages Cron -->
        <div class="mt-2">
          <span class="text-xs font-semibold text-slate-400 block mb-2">Préréglages :</span>
          <div class="flex flex-wrap gap-2">
            <Button 
              v-for="preset in cronPresets" 
              :key="preset.value" 
              :label="preset.label" 
              class="p-button-outlined p-button-xs text-xs" 
              size="small"
              severity="secondary"
              @click="setCronPreset(preset.value)" 
            />
          </div>
        </div>
      </div>

      <!-- Activation / Désactivation -->
      <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
        <div>
          <div class="font-semibold text-slate-800 dark:text-white">Activer la planification</div>
          <div class="text-xs text-slate-400 mt-0.5">Si décoché, la tâche sera ignorée par le planificateur.</div>
        </div>
        <ToggleSwitch v-model="task.active" />
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 mt-4">
        <Button label="Annuler" severity="secondary" @click="router.push('/auth/configuration/scheduler')" />
        <Button 
          :label="saving ? 'Enregistrement...' : 'Enregistrer'" 
          :icon="saving ? 'pi pi-spin pi-spinner' : 'pi pi-check'" 
          severity="primary" 
          :disabled="saving"
          @click="saveTask" 
        />
      </div>
    </div>
  </div>
</template>
