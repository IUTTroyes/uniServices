<script setup>
import {onMounted, ref} from 'vue'
import {useDiplomeStore, useUsersStore, useAnneeUnivStore} from '@stores'
import {ErrorView, ListSkeleton, SimpleSkeleton, ArticleSkeleton} from "@components";
import {getDiplomesService} from "@requests";
import Loader from "@components/loader/GlobalLoader.vue"
import FicheRessource from "../../components/Pn/FicheRessource.vue";
import FicheSae from "../../components/Pn/FicheSae.vue";
import FicheMatiere from "../../components/Pn/FicheMatiere.vue";
import {
  getAnneeSemestresService,
  getCompetenceUeService,
  getPnAnneesService,
  getPnDiplome,
  getSemestreUesService,
  getEnseignementsUeService
} from "@requests";

const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();
const departementId = ref(null);

const isLoadingDiplomes = ref(true)
const diplomes = ref([])
const selectedDiplome = ref(null)
const selectedPn = ref(null)

const isLoadingPn = ref(true)
const pn = ref(null)
const annees = ref([])
const semestres = ref([])
const ues = ref([])
const enseignements = ref([])

const hasError = ref(false)

const visibleDialog = ref(false);
const dialogContent = ref(null);

const selectedAnneeUniversitaire = ref(anneeUnivStore.selectedAnneeUniv);

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
    const params = {
      departement: departementId,
    }
    diplomes.value = await getDiplomesService(params, '/maquette');
  } catch (error) {
    console.error('Erreur lors du chargement des diplomes:', error);
    hasError.value = true;
  } finally {
    if (diplomes.value.length > 0) {
      selectedDiplome.value = diplomes.value[0];
      selectedPn.value = selectedDiplome.value?.pns?.find(pn => pn.anneeUniversitaire?.actif === true) || null;
      console.log(selectedPn.value);
    }
    isLoadingDiplomes.value = false
    console.log(diplomes.value);
  }
}

const changeDiplome = (diplome) => {
  selectedDiplome.value = diplome
  selectedPn.value = selectedDiplome.value?.pns?.find(pn => pn.anneeUniversitaire?.actif === true) || null;
  console.log(selectedPn.value);
}

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
  <div v-else class="card h-full">
    <div v-if="isLoadingDiplomes">
    <SimpleSkeleton width="100%" class="mb-6"/>
    <div class="flex flex-row justify-between">
      <SimpleSkeleton width="20%" class="mb-6"/>
      <SimpleSkeleton width="10%" class="mb-6"/>
    </div>
    </div>
    <div v-else>
      <h2 class="text-2xl font-bold">Programmes pédagogiques nationaux</h2>
      <Divider/>
      <Tabs :value="selectedDiplome?.id || diplomes[0]?.id" scrollable>
        <TabList>
          <Tab v-for="diplome in diplomes" :key="diplome.libelle" :value="diplome.id" @click="changeDiplome(diplome)">
            <span>{{ diplome.typeDiplome.sigle }}</span> | <span>{{ diplome.sigle }}</span> <Tag v-if="!diplome.actif" severity="danger">Inactif</Tag>
          </Tab>
        </TabList>
      </Tabs>
    </div>

    <Loader v-if="isLoadingDiplomes" class="mt-6"/>
    <div v-else class="mt-6">
      <div class="flex justify-between items-center my-6">
        <div class="text-xl font-bold">{{selectedDiplome?.parcours?.display ?? `Aucun parcours renseigné`}}</div>
        <Button label="Synchronisation depuis ORéOF" icon="pi pi-refresh"/>
      </div>
      <div class="text-muted-color mb-4">Responsable du diplome : {{selectedDiplome?.responsableDiplome?.display ?? `Pas de responsable`}}</div>
      <Fieldset v-if="selectedPn" v-for="annee in selectedPn.annees" :legend="`${annee.libelle}`" :toggleable="true">
        <template #toggleicon>
          <i class="pi pi-angle-down"></i>
        </template>
        <div class="border-l-4 border-primary-500 pl-4">
          <div class="mb-1 text-lg">{{annee.libelleLong}}</div>
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

          <div v-for="semestre in annee.semestres" class="ml-6 border-l-4 border-primary-300 pl-4">
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
                  <td class="px-2 font-bold">{{ semestre.ues?.length ?? '' }}</td>
                </tr>
                </tbody>
              </table>
            </div>
            <Fieldset v-for="ue in semestre.ues" :toggleable="true" :legend="`${ue.numero} . ${ue.displayApc}`" class="ml-6" :collapsed="true">
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
                    <td v-if="selectedDiplome.typeDiplome.apc" :class="ue.competence.couleur" class="px-2 font-bold !w-fit !bg-opacity-40">{{ue.competence.nomCourt}}</td>
                    <td class="px-2 font-bold !w-fit">{{ue.nbEcts}}</td>
                    <td v-if="!selectedDiplome.typeDiplome.apc" class="px-2 font-bold !w-fit">0</td>
                  </tr>
                  </tbody>
                </table>
                <Button icon="pi pi-cog" rounded outlined severity="warn" @click="" v-tooltip.top="`Accéder aux paramètres`"/>
              </div>

              <div v-for="enseignementUe in ue.enseignementUes" class="!border-l-4 !border-l-primary-200 !pl-4 !border-0">
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

                      <Button icon="pi pi-info-circle" rounded outlined severity="info" @click="showDetails(enseignementUe.enseignement, semestre)" v-tooltip.top="`Accéder au détail`"/>
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
          Aucun programme pédagogique national trouvé pour le diplôme et l'année universitaire sélectionné.
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
