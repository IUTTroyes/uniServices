<script setup>
import {ref} from 'vue';
import {PermissionGuard, ValidatedInput} from "@components";
import {getStatutsClasses,getPriorityClasses,priorities,updatePriority} from "@/utils";

const props = defineProps({
  ticket: {
    type: Object,
    required: true
  }
});


</script>

<template>
  <div class="border border-gray-200 rounded-lg p-5 shadow-md hover:shadow-lg transition-shadow mb-4">
    <div class="flex items-start justify-between gap-4 mb-3">
      <div class="flex-1">
        <div class="font-semibold text-xl">
          {{ ticket.sujet || ticket.subject }}
        </div>
      </div>

      <div class="flex items-center gap-6">
        <span class="text-sm text-gray-600 italic dark:text-gray-400">
          {{ ticket.helpdeskCategorie?.libelle || ticket.category }}
        </span>
        <span class="px-3 py-1 rounded border text-sm font-medium" :class="getStatutsClasses(ticket.statut)">
          {{ ticket.statut }}
        </span>
      </div>
    </div>

    <div class="text-sm mb-4 line-clamp-2">
      <p class="text-muted-color">{{ ticket.description }}</p>
    </div>

    <div class="flex justify-between items-center w-full gap-4">
      <div v-if="(ticket.files_names && ticket.files_names.length > 0) || ticket.attachment"
           class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 border border-blue-100 rounded text-sm text-blue-800">
        <i class="pi pi-file text-xs"></i>
        <span>
          {{ ticket.files_names?.length || 1 }} fichier<span v-if="ticket.files_names?.length > 1">s</span>
        </span>
      </div>

      <div v-if="ticket.helpdeskMessages || ticket.messages"
           class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 border border-blue-400 rounded text-sm text-blue-600 dark:text-gray-300">
        <i class="pi pi-comments text-xs"></i>
        <span>
          {{ ticket.helpdeskMessages?.length ?? ticket.messages?.length ?? 0 }} message<span v-if="(ticket.helpdeskMessages?.length ?? ticket.messages?.length ?? 0) > 1">s</span>
        </span>
      </div>

      <div class="flex justify-end ml-auto">
        <PermissionGuard permission="isPersonnelService">
          <div @click.stop>
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
                @change="updatePriority(ticket.id, ticket.priority)"
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
          </div>
        </PermissionGuard>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>