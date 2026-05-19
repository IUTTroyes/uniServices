<script setup>

import TicketCard from "@/components/TicketCard.vue";
import { useUsersStore } from "@stores";
import {computed, ref} from "vue";
import {tickets} from '@/mocks/messages.js';
import { useRouter } from 'vue-router';
import {PermissionGuard} from "@components";
import {TabGroup, TabPanels} from "@headlessui/vue";

const router = useRouter();
const userStore = useUsersStore();
const date = new Date();

const goToTicket = (id) => {
  router.push({ name: 'TicketView', params: { id: id } });
};

const initiales = computed(
    () => `${userStore.user?.prenom?.charAt(0) || ""}${userStore.user?.nom?.charAt(0) || ""}`
);
</script>

<template>
<div class="card">
  <Panel
      header="Filtrer par:"
      :pt="{
        root: '!border !border-violet-400 overflow-hidden',
        header: '!bg-violet-100 dark:!bg-violet-500  !border-none',
        content: 'bg-violet-100 dark:bg-violet-500'
    }"
  >
      <div class="flex justify-center w-full py-4">

      <div class="flex flex-row gap-20 items-end">

        <div class="flex flex-col">
          <label for="creation" class="mb-1 text-sm font-medium">Date de création</label>
          <Select id="creation" v-model="selectedCreated" :options="dates"  optionLabel="name" class="w-48" />
        </div>
        <div class="flex flex-col">
          <label for="Service" class="mb-1 text-sm font-medium">Service</label>
          <Select id="Service" v-model="selectedService" :options="services" optionLabel="name" class="w-48" />
        </div>

        <PermissionGuard permission="isPersonnel">
          <div class="flex flex-col">
            <label for="category" class="mb-1 text-sm font-medium">Catégorie</label>
            <Select id="category" v-model="selectedCategory" :options="categories" optionLabel="name" class="w-48" />
          </div>
        </PermissionGuard>

        <div class="flex flex-col">
          <label for="statut" class="mb-1 text-sm font-medium">Statut</label>
          <Select id="statut" v-model="selectedStatut" :options="statut" optionLabel="name" class="w-48" />
        </div>
        <PermsissionGuard permission="isPersonnel">
          <div class="flex flex-col">
            <label for="assigne" class="mb-1 text-sm font-medium">Assignés</label>
            <Select id="assigne" v-model="selectedAssigne" :options="assigne" optionLabel="name" class="w-48" />
          </div>
        </PermsissionGuard>
      </div>
    </div>
  </Panel>
  <Tabs value="0" class="mt-10">
    <TabList class="mb-10">
      <Tab value="0">Tickets Postés</Tab>
      <PermissionGuard permission="isPersonnel">
        <Tab value="1">Tickets Reçus</Tab>
      </PermissionGuard>
    </TabList>
    <TabGroup>
      <TabPanels>

        <TabPanel value="0">

          <div>
            <Toolbar style="border:none">
              <template #start>
                <div class="font-semibold text-xl">Tickets Postés</div>
              </template>
              <template #end>
                <IconField>
                  <InputIcon>
                    <i class="pi pi-search" />
                  </InputIcon>
                  <InputText placeholder="Search" />
                </IconField>
              </template>
            </Toolbar>
          </div>

          <div class="p-6">
            <div v-for="ticket in tickets" :key="ticket.id">
              <TicketCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer hover:shadow-md transition-shadow"/>
            </div>
          </div>
        </TabPanel>
        <TabPanel value="1">
          <div >

            <div>
              <Toolbar style="border:none">
                <template #start>
                  <div class="font-semibold text-xl">Tickets Reçus</div>
                </template>
                <template #end>
                  <IconField>
                    <InputIcon>
                      <i class="pi pi-search" />
                    </InputIcon>
                    <InputText placeholder="Search" />
                  </IconField>
                </template>
              </Toolbar>
            </div>

          </div>
          <div class="p-6">
            <div v-for="ticket in tickets" :key="ticket.id">
              <TicketCard :ticket="ticket" @click="goToTicket(ticket.id)" class="cursor-pointer hover:shadow-md transition-shadow"/>
            </div>
          </div>
        </TabPanel>
      </TabPanels>
    </TabGroup>
  </Tabs>
</div>
</template>

<style scoped>
.bg-primary-light {
  background-color: var(--p-tag-primary-background);
  border: 1px solid var(--p-tag-primary-background);
  color: var(--primary-color);
}
</style>