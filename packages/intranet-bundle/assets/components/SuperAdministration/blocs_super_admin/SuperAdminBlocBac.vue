<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const panelMenuItems = [
  { label: 'Créer un type de bac', icon: 'pi pi-plus', command: () => { router.push('/intranet/super-administration/bac'); } },
  { label: 'Liste des types de bacs', icon: 'pi pi-list', command: () => { router.push('/intranet/super-administration/bacs'); } },
];
</script>

<template>
  <div>
    <div class="flex justify-between gap-10 h-full">
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-id-card bg-amber-400/20 rounded-full p-4 text-amber-500" />
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">Types de bacs</span>
              <em class="text-muted-color px-2">Gestion des séries et filières de baccalauréat</em>
            </div>
          </div>
        </template>
        <div class="mt-4">
          <PanelMenu :model="panelMenuItems" multiple />
        </div>
      </Fieldset>
    </div>
  </div>
</template>

<style scoped>
</style>
