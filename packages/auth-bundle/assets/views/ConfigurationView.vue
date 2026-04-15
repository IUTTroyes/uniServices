<script setup>
import {ErrorView} from "@components";

const panelMenuEtablissementItems = [
  { label: 'Informations générales', icon: 'pi pi-info-circle', route: '/configuration/etablissement' },
  { label: 'Coordonnées', icon: 'pi pi-map-marker', route: '/configuration/etablissement/coordonnees' },
  { label: 'Contacts', icon: 'pi pi-phone', route: '/configuration/etablissement/contacts' },
]

const panelMenuUserItems = [
  { label: 'Gestion des accès', icon: 'pi pi-users', route: '/configuration/gestion-acces' },
]

const panelMenuItems = [
  { label: 'Types de diplômes', icon: 'pi pi-list', route: '/configuration/type-diplome' },
  { label: 'Années Universitaires', icon: 'pi pi-clock', route: '/configuration/annee-universitaire' },
]

</script>

<template>
  <ErrorView v-if="hasError"/>
  <div v-else class="h-full">

    <div class="card">
      <div class="card-body flex flex-col gap-10">
        <Fieldset class="w-full">
          <template #legend>
            <div class="flex items-center pl-2">
              <i class="pi pi-building bg-primary-400/20 rounded-full p-4 text-primary-500"/>
              <div class="flex flex-col">
                <span class="font-bold px-2 capitalize">L'établissement</span>
                <em class="text-muted-color px-2">Gérer les données de l'établissement</em>
              </div>
            </div>
          </template>
          <div class="mt-4">
            <PanelMenu :model="panelMenuEtablissementItems" multiple>
              <template #item="{ item }">
                <router-link v-if="item.route" v-slot="{ href, navigate }" :to="item.route" custom>
                  <a v-ripple
                     class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2"
                     :href="href" @click="navigate">
                    <span :class="item.icon"/>
                    <span class="ml-2">{{ item.label }}</span>
                  </a>
                </router-link>
                <a v-else v-ripple
                   class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2"
                   :href="item.url" :target="item.target">
                  <span :class="item.icon"/>
                  <span class="ml-2">{{ item.label }}</span>
                  <span v-if="item.items" class="pi pi-angle-down text-primary ml-auto"/>
                </a>
              </template>
            </PanelMenu>
          </div>
        </Fieldset>

        <div class="flex flex-row justify-between gap-10">
          <Fieldset class="w-full">
            <template #legend>
              <div class="flex items-center pl-2">
                <i class="pi pi-users bg-purple-400/20 rounded-full p-4 text-purple-500"/>
                <div class="flex flex-col">
                  <span class="font-bold px-2 capitalize">Gérer les accès</span>
                  <em class="text-muted-color px-2">Gérer les utilisateurs et les applications autorisées</em>
                </div>
              </div>
            </template>
            <div class="mt-4">
              <PanelMenu :model="panelMenuUserItems" multiple>
                <template #item="{ item }">
                  <router-link v-if="item.route" v-slot="{ href, navigate }" :to="item.route" custom>
                    <a v-ripple
                       class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2"
                       :href="href" @click="navigate">
                      <span :class="item.icon"/>
                      <span class="ml-2">{{ item.label }}</span>
                    </a>
                  </router-link>
                  <a v-else v-ripple
                     class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2"
                     :href="item.url" :target="item.target">
                    <span :class="item.icon"/>
                    <span class="ml-2">{{ item.label }}</span>
                    <span v-if="item.items" class="pi pi-angle-down text-primary ml-auto"/>
                  </a>
                </template>
              </PanelMenu>
            </div>
          </Fieldset>

          <Fieldset class="w-full">
            <template #legend>
              <div class="flex items-center pl-2">
                <i class="pi pi-wrench bg-blue-400/20 rounded-full p-4 text-blue-500"/>
                <div class="flex flex-col">
                  <span class="font-bold px-2 capitalize">Configuration</span>
                  <em class="text-muted-color px-2">Configuration des données communes aux applications</em>
                </div>
              </div>
            </template>
            <div class="mt-4">
              <PanelMenu :model="panelMenuItems" multiple>
                <template #item="{ item }">
                  <router-link v-if="item.route" v-slot="{ href, navigate }" :to="item.route" custom>
                    <a v-ripple
                       class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2"
                       :href="href" @click="navigate">
                      <span :class="item.icon"/>
                      <span class="ml-2">{{ item.label }}</span>
                    </a>
                  </router-link>
                  <a v-else v-ripple
                     class="flex items-center cursor-pointer text-surface-700 dark:text-surface-0 px-4 py-2"
                     :href="item.url" :target="item.target">
                    <span :class="item.icon"/>
                    <span class="ml-2">{{ item.label }}</span>
                    <span v-if="item.items" class="pi pi-angle-down text-primary ml-auto"/>
                  </a>
                </template>
              </PanelMenu>

            </div>
          </Fieldset>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.app {
  cursor: pointer;
  transition: transform 0.2s;

  &:hover {
    transform: scale(1.02);
  }

  &.disabled {
    cursor: not-allowed;
    opacity: 0.5;

    &:hover {
      transform: none;
    }
  }
}
</style>
