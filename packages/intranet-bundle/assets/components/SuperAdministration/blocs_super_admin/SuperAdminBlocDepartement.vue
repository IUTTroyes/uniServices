<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const panelMenuItems = [
  { label: 'Créer un département', icon: 'pi pi-plus', command: () => {router.push('/super-administration/departement')} },
  { label: 'Liste des départements', icon: 'pi pi-list', command: () => {router.push('/super-administration/departements')} },
];
</script>

<template>
  <div>
    <div class="flex justify-between gap-10 h-full">
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-flag bg-lime-400 bg-opacity-20 rounded-full p-4 text-lime-500"/>
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">Départements</span>
              <em class="text-muted-color px-2">Gestion globale des départements</em>
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
