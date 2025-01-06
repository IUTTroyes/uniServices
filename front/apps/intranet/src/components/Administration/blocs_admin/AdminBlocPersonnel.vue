<script setup>
const panelMenuItems = [
  { label: 'Liste des personnels du département', icon: 'pi pi-list', command: () => {} },
  { label: 'Prévisionnels', icon: 'pi pi-clock', command: () => {} },
  { label: 'HRS/Primes', icon: 'pi pi-money-bill', command: () => {} },
];
</script>

<template>
  <div>
    <div class="flex justify-between gap-10 h-full">
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-briefcase bg-blue-400 bg-opacity-20 rounded-full p-4 text-blue-500"/>
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">Personnels</span>
              <em class="text-muted-color px-2">Infos, prévisionnels, HRS/Primes</em>
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
