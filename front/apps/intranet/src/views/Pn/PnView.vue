<script setup>
import {onMounted, ref, watch} from 'vue'
import { useSemestreStore, useUsersStore, useDiplomeStore, useAnneeUnivStore } from '@stores'
import {ListSkeleton, SimpleSkeleton, ErrorView} from "@components";
import FicheRessource from "../../components/Pn/FicheRessource.vue";
import FicheSae from "../../components/Pn/FicheSae.vue";
import FicheMatiere from "../../components/Pn/FicheMatiere.vue";
import { getEnseignementService, getPnsDiplome, getPnDiplome } from "@requests";

const usersStore = useUsersStore();
const diplomeStore = useDiplomeStore();
const departementId = ref(null);

const diplomes = ref([])
const selectedDiplome = ref(null)
const pn = ref(null)
const selectedPn = ref(null)
const selectedEnseignement = ref(null)
const isLoadingDiplomes = ref(true)
const isLoadingPn = ref(true)
const isLoadingEnseignement = ref(true)
const hasError = ref(false)

const visibleDialog = ref(false);
const dialogContent = ref(null);

const selectedAnneeUniversitaire = JSON.parse(localStorage.getItem('selectedAnneeUniv'))

onMounted(async () => {
  if (usersStore.departementDefaut) {
    departementId.value = usersStore.departementDefaut.id;
    await getDiplomes(departementId.value);
  } else {
    console.error('departementDefaut is not defined');
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
    hasError.value = true;
  } finally {
    isLoadingDiplomes.value = false
  }
}

const getPnsForDiplome = async (diplomeId) => {
  try {
    console.log(selectedAnneeUniversitaire.id)
    isLoadingPn.value = true;
    pn.value = await getPnDiplome(selectedDiplome.value.id, selectedAnneeUniversitaire.id)
    console.log(pn.value)
    if (pn.value.length > 0) {
      pn.value = pn.value[0];
      nodes.value = transformData(pn.value.annees);
    }
  } catch (error) {
    console.error('Erreur lors du chargement des PNs:', error);
    hasError.value = true;
  } finally {
    isLoadingPn.value = false;
  }
}

const getEnseignement = async (enseignementId, semestre) => {
  try {
    isLoadingEnseignement.value = true;
    selectedEnseignement.value = await getEnseignementService(enseignementId)
    showDetails(selectedEnseignement.value, semestre)
  } catch (error) {
    console.error('Erreur lors du chargement de l\'enseignement:', error);
    hasError.value = true;
  } finally {
    isLoadingEnseignement.value = false;
  }
}

const changeDiplome = (diplome) => {
  selectedDiplome.value = diplome
  getPnsForDiplome(selectedDiplome.value.id);

  if (selectedPn.value) {
    nodes.value = transformData(selectedPn.value.annees);
  }
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
    children: annee.semestres.map(semestre => ({
      key: semestre['@id'],
      data: { libelle: semestre.libelle, apogeeCode: semestre.codeElement },
      edit: true,
    }))
  }));
};

const showDetails = (item, semestre) => {
  if (item) {
    dialogContent.value = { item, semestre };
    visibleDialog.value = true;
  } else {
    console.error('Item is null or undefined');
  }
};
</script>

<template>
  <ErrorView v-if="hasError" />
  <div v-else class="card">
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
      <div class="flex justify-between items-center my-6">
        <div class="text-xl font-bold">{{selectedDiplome?.parcours?.display ?? `Aucun parcours renseigné`}}</div>
        <Button label="Synchronisation depuis ORéOF" icon="pi pi-refresh"/>
      </div>
