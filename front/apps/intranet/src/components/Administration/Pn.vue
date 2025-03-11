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

  nodes.value = transformData(selectedPn.value.structureAnnees);
}

const nodes = ref([]);
const columns = ref([
  { field: 'libelle', header: 'Libellé', expander: true },
  { field: 'apogeeCode', header: 'Code Apogée', expander: false },
]);

// Fonction pour transformer les données
const transformData = (data) => {
  return data.map(annee => ({
    key: annee['@id'],
    data: { libelle: annee.libelle, apogeeCode: annee.apogeeCodeEtape },
    edit: false,
    children: annee.structureSemestres.map(semestre => ({
      key: semestre['@id'],
      data: { libelle: semestre.libelle, apogeeCode: semestre.codeElement },
      edit: true,
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
            <span>{{ diplome.typeDiplome.sigle }}</span> | <span>{{ diplome.sigle }}</span>
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

    <!--    <TreeTable :value="nodes" tableStyle="min-width: 50rem">-->
    <!--      <Column v-for="col in columns" :key="col.field" :field="col.field" :header="col.header" :expander="col.expander"></Column>-->
    <!--    </TreeTable>-->


    <div class="mt-6">

      <div class="text-xl font-bold">{{selectedDiplome?.apcParcours?.display ?? `Pas de parcours`}}</div>
      <Fieldset v-if="selectedPn" v-for="annee in selectedPn?.structureAnnees" :legend="`${annee.libelle}`" :toggleable="true">
        <div class="border-l-2 border-primary-500 pl-4">
          <div class="my-6 flex flex-row gap-4">
            <table class="text-lg">
              <thead>
              <tr class="border-b">
                <th class="px-2 font-normal text-muted-color text-start">Année</th>
                <th class="px-2 font-normal text-muted-color text-start">Code étape</th>
                <th class="px-2 font-normal text-muted-color text-start">Code version</th>
              </tr>
              </thead>
              <tbody>
              <tr>
                <td class="px-2 font-bold">{{ annee.libelle }}</td>
                <td class="px-2 font-bold">{{ annee.apogeeCodeEtape }}</td>
                <td class="px-2 font-bold">{{ annee.apogeeCodeVersion }}</td>
              </tr>
              </tbody>
            </table>
          </div>
          <div v-for="semestre in annee.structureSemestres" class="ml-12 border-l-2 border-primary-500 pl-4">
            <div class="my-6 flex flex-row gap-4">
              <table class="text-lg">
                <thead>
                <tr class="border-b">
                  <th class="px-2 font-normal text-muted-color text-start">Semestre</th>
                  <th class="px-2 font-normal text-muted-color text-start">Code élément</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                  <td class="px-2 font-bold">{{ semestre.libelle }}</td>
                  <td class="px-2 font-bold">{{ semestre.codeElement }}</td>
                </tr>
                </tbody>
              </table>
            </div>
            <div v-for="ue in semestre.structureUes" class="ml-12 border-l-2 border-primary-500 pl-4">
              <div class="my-6 flex flex-row gap-4">
                <table class="text-lg">
                  <thead>
                  <tr class="border-b">
                    <th class="px-2 font-normal text-muted-color text-start">UE</th>
                    <th class="px-2 font-normal text-muted-color text-start">Code élément</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td class="px-2 font-bold">{{ue.displayApc}}</td>
                    <td class="px-2 font-bold">{{ ue.codeElement }}</td>
                  </tr>
                  </tbody>
                </table>
              </div>
              <Fieldset v-for="enseignementUe in ue.scolEnseignementUes" :legend="`${enseignementUe.enseignement.libelle}`" :toggleable="true">

              </Fieldset>
            </div>
          </div>
        </div>
      </Fieldset>


      <!--      <TreeTable :value="nodes" tableStyle="min-width: 50rem">-->
      <!--        <template #header>-->
      <!--          <div class="text-xl font-bold">{{selectedDiplome?.apcParcours?.display ?? `Pas de parcours`}}</div>-->
      <!--        </template>-->
      <!--        <Column v-for="col in columns" :key="col.field" :field="col.field" :header="col.header" :expander="col.expander"></Column>-->
      <!--        <Column style="width: 2rem">-->
      <!--          <template #body="slotProps">-->
      <!--            <div v-if="slotProps.node.edit" class="flex flex-wrap gap-2">-->
      <!--              <Button type="button" icon="pi pi-pencil" rounded outlined severity="warn" @click="handleEditClick(slotProps.node.key)" />-->
      <!--            </div>-->
      <!--          </template>-->
      <!--        </Column>-->
      <!--              <template #footer>-->
      <!--                <div class="flex justify-start">-->
      <!--                  <Button icon="pi pi-refresh" label="Reload" severity="warn" />-->
      <!--                </div>-->
      <!--              </template>-->
      <!--      </TreeTable>-->
    </div>
  </div>
</template>

<style scoped>

</style>
