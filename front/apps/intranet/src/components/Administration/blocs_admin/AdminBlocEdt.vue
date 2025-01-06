<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const panelMenuItems = [
  { label: 'Gestion de l\'emploi du temps', icon: 'pi pi-calendar', command: () => {} },
  { label: 'Gestion des événements et actualités', icon: 'pi pi-megaphone', command: () => {router.push('/administration/pn');} },
  { label: 'Passerelle EduSign', icon: 'pi pi-qrcode', command: () => {} },
];
</script>

<template>
  <div>
    <div class="flex justify-between gap-10 h-full">
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-users bg-lime-400 bg-opacity-20 rounded-full p-4 text-lime-500"/>
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">Emploi du temps</span>
              <em class="text-muted-color px-2">Cours, événements, edusign</em>
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