<!--    si il n'y a pas de pn    -->
      <div v-if="pn && pn.length === 0" class="flex justify-center">
        <Message severity="error" icon="pi pi-exclamation-triangle" class="w-fit">
          Aucun programme pédagogique national trouvé pour le diplôme et l'année universitaire sélectionné.
        </Message>
      </div>
      <Fieldset v-if="pn" v-for="annee in pn.annees" :legend="`${annee.libelle}`" :toggleable="true">
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
          <div v-for="semestre in annee.semestres" class="ml-6 border-l-2 border-primary-300 pl-4">
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
                  <td class="px-2 font-bold">{{ semestre.ues.length }}</td>
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
            <Fieldset v-for="ue in semestre.ues" :toggleable="true" :legend="`${ue.numero} . ${ue.displayApc}`" class="ml-6 !border-l-2 !border-l-primary-200 !pl-4 !border-0" :collapsed="true">
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
                    <td v-if="selectedDiplome.typeDiplome.apc" :class="ue.competence.couleur" class="px-2 font-bold !w-fit">{{ue.competence.nomCourt}}</td>
                    <td class="px-2 font-bold !w-fit">{{ue.nbEcts}}</td>
                    <td v-if="!selectedDiplome.typeDiplome.apc" class="px-2 font-bold !w-fit">0</td>
                  </tr>
                  </tbody>
                </table>
                <Button icon="pi pi-cog" rounded outlined severity="warn" @click="" v-tooltip.top="`Accéder aux paramètres`"/>
              </div>
              <div v-for="enseignementUe in ue.enseignementUes">
                <Fieldset v-if="!enseignementUe.enseignement.parent" legend="" :toggleable="true">
                  <template #toggleicon>
                    <i class="pi pi-angle-down"></i>
                    <div>{{ enseignementUe.enseignement.libelle }}</div>
                    <Tag v-if="enseignementUe.enseignement.enfants && enseignementUe.enseignement.enfants.length >= 1" severity="danger">Ressource parent</Tag>
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

                  <Fieldset v-for="enfant in enseignementUe.enseignement.enfants" :toggleable="true" class="!bg-gray-300 !bg-opacity-10">
                    <template #toggleicon>
                      <i class="pi pi-angle-down"></i>
                      <div>{{ enfant.libelle }}</div>
                      <Tag v-if="enfant.enfants && enfant.enfants.length >= 1" severity="danger">Ressource parent</Tag>
                      <Tag v-if="enfant.parent" severity="warn">Ressource enfant</Tag>
                    </template>
                    <div class="my-6 flex flex-row items-center gap-4">
                      <table class="text-lg">
                        <thead>
                        <tr class="border-b">
                          <th class="px-2 font-normal text-muted-color text-start">Code {{enfant.type}}</th>
                          <th class="px-2 font-normal text-muted-color text-start">Enseignement</th>
                          <th class="px-2 font-normal text-muted-color text-start">Code apogée</th>
                          <th class="px-2 font-normal text-muted-color text-start">Type</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                          <td class="px-2 font-bold">{{ enfant.codeEnseignement }}</td>
                          <td class="px-2 font-bold">{{ enfant.libelle }}</td>
                          <td class="px-2 font-bold">{{ enfant.codeApogee }}</td>
                          <td class="px-2 font-bold">
                            <Tag v-if="enfant.type === 'sae'" severity="success">{{ enfant.type }}</Tag>
                            <Tag v-else severity="info">{{ enfant.type }}</Tag>
                          </td>
                        </tr>
                        </tbody>
                      </table>
                      <div v-if="enfant.bonification" class="px-2 font-bold"><Tag severity="danger">Bonif.</Tag></div>

                      <Button icon="pi pi-info-circle" rounded outlined severity="info" @click="getEnseignement(enfant.id, semestre)" v-tooltip.top="`Accéder au détail`"/>
                      <Button icon="pi pi-book" rounded outlined severity="primary" @click="" v-tooltip.top="`Accéder au plan de cours`"/>
                      <Button icon="pi pi-cog" rounded outlined severity="warn" @click="" v-tooltip.top="`Accéder aux paramètres`"/>
                    </div>
                  </Fieldset>
                </Fieldset>
              </div>
            </Fieldset>
          </div>
        </div>
      </Fieldset>
      <div v-else class="flex justify-center">
        <Message severity="error" icon="pi pi-exclamation-triangle" class="w-fit">
          Une erreur est survenue
        </Message>
      </div>
    </div>
  </div>

  <Dialog v-model:visible="visibleDialog" modal :style="{ width: '70vw' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" dismissable-mask>
    <template #header>
      <div></div>
    </template>
    <template v-if="dialogContent">
      <FicheRessource v-if="dialogContent.item.type === 'ressource'" :enseignement="dialogContent.item" :parcours="selectedDiplome.parcours" :semestre="dialogContent.semestre"/>
      <FicheSae v-else-if="dialogContent.item.type === 'sae'" :enseignement="dialogContent.item" :parcours="selectedDiplome.parcours" :semestre="dialogContent.semestre"/>
      <FicheMatiere v-else-if="dialogContent.item.type === 'matiere'" :enseignement="dialogContent.item" :semestre="dialogContent.semestre" :diplome="selectedDiplome"/>
    </template>
  </Dialog>
</template>

<style scoped>
.p-dialog-header {
  justify-content: end !important;
}
</style>
