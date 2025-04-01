<script setup>
import { useRouter } from 'vue-router';
import { onMounted, ref } from 'vue'
import { getPeriodeStageSemestreService } from '@requests/stage_services/stagePeriodeService.js'

const router = useRouter();
const periodes = ref([]);
const panelMenuItems = ref([]);

onMounted(async () => {
  // récupérer les périodes de stage du semestre dans lequel je suis (étudiant)
  periodes.value = await getPeriodeStageSemestreService(1) //todo: gérer le semestre et la recherche dans programme et autorisé saisie
  // construire le panelMenuItems
  periodes.value.forEach(periode => {
    panelMenuItems.value.push({ label: periode.libelle, icon: 'pi pi-list', command: () => {router.push('applications/etudiants/periode-stage/'+periode.id)} })
  })
});


</script>

<template>
  <div>
    <div class="flex justify-between gap-10 h-full">
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-briefcase bg-orange-400 bg-opacity-20 rounded-full p-4 text-orange-500"/>
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">Stages</span>
              <em class="text-muted-color px-2">Gestion des périodes, des conventions, ...</em>
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
