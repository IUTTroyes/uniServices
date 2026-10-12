<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const panelMenuItems = [
  { label: 'Créer un type d\'heures', icon: 'pi pi-plus', command: () => { router.push('/intranet/super-administration/type-hrs'); } },
  { label: 'Liste des types d\'heures', icon: 'pi pi-clock', command: () => { router.push('/intranet/super-administration/types-hrs'); } },
];
</script>

<template>
  <div>
    <div class="flex justify-between gap-10 h-full">
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-clock bg-orange-400/20 rounded-full p-4 text-orange-500" />
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">Types d'heures (HRS)</span>
              <em class="text-muted-color px-2">Gestion des référentiels d'heures et plafonds</em>
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
