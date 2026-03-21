<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const panelMenuItems = [
  { label: 'Nouvelle année universitaire', icon: 'pi pi-plus', command: () => {router.push('/super-administration/annee-universitaire')} },
  { label: 'Liste des années universitaires', icon: 'pi pi-list', command: () => {router.push('/super-administration/annees-universitaires');} },
];
</script>

<template>
  <div>
    <div class="flex justify-between gap-10 h-full">
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-folder bg-primary-400 bg-opacity-20 rounded-full p-4 text-primary-500"/>
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">Année Universitaire</span>
              <em class="text-muted-color px-2">nouvelle année et gestion des existantes</em>
            </div>
          </div>
        </template>
        <div class="mt-4">
          <PanelMenu :model="panelMenuItems" multiple/>
        </div>
      </Fieldset>
    </div>
  </div>
</template>

<style scoped>

</style>
