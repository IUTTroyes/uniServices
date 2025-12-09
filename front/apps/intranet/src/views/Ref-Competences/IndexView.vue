<script setup lang="ts">
import {ErrorView, ListSkeleton, SimpleSkeleton, ValidatedInput, validationRules} from "@components";
import {onMounted, ref} from "vue";
import {useUsersStore} from '@stores'
import {getReferentiels} from '@requests'
import {useConfirm} from "primevue/useconfirm";
import {useToast} from "primevue/usetoast";
import api from '@helpers/axios';
import AfficheReferentielCompetences from "@/components/Pn/AfficheReferentielCompetences.vue";
import BlocHelp from "@components/components/BlocHelp.vue"

const usersStore = useUsersStore();

const hasError = ref(false);
const departementId = ref(null);
const isLoadingDiplomes = ref(true);
const create = ref(false);
const isLoadingReferentiel = ref(true);
const referentiels = ref([])
const selectedReferentiel = ref(null)
const oreofId = ref<string | null>(null)
const pizza = ref(false)

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
      isLoadingReferentiel.value = false;
    }
  } catch (error) {
    console.error('Erreur lors du chargement des référentiels:', error);
    hasError.value = true;
  } finally {
    isLoadingDiplomes.value = false;
  }
}

const changeReferentiel = (referentiel) => {
  create.value = false;
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
          <Tab v-for="referentiel in referentiels" :key="referentiel.libelle" :value="referentiel.id"
               @click="changeReferentiel(referentiel)">
            <span>{{ referentiel.libelle }}</span> | <span>{{ referentiel.anneePublication }}</span>
          </Tab>
          <Tab value="add-referentiel" @click="create = true">
            <span>+ Ajouter un référentiel</span>
          </Tab>
        </TabList>
      </Tabs>
    </div>

    <template v-if="create">
      <div class="flex justify-between items-center my-6">
        <h3 class="text-xl font-semibold">Créer un nouveau référentiel de compétences</h3>
      </div>
      <BlocHelp message="Création d'un nouveau référentiel de compétences"></BlocHelp>
      <form class="flex flex-col gap-4 mt-2">
        <div>
          <ValidatedInput
            v-model="oreofId"
            name="oreofId"
            label="Id ORéOF associé"
            type="text"
            :rules="[]"
            placeholder="Saisir l'id ORéOF"
          />
        </div>

        <div class="flex items-center gap-2">
          <Checkbox v-model="pizza" inputId="ingredient2" name="pizza" value="Mushroom" />
          <label for="ingredient2"> Synchroniser avec ORéBUT </label>
        </div>

        <Button
            label="Créer le référentiel de compétences"
            icon="pi pi-check"
            class="mt-4"
        />
      </form>
    </template>
    <template v-else>
      <ListSkeleton v-if="isLoadingReferentiel" class="mt-4"/>
      <div v-else class="mt-6">
        <div class="flex justify-between items-center my-6">
          <h3 class="text-xl font-semibold">Référentiel de compétences : <span
              class="font-bold">{{ selectedReferentiel.libelle }} ({{ selectedReferentiel.anneePublication }})</span>
          </h3>
          <Button label="Synchronisation depuis ORéOF" icon="pi pi-refresh" @click="synchronisationOreof"/>
        </div>
        <p>{{ selectedReferentiel.description }}</p>
        <AfficheReferentielCompetences :referentiel="selectedReferentiel" v-if="selectedReferentiel"/>
        <div v-else class="text-center text-muted-color">Aucun référentiel selectionné</div>
      </div>
    </template>
  </div>
</template>

<style scoped>

</style>
