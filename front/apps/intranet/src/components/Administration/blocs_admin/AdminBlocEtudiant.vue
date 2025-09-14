<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const panelMenuItems = [
  { label: 'Liste de tous les étudiants', icon: 'pi pi-list', route: '/administration/etudiant/' },
  { label: 'Ajouter des étudiants', icon: 'pi pi-plus-circle', route: '/administration/etudiant/ajout/' },
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
