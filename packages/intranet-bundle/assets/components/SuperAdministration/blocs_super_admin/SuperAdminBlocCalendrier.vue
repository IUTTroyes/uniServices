<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const panelMenuItems = [
  { label: 'Créer une semaine', icon: 'pi pi-plus', command: () => { router.push('/intranet/super-administration/calendrier'); } },
  { label: 'Gestion du calendrier', icon: 'pi pi-calendar', command: () => { router.push('/intranet/super-administration/calendriers'); } },
];
</script>

<template>
  <div>
    <div class="flex justify-between gap-10 h-full">
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-calendar bg-purple-400/20 rounded-full p-4 text-purple-500" />
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">Calendrier</span>
              <em class="text-muted-color px-2">Gestion des semaines universitaires</em>
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
