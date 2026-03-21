<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const panelMenuItems = [
  { label: 'Programmes pédagogiques nationaux', icon: 'pi pi-book', command: () => {router.push('/administration/pn');} },
  { label: 'Référentiels de compétences', icon: 'pi pi-list', command: () => {router.push('/administration/referentiels-competences');} },
  { label: 'Gestion des plan de cours', icon: 'pi pi-file', command: () => {} },
];
</script>

<template>
  <div>
    <div class="flex justify-between gap-10 h-full">
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-users bg-orange-400 bg-opacity-20 rounded-full p-4 text-orange-500"/>
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">PPN</span>
              <em class="text-muted-color px-2">Programmes pédagogiques, matières, plans de cours</em>
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
