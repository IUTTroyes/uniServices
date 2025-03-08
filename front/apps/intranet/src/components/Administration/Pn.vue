<script setup>
import { onMounted, ref } from 'vue'
import { useSemestreStore, useUsersStore, useDiplomeStore } from '@stores'
import { SimpleSkeleton } from "@components";
import { NodeService } from '@/service/NodeService';

const usersStore = useUsersStore();
const diplomeStore = useDiplomeStore();
const departementId = ref(null);

const diplomes = ref([])
const selectedDiplome = ref(null)
const selectedPn = ref(null)
const isLoadingDiplomes = ref(true)
const isLoadingPn = ref(true)

const nodes = ref([]);
const columns = ref([
  { field: 'libelle', header: 'Libellé', expander: true },
]);

onMounted(async () => {
  if (usersStore.departementDefaut) {
    departementId.value = usersStore.departementDefaut.id;
    await getDiplomes(departementId.value);
  } else {
    console.error('departementDefaut is not defined');
  }

  if (selectedDiplome.value) {
    selectedPn.value = selectedDiplome.value.structurePns.find(pn => pn.structureAnneeUniversitaires.some(annee => annee.actif === true))
  }

  if (selectedPn.value) {
    console.log(selectedPn.value)
    nodes.value = transformData(selectedPn.value.structureAnnees);

    console.log('nodes', nodes.value)
    isLoadingPn.value = false;
  }
})

const getDiplomes = async (departementId) => {
  try {
    isLoadingDiplomes.value = true
    await diplomeStore.getDiplomesActifsDepartement(departementId);
    diplomes.value = diplomeStore.diplomes;
  } catch (error) {
    console.error('Erreur lors du chargement des diplomes:', error);
  } finally {
    if (diplomes.value.length > 0 && !selectedDiplome.value) {
      selectedDiplome.value = diplomes.value[0]
      selectedPn.value = selectedDiplome.value.structurePns.find(pn => pn.structureAnneeUniversitaires.some(annee => annee.actif))
    }
    isLoadingDiplomes.value = false
  }
}

const changeDiplome = (diplome) => {
  selectedDiplome.value = diplome
  selectedPn.value = diplome.structurePns.find(pn => pn.structureAnneeUniversitaires.some(annee => annee.actif))
}

// Fonction pour transformer les données
const transformData = (data) => {
  return data.map(item => ({
    key: item['@id'],
    data: { libelle: item.libelle },
    children: item.structureSemestres.map(semestre => ({
      key: semestre['@id'],
      data: { libelle: semestre.libelle }
    }))
  }));
};

</script>

<template>
  <div class="card">
    <SimpleSkeleton v-if="isLoadingDiplomes" class="w-1/2" />
    <div v-else>
      <Tabs :value="selectedDiplome?.id || diplomes[0]?.id" scrollable>
        <TabList>
          <Tab v-for="diplome in diplomes" :key="diplome.libelle" :value="diplome.id" @click="changeDiplome(diplome)">
            <span>{{ diplome.sigle }}</span>
          </Tab>
        </TabList>
      </Tabs>
    </div>

    <div class="flex justify-between gap-10 mt-6">
      <Select v-if="selectedDiplome" v-model="selectedPn"
              :options="selectedDiplome.structurePns"
              optionLabel="libelle"
              placeholder="Selectionner un PN"
              class="w-full md:w-56"/>

      <Button label="Créer un nouveau pn" icon="pi pi-plus" />
    </div>

    <TreeTable :value="nodes" tableStyle="min-width: 50rem">
      <Column v-for="col in columns" :key="col.field" :field="col.field" :header="col.header" :expander="col.expander"></Column>
    </TreeTable>
  </div>
</template>

<style scoped>
</style>
