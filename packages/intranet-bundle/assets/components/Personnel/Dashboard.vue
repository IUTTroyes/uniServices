<script setup>
import {computed, onMounted, ref} from 'vue';
import {useRouter} from 'vue-router';
import {useUsersStore, useAnneeUnivStore} from '@stores';
import {} from '@requests';

const router = useRouter();
const userStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();
const selectedAnneeUniversitaireId = computed(() => anneeUnivStore.selectedAnneeUniv?.id ?? null);
const widgets = ref([]);


const structureDepartementPersonnelId = computed(() => userStore.departementDefaut?.departementPersonnel?.id || null);

const getDashboardWidgets = async () => {
}


onMounted(async () => {
  await loadDashboard();
});
</script>

<template>
  <div class="flex flex-col gap-4">
    <div class="grid grid-cols-12 gap-4">
     
    </div>
    <div class="card flex justify-between items-center">
      <div>
        <div class="text-xl font-semibold">Mon dashboard</div>
        <div class="text-sm text-color-secondary">Personnalisez vos widgets en fonction de votre structure.</div>
      </div>
      <button class="border-none rounded-lg bg-primary px-4 py-2 text-white" type="button" @click="router.push({name: 'DashboardWidgetsConfig'})">
        Configurer les widgets
      </button>
    </div>
  </div>
</template>
