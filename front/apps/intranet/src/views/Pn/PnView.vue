<script setup>
import { onMounted, ref } from 'vue'
import { useSemestreStore, useUsersStore, useDiplomeStore } from '@stores'
import {ListSkeleton, SimpleSkeleton} from "@components";
import FicheRessource from "../../components/Pn/FicheRessource.vue";
import FicheSae from "../../components/Pn/FicheSae.vue";
import FicheMatiere from "../../components/Pn/FicheMatiere.vue";
import { getEnseignementService, getPnsDiplome } from "@requests";

const usersStore = useUsersStore();
const diplomeStore = useDiplomeStore();
const departementId = ref(null);

const diplomes = ref([])
const selectedDiplome = ref(null)
const pns = ref([])
const selectedPn = ref(null)
const selectedEnseignement = ref(null)
const isLoadingDiplomes = ref(true)
const isLoadingPn = ref(true)
const isLoadingEnseignement = ref(true)

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
    await getPnsForDiplome(selectedDiplome.value.id);
  }

  if (selectedPn.value) {
    nodes.value = transformData(selectedPn.value.structureAnnees);
    isLoadingPn.value = false;
  }
})

const getDiplomes = async (departementId) => {
  try {
    isLoadingDiplomes.value = true
    await diplomeStore.getDiplomesActifsDepartement(departementId);
    diplomes.value = diplomeStore.diplomes;
    if (diplomes.value.length > 0) {
      selectedDiplome.value = diplomes.value[0];
      await getPnsForDiplome(selectedDiplome.value.id);
    }
  } catch (error) {
    console.error('Erreur lors du chargement des diplomes:', error);
  } finally {
    isLoadingDiplomes.value = false
  }
}

const getPnsForDiplome = async (diplomeId) => {
  try {
    isLoadingPn.value = true;
    pns.value = await getPnsDiplome(diplomeId)
    // parmis tous les pn, on prend celui qui a une année active
    selectedPn.value = pns.value.find(pn => pn.structureAnneeUniversitaires.some(annee => annee.actif === true)) ?? null
    nodes.value = transformData(selectedPn.value.structureAnnees);
    if (selectedPn.value) {
      nodes.value = transformData(selectedPn.value.structureAnnees);
    }
  } catch (error) {
    console.error('Erreur lors du chargement des PNs:', error);
  } finally {
    isLoadingPn.value = false;
  }
}

const getEnseignement = async (enseignementId, semestre) => {
  try {
    isLoadingEnseignement.value = true;
    selectedEnseignement.value = await getEnseignementService(enseignementId)
    console.log(enseignementId)
    showDetails(selectedEnseignement.value, semestre)
  } catch (error) {
    console.error('Erreur lors du chargement de l\'enseignement:', error);
  } finally {
    isLoadingEnseignement.value = false;
  }
}

const changeDiplome = (diplome) => {
  selectedDiplome.value = diplome
  getPnsForDiplome(selectedDiplome.value.id);

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

const showDetails = (item, semestre) => {
  console.log(item);
  if (item) {
    dialogContent.value = { item, semestre };
    visibleDialog.value = true;
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

    <ListSkeleton v-if="isLoadingPn" class="mt-4"/>
    <div v-else class="mt-6">
      <div class="flex justify-between gap-10 my-6">
        <Select v-if="selectedDiplome" v-model="selectedPn"
                :options="pns"
                optionLabel="libelle"
                placeholder="Selectionner un PN"
                class="w-full md:w-56"/>

        <Button label="Synchronisation depuis ORéOF" icon="pi pi-refresh" />
      </div>
      <div class="text-xl font-bold mb-4">{{selectedDiplome?.apcParcours?.display ?? `Aucun parcours renseigné`}}</div>
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
              <Button icon="pi pi-cog" rounded outlined severity="warn" @click="" v-tooltip.top="`Accéder aux paramètres`"/>
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
                <Button icon="pi pi-cog" rounded outlined severity="warn" @click="" v-tooltip.top="`Accéder aux paramètres`"/>
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
                  <div v-if="enseignementUe.enseignement.bonification" class="px-2 font-bold"><Tag severity="danger">Bonif.</Tag></div>

                  <Button icon="pi pi-info-circle" rounded outlined severity="info" @click="getEnseignement(enseignementUe.enseignement.id, semestre)" v-tooltip.top="`Accéder au détail`"/>
                  <Button icon="pi pi-book" rounded outlined severity="primary" @click="" v-tooltip.top="`Accéder au plan de cours`"/>
                  <Button icon="pi pi-cog" rounded outlined severity="warn" @click="" v-tooltip.top="`Accéder aux paramètres`"/>
                </div>
              </Fieldset>
            </Fieldset>
          </div>
        </div>
      </Fieldset>
    </div>
  </div>

  <Dialog v-model:visible="visibleDialog" modal :header="`Détails ${dialogContent?.item.type}  -   ${dialogContent?.item.libelle}`" :style="{ width: '70vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" dismissable-mask>
    <template v-if="dialogContent">
        <div v-if="dialogContent?.item.libelle_court" class="text-s mb-4 text-muted-color">{{dialogContent?.item.libelle_court}}</div>
        <div class="my-6 flex flex-row items-center gap-4">
          <table class="text-lg">
            <thead>
            <tr class="border-b">
              <th class="px-2 font-normal text-muted-color text-start">Code {{dialogContent.item.type}}</th>
              <th class="px-2 font-normal text-muted-color text-start">Enseignement</th>
              <th class="px-2 font-normal text-muted-color text-start">Code apogée</th>
              <th class="px-2 font-normal text-muted-color text-start">Type</th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td class="px-2 font-bold">{{ dialogContent.item.codeEnseignement }}</td>
              <td class="px-2 font-bold">{{ dialogContent.item.libelle }}</td>
              <td class="px-2 font-bold">{{ dialogContent.item.codeApogee }}</td>
              <td class="px-2 font-bold">
                <Tag v-if="dialogContent.item.type === 'sae'" severity="success">{{ dialogContent.item.type }}</Tag>
                <Tag v-else severity="info">{{ dialogContent.item.type }}</Tag>
              </td>
            </tr>
            </tbody>
          </table>
          <div v-if="dialogContent.item.bonification" class="px-2 font-bold"><Tag severity="danger">Bonif.</Tag></div>
        </div>

        <Divider/>
        <FicheRessource v-if="dialogContent.item.type === 'ressource'" :enseignement="dialogContent.item" :parcours="selectedDiplome.apcParcours" :semestre="dialogContent.semestre"/>
        <FicheSae v-else-if="dialogContent.item.type === 'sae'" :enseignement="dialogContent.item" :parcours="selectedDiplome.apcParcours" :semestre="dialogContent.semestre"/>
        <FicheMatiere v-else-if="dialogContent.item.type === 'matiere'" :enseignement="dialogContent.item" :semestre="dialogContent.semestre" :diplome="selectedDiplome"/>
    </template>
  </Dialog>
</template>

<style scoped>

</style>
