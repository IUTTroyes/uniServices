<script setup>
import { onMounted, ref } from 'vue'
import { useSemestreStore, useUsersStore, useDiplomeStore } from '@stores'
import { SimpleSkeleton } from "@components";
import { NodeService } from '@/service/NodeService';
import FicheRessource from "../../components/Pn/FicheRessource.vue";
import FicheSae from "../../components/Pn/FicheSae.vue";
import FicheMatiere from "../../components/Pn/FicheMatiere.vue";

const usersStore = useUsersStore();
const diplomeStore = useDiplomeStore();
const departementId = ref(null);

const diplomes = ref([])
const selectedDiplome = ref(null)
const selectedPn = ref(null)
const isLoadingDiplomes = ref(true)
const isLoadingPn = ref(true)

const visibleDialog = ref(false);
const dialogContent = ref(null);

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

const showDetails = (type, item) => {
  if (item) {
    dialogContent.value = { type, item };
    visibleDialog.value = true;
    console.log(dialogContent.value);
  } else {
    console.error('Item is null or undefined');
  }
};
</script>

<template>
  <div class="card">
    <SimpleSkeleton v-if="isLoadingDiplomes" class="w-1/2" />
    <div v-else>
      <h2 class="text-2xl font-bold">Programmes pédagogiques nationaux</h2>
      <Divider/>
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

      <Button label="Synchronisation depuis ORéOF" icon="pi pi-refresh" />
    </div>

    <div class="mt-6">
      <div class="text-xl font-bold mb-4">{{selectedDiplome?.apcParcours?.display ?? `Pas de parcours`}}</div>
      <Fieldset v-if="selectedPn" v-for="annee in selectedPn?.structureAnnees" :legend="`${annee.libelle}`" :toggleable="true">
        <template #toggleicon>
          <i class="pi pi-angle-down"></i>
        </template>
        <div class="border-l-2 border-primary-500 pl-4">
          <div class="mb-1 text-lg">{{annee.libelleLong}}</div>
          <div class="text-muted-color mb-4">Responsable du diplome : {{selectedDiplome?.responsableDiplome?.display ?? `Pas de responsable`}}</div>
          <div class="my-6 flex flex-row items-center gap-4">
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
          <div v-for="semestre in annee.structureSemestres" class="ml-6 border-l-2 border-primary-300 pl-4">
            <div class="mt-6 mb-2 flex flex-row items-center gap-4">
              <table class="text-lg">
                <thead>
                <tr class="border-b">
                  <th class="px-2 font-normal text-muted-color text-start">Semestre</th>
                  <th class="px-2 font-normal text-muted-color text-start">Code élément</th>
                  <th class="px-2 font-normal text-muted-color text-start">Nbr. d'UEs</th>

                </tr>
                </thead>
                <tbody>
                <tr>
                  <td class="px-2 font-bold">{{ semestre.libelle }}</td>
                  <td class="px-2 font-bold">{{ semestre.codeElement }}</td>
                  <td class="px-2 font-bold">{{ semestre.structureUes.length }}</td>
                </tr>
                </tbody>
              </table>
              <Button icon="pi pi-cog" rounded outlined severity="warn" @click=""/>
            </div>
            <div class="mb-4">
              <div>Nombre de groupes</div>
              <table class="text-lg">
                <thead>
                <tr class="border-b">
                  <th class="px-2 font-normal text-muted-color text-start">CM</th>
                  <th class="px-2 font-normal text-muted-color text-start">TD</th>
                  <th class="px-2 font-normal text-muted-color text-start">TP</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                  <td class="px-2 font-bold">{{ semestre.nbGroupesCm }}</td>
                  <td class="px-2 font-bold">{{ semestre.nbGroupesTd }}</td>
                  <td class="px-2 font-bold">{{ semestre.nbGroupesTp }}</td>
                </tr>
                </tbody>
              </table>
            </div>
            <Fieldset v-for="ue in semestre.structureUes" :toggleable="true" :legend="`${ue.numero} . ${ue.displayApc}`" class="ml-6 !border-l-2 !border-l-primary-200 !pl-4 !border-0" :collapsed="true">
              <template #toggleicon>
                <i class="pi pi-angle-down"></i>
              </template>
              <div class="my-6 flex flex-row items-center gap-4">
                <table class="text-lg">
                  <thead>
                  <tr class="border-b">
                    <th class="px-2 font-normal text-muted-color text-start">UE</th>
                    <th class="px-2 font-normal text-muted-color text-start">Code élément</th>
                    <th v-if="selectedDiplome.typeDiplome.apc" class="px-2 font-normal text-muted-color text-start">Compétence Apc</th>
                    <th class="px-2 font-normal text-muted-color text-start">Nb. ECTS</th>
                    <th v-if="!selectedDiplome.typeDiplome.apc" class="px-2 font-normal text-muted-color text-start">Coeff</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td class="px-2 font-bold">{{ue.libelle}}</td>
                    <td class="px-2 font-bold">{{ ue.codeElement }}</td>
                    <td v-if="selectedDiplome.typeDiplome.apc" :class="ue.apcCompetence.couleur" class="px-2 font-bold !w-fit">{{ue.apcCompetence.nomCourt}}</td>
                    <td class="px-2 font-bold !w-fit">{{ue.nbEcts}}</td>
                    <td v-if="!selectedDiplome.typeDiplome.apc" class="px-2 font-bold !w-fit">0</td>
                  </tr>
                  </tbody>
                </table>
                <Button icon="pi pi-cog" rounded outlined severity="warn" @click=""/>
              </div>
              <Fieldset v-for="enseignementUe in ue.scolEnseignementUes" :legend="`${enseignementUe.enseignement.libelle}`" :toggleable="true">
                <template #toggleicon>
                  <i class="pi pi-angle-down"></i>
                </template>
                <div class="my-6 flex flex-row items-center gap-4">
                  <table class="text-lg">
                    <thead>
                    <tr class="border-b">
                      <th class="px-2 font-normal text-muted-color text-start">Code {{enseignementUe.enseignement.type}}</th>
                      <th class="px-2 font-normal text-muted-color text-start">Enseignement</th>
                      <th class="px-2 font-normal text-muted-color text-start">Code apogée</th>
                      <th class="px-2 font-normal text-muted-color text-start">Type</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                      <td class="px-2 font-bold">{{ enseignementUe.enseignement.codeEnseignement }}</td>
                      <td class="px-2 font-bold">{{ enseignementUe.enseignement.libelle }}</td>
                      <td class="px-2 font-bold">{{ enseignementUe.enseignement.codeApogee }}</td>
                      <td class="px-2 font-bold">
                        <Tag v-if="enseignementUe.enseignement.type === 'sae'" severity="success">{{ enseignementUe.enseignement.type }}</Tag>
                        <Tag v-else severity="info">{{ enseignementUe.enseignement.type }}</Tag>
                      </td>
                    </tr>
                    </tbody>
                  </table>
                  <Button icon="pi pi-info-circle" rounded outlined severity="info" @click="showDetails('enseignement', enseignementUe.enseignement)"/>
                  <Button icon="pi pi-cog" rounded outlined severity="warn" @click=""/>
                </div>
              </Fieldset>
            </Fieldset>
          </div>
        </div>
      </Fieldset>
    </div>
  </div>

  <Dialog v-model:visible="visibleDialog" modal :header="`Détails ${dialogContent?.type} ${dialogContent?.item.libelle}`" :style="{ width: '50vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" dismissable-mask>
    <template v-if="dialogContent">
      <div v-if="dialogContent.type === 'enseignement'" class="m-0">
        <FicheRessource v-if="dialogContent.item.type === 'ressource'" :enseignement="dialogContent.item"/>
        <FicheSae v-else-if="dialogContent.item.type === 'sae'" :enseignement="dialogContent.item"/>
        <FicheMatiere v-else-if="dialogContent.item.type === 'matiere'" :enseignement="dialogContent.item"/>
      </div>
    </template>
  </Dialog>
</template>

<style scoped>

</style>
