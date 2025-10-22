<script setup lang="ts">
import {ErrorView, ListSkeleton, SimpleSkeleton} from "@components";
import {onMounted, ref} from "vue";
import {useUsersStore} from '@stores'
import { getReferentiels } from '@requests'
import {useConfirm} from "primevue/useconfirm";
import {useToast} from "primevue/usetoast";
import api from '@helpers/axios';
import AfficheReferentielCompetences from "@/components/Pn/AfficheReferentielCompetences.vue";

const usersStore = useUsersStore();

const hasError = ref(false);
const departementId = ref(null);
const isLoadingDiplomes = ref(true);
const isLoadingReferentiel = ref(true);
const referentiels = ref([])
const selectedReferentiel = ref(null)

const confirm = useConfirm();
const toast = useToast();

onMounted(async () => {
  if (usersStore.departementDefaut) {
    departementId.value = await usersStore.departementDefaut.id;
    await _getReferentiels();
  } else {
    console.error('departementDefaut is not defined');
  }
})

const _getReferentiels = async () => {
  try {
    isLoadingDiplomes.value = true
    referentiels.value = await getReferentiels(departementId.value);
    if (referentiels.value.length > 0) {
      selectedReferentiel.value = referentiels.value[0];
    }
  } catch (error) {
    console.error('Erreur lors du chargement des référentiels:', error);
    hasError.value = true;
  } finally {
    isLoadingDiplomes.value = false;
  }
}

const changeReferentiel = (referentiel) => {
  isLoadingReferentiel.value = true;
  selectedReferentiel.value = referentiel;
  isLoadingReferentiel.value = false;
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
        departementId: departementId.value
      })
          .then(() => {
            toast.add({severity: 'success', summary: 'Succès', detail: 'Synchronisation terminée.', life: 3000});
            getReferentiels(departementId.value);
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
      <Tabs :value="selectedReferentiel?.id || referentiels[0]?.id" scrollable>
        <TabList>
          <Tab v-for="referentiel in referentiels" :key="referentiel.libelle" :value="referentiel.id" @click="changeReferentiel(referentiel)">
            <span>{{ referentiel.libelle }}</span> | <span>{{ referentiel.anneePublication }}</span>
          </Tab>
        </TabList>
      </Tabs>
    </div>

    <ListSkeleton v-if="isLoadingReferentiel" class="mt-4"/>
    <div v-else class="mt-6">
      <div class="flex justify-between items-center my-6">
        <h3 class="text-xl font-semibold">Référentiel de compétences : <span class="font-bold">{{ selectedReferentiel.libelle }} ({{ selectedReferentiel.anneePublication }})</span></h3>
        <Button label="Synchronisation depuis ORéOF" icon="pi pi-refresh" @click="synchronisationOreof"/>
      </div>
      <p>{{selectedReferentiel.description}}</p>
      <AfficheReferentielCompetences :referentiel="selectedReferentiel" v-if="selectedReferentiel" />
      <div v-else class="text-center text-muted-color">Aucun référentiel selectionné</div>
    </div>
  </div>
</template>

<style scoped>

</style>
