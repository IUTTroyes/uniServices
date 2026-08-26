<script setup>
import {ref} from 'vue';
import {PermissionGuard} from '@components';

const newActu = ref(false);
const showActuDialog = ref(false);

defineProps({
  data: {
    type: Object,
    default: () => ({items: []}),
  },
});
</script>

<template>
  <div class="flex flex-col justify-between gap-4">
    <Timeline v-if="data.items.length > 0" :value="data.items" align="left" class="w-full">
      <template #content="slotProps">
        <div class="text-sm leading-4">
          {{ slotProps.item.title }}
        </div>
      </template>
    </Timeline>
    <!--  todo: styliser un peu cette info  -->
    <Message v-else severity="info" icon="pi pi-info-circle">
      Aucune actualité disponible.
    </Message>

    <PermissionGuard :permissions="['ROLE_ADMIN']">
      <Button size="small" severity="primary" label="Gérer les actus" icon="pi pi-pencil" @click="showActuDialog = true"/>
    </PermissionGuard>
  </div>

  <Dialog
      header="Actualités du département"
      :visible="showActuDialog"
      modal
      dismissable-mask
      :style="{ width: '90vw' }"
      :breakpoints="{ '1199px': '85vw', '575px': '95vw' }"
      @update:visible="showActuDialog = $event"
  >
    <DataTable
        :value="data.items"
        :paginator="true"
        :rows="5"
        striped-rows
        removableSort
        sortMode="multiple"
        lazy
        :rows-per-page-options="[5, 10, 20]"
        responsive-layout="scroll"
        class="w-full mb-6"
    >
      <Column field="title" header="Titre" sortable></Column>
      <Column field="pubDate" header="Date de publication" sortable></Column>
      <Column field="description" header="Description" sortable></Column>
      <Column field="public" header="Public" sortable></Column>
      <Column field="actif" header="Actif" sortable></Column>

      <template #empty>
        <Message severity="info" icon="pi pi-info-circle">
          Aucune actualité disponible.
        </Message>
      </template>
    </DataTable>

    <Divider></Divider>

    <Button size="small" severity="primary" label="Créer une actus" icon="pi pi-plus" @click="newActu = true"/>

    <div v-if="newActu">
      <form>
        <div class="mb-3">
          <label for="title" class="form-label">Titre</label>
          <InputText id="title" v-model="newActu.title" class="w-full"/>
        </div>

        <Button size="small" severity="primary" label="Enregistrer" @click="newActu = false"/>
        <Button size="small" severity="secondary" label="Annuler" @click="newActu = false"/>
      </form>
    </div>
  </Dialog>
</template>

<style scoped>
</style>
