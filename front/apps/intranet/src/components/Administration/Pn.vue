<script setup>
import { onMounted, ref } from 'vue'
import { useSemestreStore } from '@stores'
import { useUsersStore, useDiplomeStore } from "@stores";
import { NodeService } from '@/service/NodeService';
import { SimpleSkeleton } from "@components";

const usersStore = useUsersStore();
const diplomeStore = useDiplomeStore();
const departementId = usersStore.departementDefaut.id;

const diplomes = ref([])
const selectedDiplome = ref(null)
const selectedPn = ref(null)

const isLoadingDiplomes = ref(false)
const isLoadingPn = ref(false)

const columns = ref([]);

const getDiplomes = async (departementId) => {
  await diplomeStore.getDiplomesActifsDepartement(departementId);
  diplomes.value = diplomeStore.diplomes;
}

onMounted(async () => {
  isLoadingDiplomes.value = true
  isLoadingPn.value = true
  try {
    await getDiplomes(departementId)
  } catch (error) {
    console.error('Erreur lors du chargement des diplomes:', error);
  } finally {
    isLoadingDiplomes.value = false
  }

  if (selectedDiplome.value === null) {
    selectedDiplome.value = diplomes.value[0]
  }

  if (selectedDiplome.value) {
    selectedPn.value = selectedDiplome.value.structurePns.find(pn => pn.structureAnneeUniversitaires.some(annee => annee.actif === true))
    console.log(selectedDiplome.value)
    columns.value = [
        { field: 'libelle', header: 'Année', expander: true },
    ]
    isLoadingPn.value = false;
  }

  if (selectedPn.value) {
    NodeService.getTreeTableNodes().then((data) => (selectedDiplome.value.annees = data));
  }
})

const changeDiplome = (diplome) => {
  selectedDiplome.value = diplome
  // sélectionner le PN de l'année universitaire en cours
  selectedPn.value = diplome.structurePns.find(pn => pn.structureAnneeUniversitaires.some(annee => annee.actif === true))
  console.log(selectedPn.value?.structureAnnees)
}

</script>

<template>
  <div class="card">
    <SimpleSkeleton v-if="isLoadingDiplomes" class="w-1/2" />
    <Tabs v-else :value="selectedDiplome ? selectedDiplome.id : diplomes[0]?.id" scrollable>
      <TabList>
        <Tab v-for="diplome in diplomes" :key="diplome.libelle" :value="diplome.id" @click="changeDiplome(diplome)">
          <span>{{ diplome.sigle }}</span>
        </Tab>
      </TabList>
    </Tabs>

    <div class="flex justify-between gap-10 mt-6">
      <Select v-if="selectedDiplome" v-model="selectedPn"
              :options="selectedDiplome.structurePns"
              optionLabel="libelle"
              placeholder="Selectionner un PN"
              class="w-full md:w-56"/>

      <Button label="Créer un nouveau pn" icon="pi pi-plus" />
    </div>

    {{selectedPn?.structureAnnees}}

    <SimpleSkeleton v-if="isLoadingPn" class="w-1/2" />
    <TreeTable v-else-if="selectedPn?.structureAnnees" :value="selectedPn?.structureAnnees" tableStyle="min-width: 50rem">
      <Column v-for="col of columns" :key="col.field" :field="col.field" :header="col.header" :expander="col.expander"></Column>
    </TreeTable>
  </div>
</template>

<style scoped>
</style>
