<script setup>

const panelMenuItems = [
  { label: 'Liste de tous les étudiants', icon: 'pi pi-list', command: () => {} },
  { label: 'Ajouter des étudiants', icon: 'pi pi-plus-circle', command: () => {} },
  { label: 'Gestion des cohortes', icon: 'pi pi-users', command: () => {} },
  { label: 'Gestion des absences', icon: 'pi pi-calendar', command: () => {} },
  { label: 'Gestion des notes et évaluations', icon: 'pi pi-book', command: () => {} },
];
</script>

<template>
  <div>
    <div class="flex justify-between gap-10 h-full">
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-users bg-primary-400 bg-opacity-20 rounded-full p-4 text-primary-500"/>
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">etudiants</span>
              <em class="text-muted-color px-2">Infos, absences, notes... de l'ensemble du département</em>
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
