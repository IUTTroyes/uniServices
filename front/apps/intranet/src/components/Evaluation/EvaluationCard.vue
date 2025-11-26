<script setup>
import {defineProps, defineEmits} from 'vue';
import {PermissionGuard} from '@components/index.js';

const props = defineProps({
  evaluation: { type: Object, required: true }
});

const emit = defineEmits(['open-dialog', 'update-visibility', 'update-edit']);

const getSeverity = (type) => {
  switch (type) {
    case 'Examen':
      return 'error';
    case 'Travaux Pratiques':
      return 'info';
    case 'Projet':
      return 'warn';
    default:
      return 'secondary';
  }
};

const onOpen = (mode, header) => {
  emit('open-dialog', props.evaluation.id, mode, header);
};

const onToggleVisibility = () => {
  emit('update-visibility', props.evaluation);
};

const onToggleEdit = () => {
  emit('update-edit', props.evaluation);
};
</script>

<template>
  <div class="card m-0 py-2 px-4">
    <div class="flex flex-col gap-4">
      <div class="flex justify-between items-center gap-4">
        <div class="flex items-center gap-2">
          <div class="text-lg font-bold">
            {{ evaluation.typeIcon }} {{ evaluation.libelle }}
          </div>
          <Message v-if="evaluation.type" :severity="getSeverity(evaluation.type)" size="small">
            {{ evaluation.type }}
          </Message>
          <Message v-if="evaluation.typeGroupe" severity="secondary" size="small">
            {{ evaluation.typeGroupe }}
          </Message>
        </div>
        <div>
          <Message
            :severity="evaluation.etat === 'non_initialisee' ? 'error' : evaluation.etat === 'initialisee' ? 'info' : evaluation.etat === 'planifiee' ? 'warn' : evaluation.etat === 'complet' ? 'success' : 'error'"
            :icon="evaluation.etat === 'non_initialisee' ? 'pi pi-exclamation-triangle' : evaluation.etat === 'initialisee' ? 'pi pi-info-circle' : evaluation.etat === 'planifiee' ? 'pi pi-clock'  : evaluation.etat === 'complet' ? 'pi pi-check-circle' : 'pi pi-exclamation-triangle'"
            size="small">
            {{ evaluation.etat === 'non_initialisee' ? 'À initialiser' : evaluation.etat === 'initialisee' ? 'Initialisée' : evaluation.etat === 'planifiee' ? 'À saisir' : evaluation.etat === 'complet' ? 'Complet' : 'Erreur' }}
          </Message>
        </div>
      </div>

      <div>
        <div class="flex justify-between items-center gap-4">
          <div class="text-sm flex items-center gap-1"><i class="pi pi-users"></i>Notes saisies</div>
          <div class="text-sm flex items-center gap-1">
            <span class="font-bold">{{ evaluation.entered }}/{{ evaluation.total }}</span>
            ({{ evaluation.percent }}%)
          </div>
        </div>
        <ProgressBar :value="evaluation.percent" class="!h-3"></ProgressBar>
      </div>

      <div class="flex justify-between gap-2">
        <div class="flex items-center gap-2 flex-wrap">
          <div>Saisie autorisée :</div>
          <div v-if="evaluation.personnelAutorise?.length > 0">
            <div v-for="personnel in evaluation.personnelAutorise" :key="personnel.id || personnel.display"
                 class="border border-neutral-200 dark:border-neutral-600 rounded-md px-3 py-1 text-sm bg-neutral-100 dark:bg-neutral-800 flex items-center gap-2">
              {{ personnel.display }}
            </div>
          </div>
          <div v-else class="border border-neutral-200 dark:border-neutral-600 rounded-md px-3 py-1 text-sm bg-neutral-100 dark:bg-neutral-800 flex items-center gap-2">
            Aucun personnel autorisé
          </div>
        </div>

        <div class="text-sm text-neutral-500">
          {{ evaluation.date ? (new Date(evaluation.date).getDate() + '/' + (new Date(evaluation.date).getMonth() + 1) + '/' + new Date(evaluation.date).getFullYear()) : '' }}
        </div>
      </div>
    </div>

    <PermissionGuard :permission="{ permission: 'canManageEvaluation', context: { evaluation } }">
      <Divider />
      <div class="flex justify-between items-center gap-4">
        <div class="flex items-center justify-start gap-2">
          <Button v-if="evaluation.etat !== 'non_initialisee'" label="Saisir les notes" icon="pi pi-file-edit" outlined severity="primary" size="small" @click="onOpen('saisie', 'Saisie des notes')" />
          <Button v-if="evaluation.etat !== 'non_initialisee'" label="Modifier" icon="pi pi-pencil" outlined severity="warn" size="small" @click="onOpen('edit', 'Édition de l\'évaluation')" />
          <Button v-if="evaluation.etat !== 'non_initialisee'" label="Statistiques" icon="pi pi-chart-line" outlined severity="info" size="small" @click="onOpen('stat', 'Statistiques de l\'évaluation')" />
          <Button v-if="evaluation.etat === 'non_initialisee'" label="Initialiser" icon="pi pi-plus" outlined severity="primary" size="small" @click="onOpen('edit', 'Initialiser l\'évaluation')" />
        </div>
        <div class="flex items-center justify-end gap-4">
          <div class="flex items-center justify-end gap-1">
            <i :class="evaluation.visible ? 'pi pi-eye text-green-500' : 'pi pi-eye-slash text-gray-400'"></i>
            <span class="text-sm">{{ evaluation.visible ? 'Visible' : 'Masquée' }}</span>
            <ToggleSwitch v-model="evaluation.visible" @change="onToggleVisibility" :disabled="evaluation.etat==='non_initialisee'" />
          </div>
          <div class="flex items-center justify-end gap-1">
            <i :class="evaluation.modifiable ? 'pi pi-lock-open text-green-500' : 'pi pi-lock text-gray-400'"></i>
            <span class="text-sm">{{ evaluation.modifiable ? 'Modifiable' : 'Non-modifiable' }}</span>
            <ToggleSwitch v-model="evaluation.modifiable" @change="onToggleEdit" :disabled="evaluation.etat==='non_initialisee'" />
          </div>
        </div>
      </div>
    </PermissionGuard>
  </div>
</template>
