<script setup lang="ts">
import {ErrorView, ListSkeleton, SimpleSkeleton} from "@components";
import {onMounted, ref} from "vue";
import {useDiplomeStore, useUsersStore} from '@stores'
import {useConfirm} from "primevue/useconfirm";
import {useToast} from "primevue/usetoast";
import api from '@helpers/axios';


const diplomeStore = useDiplomeStore();
const usersStore = useUsersStore();

const hasError = ref(false);
const departementId = ref(null);
const isLoadingDiplomes = ref(true);
const isLoadingReferentiel = ref(true);
const diplomes = ref([])
const selectedDiplome = ref(null)

const confirm = useConfirm();
const toast = useToast();

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
    await diplomeStore.getDiplomesActifsDepartement(departementId); //filtrer sur un niveau de diplôme plus haut ? pour avoir juste MMI 1 fois ?
    diplomes.value = diplomeStore.diplomes;
    if (diplomes.value.length > 0) {
      selectedDiplome.value = diplomes.value[0];
      await getRefCompetencesForDiplome(selectedDiplome.value.id);
    }
  } catch (error) {
    console.error('Erreur lors du chargement des diplomes:', error);
    hasError.value = true;
  } finally {
    isLoadingDiplomes.value = false
  }
}

const getRefCompetencesForDiplome = async (diplomeId) => {
  try {
    isLoadingReferentiel.value = true;
    // pn.value = await getPnDiplome(selectedDiplome.value.id, selectedAnneeUniversitaire.id)
    // console.log(pn.value)
    // if (pn.value.length > 0) {
    //   pn.value = pn.value[0];
    //   nodes.value = transformData(pn.value.annees);
    // }
  } catch (error) {
    console.error('Erreur lors du chargement des PNs:', error);
    hasError.value = true;
  } finally {
    isLoadingReferentiel.value = false;
  }
}

const changeDiplome = (diplome) => {
  selectedDiplome.value = diplome;
  getRefCompetencesForDiplome(diplome.id);
}

const synchronisationOreof = async () => {
  confirm.require({
    message: 'Voulez-vous synchroniser les référentiels de compétences depuis ORéOF ? Les données existantes seront écrasées.',
    header: 'Confirmation',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
      label: 'Annuler',
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: 'Lancer la synchronisation',
    },
    accept: async () => {
      toast.add({
        severity: 'success',
        summary: 'Confirmé',
        detail: 'Synchronisation en cours. Cela peut prendre quelques minutes.',
        life: 3000
      });
      await api.post('/api/oreof/ref-competences/synchronisation', {
        departementId: departementId.value,
        diplomeId: selectedDiplome.value.id
      })
          .then(() => {
            toast.add({severity: 'success', summary: 'Succès', detail: 'Synchronisation terminée.', life: 3000});
            getDiplomes(departementId.value);
          })
          .catch((error) => {
            console.error('Erreur lors de la synchronisation:', error);
            toast.add({
              severity: 'error',
              summary: 'Erreur',
              detail: 'Une erreur est survenue lors de la synchronisation.',
              life: 3000
            });
          });
    },
    reject: () => {
      toast.add({severity: 'error', summary: 'Annulé', detail: 'Opération annulée.', life: 3000});
    }
  });
}


</script>

<template>
  <ErrorView v-if="hasError"/>
  <div v-else class="card">
    <SimpleSkeleton v-if="isLoadingDiplomes" class="w-1/2"/>
    <div v-else>
      <h2 class="text-2xl font-bold">Référentiels de compétences</h2>
      <Divider/>
      <Tabs :value="selectedDiplome?.id || diplomes[0]?.id" scrollable>
        <TabList>
          <Tab v-for="diplome in diplomes" :key="diplome.libelle" :value="diplome.id" @click="changeDiplome(diplome)">
            <span>{{ diplome.typeDiplome.sigle }}</span> | <span>{{ diplome.sigle }}</span>
          </Tab>
        </TabList>
      </Tabs>
    </div>

    <ListSkeleton v-if="isLoadingReferentiel" class="mt-4"/>
    <div v-else class="mt-6">
      <div class="flex justify-between items-center my-6">
        <div class="text-xl font-bold">{{ selectedDiplome?.parcours?.display ?? `Aucun parcours renseigné` }}</div>
        <Button label="Synchronisation depuis ORéOF" icon="pi pi-refresh" @click="synchronisationOreof"/>
      </div>

    </div>
  </div>
</template>

<style scoped>

</style>
