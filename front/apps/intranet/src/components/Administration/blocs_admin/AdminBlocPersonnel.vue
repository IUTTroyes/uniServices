<script setup>
const panelMenuItems = [
  { label: 'Liste des personnels du département', icon: 'pi pi-list', route: '/administration/personnels/' },
  { label: 'Prévisionnels', icon: 'pi pi-clock', route: '/administration/previsionnel/' },
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
          <PanelMenu :model="panelMenuItems" multiple>
            <template #item="{ item }">
              <router-link v-if="item.route" v-slot="{ href, navigate }" :to="item.route" custom>
                <a v-ripple class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2" :href="href" @click="navigate">
                  <span :class="item.icon" />
                  <span class="ml-2">{{ item.label }}</span>
                </a>
              </router-link>
              <a v-else v-ripple class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2" :href="item.url" :target="item.target">
                <span :class="item.icon" />
                <span class="ml-2">{{ item.label }}</span>
                <span v-if="item.items" class="pi pi-angle-down text-primary ml-auto" />
              </a>
            </template>
          </PanelMenu>
        </div>
      </Fieldset>
    </div>
  </div>
</template>

<style scoped>

</style>
