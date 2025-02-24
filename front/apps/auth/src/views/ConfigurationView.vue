<script setup>
import { TopbarComponent } from '@components'
import { useUsersStore } from '@stores'
import { tools } from '@config/uniServices.js'
import Logo from '@components/components/Logo.vue'
import { onMounted } from 'vue'

const panelMenuUserItems = [
  { label: 'Gestion des accès', icon: 'pi pi-users', route: '/configuration/gestion-access' },
]

const panelMenuItems = [
  { label: 'Types de diplômes', icon: 'pi pi-list', route: '/configuration/type-diplome' },
  { label: 'Années Universitaires', icon: 'pi pi-clock', route: '/configuration/annee-universitaire' },
]

</script>

<template>
  <div class="row">
    <div class="col-6">
      <div class="card mt-10 me-5">
        <div class="card-body flex flex-col gap-10">
          <div class="flex justify-between gap-10 h-full">
            <Fieldset class="w-full h-full">
              <template #legend>
                <div class="flex items-center pl-2">
                  <i class="pi pi-wrench bg-primary-400 bg-opacity-20 rounded-full p-4 text-primary-500"/>
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
          </div>
        </div>
      </div>
    </div>
    <div class="col-6">
      <div class="card mt-10 ms-5">
        <div class="card-body flex flex-col gap-10">
          <div class="flex justify-between gap-10 h-full">
            <Fieldset class="w-full h-full">
              <template #legend>
                <div class="flex items-center pl-2">
                  <i class="pi pi-wrench bg-primary-400 bg-opacity-20 rounded-full p-4 text-primary-500"/>
                  <div class="flex flex-col">
                    <span class="font-bold px-2 capitalize">Configuration</span>
                    <em class="text-muted-color px-2">Configuration des données commune s aux applications</em>
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
