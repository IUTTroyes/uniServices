<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const panelMenuItems = [
  { label: 'Enquêtes Etudiants', icon: 'pi pi-users', command: () => {router.push('/');} },
  { label: 'Enquêtes Personnels', icon: 'pi pi-users', command: () => {router.push('/');} },
  { label: 'Enquêtes Nominatives', icon: 'pi pi-users', command: () => {router.push('/');} },
];
</script>

<template>
  <div>
    <div class="flex justify-between gap-10 h-full">
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-star bg-red-400 bg-opacity-20 rounded-full p-4 text-red-500"/>
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">Qualité</span>
              <em class="text-muted-color px-2">Enquêtes, ...</em>
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
