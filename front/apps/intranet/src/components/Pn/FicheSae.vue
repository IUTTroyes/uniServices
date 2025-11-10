<script setup>
import {ref, computed, onMounted} from 'vue';
import { marked } from 'marked';
import { ApcCompetenceBadge, ApcAcBadge } from '@components';
import {ErrorView} from "@components";
import {getEnseignementService} from "@requests";
import {useUsersStore} from "@stores";
import router from "../../router/index.js";
import Loader from "@components/loader/GlobalLoader.vue";

const props = defineProps({
  enseignement: {
    type: Object,
    required: true
  },
  parcours: {
    type: Object,
  },
  semestre: {
    type: Object,
    required: true
  }
});

const isLoading = ref(false);
const enseignementLocal = ref([]);
const usersStore = useUsersStore();
const isAdmin = computed(() => usersStore.isAdmin);
const hasError = ref(false);

const uniqueCompetences = computed(() => {
  const competences = new Set();
  enseignementLocal.value.enseignementUes?.forEach(ue => {
    if (ue.ue.competence) {
      competences.add(ue.ue.competence);
    }
  });
  return Array.from(competences);
});

const motsCles = computed(() => {
  if (enseignementLocal.value.motsCles && enseignementLocal.value.motsCles.length > 0) {
    return enseignementLocal.value.motsCles.split(',');
  }
});

const isDescriptionExpanded = ref(false);

const formatDescription = (description) => {
  if (!description) {
    return 'Aucune description disponible';
  }
  if (isDescriptionExpanded.value || description.length <= 500) {
    return marked(description);
  }
  return marked(description.slice(0, 500) + '...');
};

const toggleDescription = () => {
  isDescriptionExpanded.value = !isDescriptionExpanded.value;
};

const isObjectifExpanded = ref(false);

const formatObjectif = (objectif) => {
  if (!objectif) {
    return 'Aucun objectif disponible';
  }
  if (isObjectifExpanded.value || objectif.length <= 500) {
    return marked(objectif);
  }
  return marked(objectif.slice(0, 500) + '...');
};

const toggleObjectif = () => {
  isDescriptionExpanded.value = !isDescriptionExpanded.value;
};

const showEnfantParent = async (id) => {
  enseignementLocal.value = await getEnseignementService(id);
};

onMounted(async () => {
  try {
    isLoading.value = true;
    enseignementLocal.value = await getEnseignementService(props.enseignement);

    // Vérifiez et initialisez les heures si elles sont manquantes
    if (enseignementLocal.value.heures) {
      enseignementLocal.value.heures.Total = {
        PN: (enseignementLocal.value.heures.CM?.PN || 0) +
            (enseignementLocal.value.heures.TD?.PN || 0) +
            (enseignementLocal.value.heures.TP?.PN || 0) +
            (enseignementLocal.value.heures.Projet?.PN || 0),
        IUT: (enseignementLocal.value.heures.CM?.IUT || 0) +
            (enseignementLocal.value.heures.TD?.IUT || 0) +
            (enseignementLocal.value.heures.TP?.IUT || 0) +
            (enseignementLocal.value.heures.Projet?.IUT || 0)
      };
    }
  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors de la récupération de l\'enseignement :', error);
  } finally {
    console.log(enseignementLocal.value)
    isLoading.value = false;
  }
});
</script>

<template>
  <Loader v-if="isLoading" />
  <ErrorView v-else-if="hasError"/>
  <div v-else>
    <div class="px-8 flex flex-row items-center gap-4">
      <div class="text-xl font-semibold">Détails {{enseignementLocal.type}} - {{enseignementLocal.libelle}}</div>
      <div v-if="enseignementLocal.libelle_court" class="text-s mb-4 text-muted-color">{{enseignementLocal.libelle_court}}</div>
      <Tag v-if="enseignementLocal.enfants && enseignementLocal.enfants.length >= 1" severity="danger">Ressource parent</Tag>
      <Tag v-if="enseignementLocal.parent" severity="warn">Ressource enfant</Tag>
    </div>
    <Divider/>
    <div class="py-4 px-8 flex flex-row items-center gap-4">
      <table class="text-lg">
        <thead>
        <tr class="border-b">
          <th class="px-2 font-normal text-muted-color text-start">Code {{enseignementLocal.type}}</th>
          <th class="px-2 font-normal text-muted-color text-start">Enseignement</th>
          <th class="px-2 font-normal text-muted-color text-start">Code apogée</th>
          <th class="px-2 font-normal text-muted-color text-start">Type</th>
        </tr>
        </thead>
        <tbody>
        <tr>
          <td class="px-2 font-bold">{{ enseignementLocal.codeEnseignement }}</td>
          <td class="px-2 font-bold">{{ enseignementLocal.libelle }}</td>
          <td class="px-2 font-bold">{{ enseignementLocal.codeApogee }}</td>
          <td class="px-2 font-bold">
            <Tag v-if="enseignementLocal.type === 'sae'" severity="success">{{ enseignementLocal.type }}</Tag>
            <Tag v-else severity="info">{{ enseignementLocal.type }}</Tag>
          </td>
        </tr>
        </tbody>
      </table>
      <div v-if="enseignementLocal.bonification" class="px-2 font-bold"><Tag severity="danger">Bonif.</Tag></div>
    </div>
    <div v-if="(enseignementLocal.enfants && enseignementLocal.enfants.length >= 1) || enseignementLocal.parent" class="border-gray-200 border p-6 rounded-xl my-6">
      <div v-if="enseignementLocal.enfants && enseignementLocal.enfants.length >= 1" class="font-bold text-lg">Ressources enfants :</div>
      <div v-else class="font-bold text-lg">Ressource parent :</div>
      <div v-if="enseignementLocal.enfants && enseignementLocal.enfants.length >= 1" v-for="enfant in enseignementLocal.enfants" :key="enfant.id" class="p-6 rounded-xl my-6 flex flex-row items-center gap-4">
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

        <Button icon="pi pi-info-circle" rounded outlined severity="info" @click="showEnfantParent(enfant.id)" v-tooltip.top="`Accéder au détail`"/>
        <Button icon="pi pi-book" rounded outlined severity="primary" @click="" v-tooltip.top="`Accéder au plan de cours`"/>
        <Button icon="pi pi-cog" rounded outlined severity="warn" @click="" v-tooltip.top="`Accéder aux paramètres`"/>
      </div>
      <div v-else-if="enseignementLocal.parent" class="p-6 rounded-xl my-6 flex flex-row items-center gap-4">
        <table class="text-lg">
          <thead>
          <tr class="border-b">
            <th class="px-2 font-normal text-muted-color text-start">Code {{enseignementLocal.parent.type}}</th>
            <th class="px-2 font-normal text-muted-color text-start">Enseignement</th>
            <th class="px-2 font-normal text-muted-color text-start">Code apogée</th>
            <th class="px-2 font-normal text-muted-color text-start">Type</th>
          </tr>
          </thead>
          <tbody>
          <tr>
            <td class="px-2 font-bold">{{ enseignementLocal.parent.codeEnseignement }}</td>
            <td class="px-2 font-bold">{{ enseignementLocal.parent.libelle }}</td>
            <td class="px-2 font-bold">{{ enseignementLocal.parent.codeApogee }}</td>
            <td class="px-2 font-bold">
              <Tag v-if="enseignementLocal.parent.type === 'sae'" severity="success">{{ enseignementLocal.parent.type }}</Tag>
              <Tag v-else severity="info">{{ enseignementLocal.parent.type }}</Tag>
            </td>
          </tr>
          </tbody>
        </table>
        <div v-if="enseignementLocal.parent.bonification" class="px-2 font-bold"><Tag severity="danger">Bonif.</Tag></div>

        <Button icon="pi pi-info-circle" rounded outlined severity="info" @click="showEnfantParent(enseignementLocal.parent.id)" v-tooltip.top="`Accéder au détail`"/>
        <Button icon="pi pi-book" rounded outlined severity="primary" @click="" v-tooltip.top="`Accéder au plan de cours`"/>
        <Button icon="pi pi-cog" rounded outlined severity="warn" @click="" v-tooltip.top="`Accéder aux paramètres`"/>
      </div>
    </div>

    <div class="py-4 px-8 flex flex-col gap-4 w-full">
      <div>
        <div class="font-bold text-lg">Description :</div>
        <div v-html="formatDescription(enseignementLocal.description)"></div>
        <div v-if="enseignementLocal.description && enseignementLocal.description.length > 500" class="text-primary underline cursor-pointer" @click="toggleDescription">
          {{ isDescriptionExpanded ? 'Voir moins' : 'Voir plus...' }}
        </div>
      </div>
      <div>
        <div class="font-bold text-lg">Objectifs et problématique professionnelle associée :</div>
        <div class="flex gap-2 flex-wrap">{{enseignementLocal.objectif ?? 'Aucun objectif renseigné'}}</div>
      </div>
      <div>
        <div class="font-bold text-lg">Mots clés :</div>
        <div class="flex gap-2 flex-wrap"><Tag v-for="motCle in motsCles" class="lowercase">{{ motCle }}</Tag><span v-if="!motsCles">Aucun mot clé renseigné</span></div>
      </div>

      <Divider/>

      <div class="text-xl font-bold">Structure de l'enseignement dans la formation</div>
      <div class="flex flex-wrap gap-x-24 gap-y-4">
        <div>
          <span class="font-bold text-lg">Parcours : </span>
          <span v-if="props.parcours">{{props.parcours.libelle }}</span>
          <span v-else>Aucun parcours renseigné</span>
        </div>
        <div>
          <span class="font-bold text-lg">Semestre : </span>
          <span v-if="props.semestre">{{props.semestre.libelle }}</span>
          <span v-else>Aucun semestre renseigné</span>
        </div>
        <div><span class="font-bold text-lg">Mutualisée : </span><span v-if="enseignementLocal.mutualisee"><Tag severity="success">Oui</Tag></span><span v-else><Tag>Non</Tag></span></div>
        <div><span class="font-bold text-lg">Suspendue : </span><span v-if="enseignementLocal.suspendu"><Tag severity="danger">Oui</Tag></span><span v-else><Tag severity="success">Non</Tag></span></div>
      </div>
      <div class="flex gap-12 w-full border p-4 rounded-lg bg-neutral-200 bg-opacity-20">
        <DataTable :value="[
            { type: 'Volume horaires attendu', CM: enseignementLocal.heures?.CM?.PN, TD: enseignementLocal.heures?.TD?.PN, TP: enseignementLocal.heures?.TP?.PN, Projet: enseignementLocal.heures?.Projet?.PN, Total: enseignementLocal.heures?.Total?.PN },
            { type: 'Volume horaires saisi', CM: enseignementLocal.heures?.CM?.IUT, TD: enseignementLocal.heures?.TD?.IUT, TP: enseignementLocal.heures?.TP?.IUT, Projet: enseignementLocal.heures?.Projet?.IUT, Total: enseignementLocal.heures?.Total?.IUT }
          ]"
                   tableStyle="min-width: 50rem"
                   size="small"
                   class="w-full"
        >
          <Column field="type" header="" class="!text-nowrap !border-r" />
          <Column field="CM" header="CM" class="!text-nowrap !border-r">
            <template #body="slotProps">
              <span v-if="slotProps.data.type === 'Volume horaires saisi' && slotProps.data.CM !== enseignementLocal.heures?.CM?.PN">
                <Tag severity="danger">{{ slotProps.data.CM }}</Tag>
              </span>
              <span v-else>{{ slotProps.data.CM }}</span>
            </template>
          </Column>
          <Column field="TD" header="TD" class="!text-nowrap !border-r">
            <template #body="slotProps">
              <span v-if="slotProps.data.type === 'Volume horaires saisi' && slotProps.data.TD !== enseignementLocal.heures?.TD?.PN">
                <Tag severity="danger">{{ slotProps.data.TD }}</Tag>
              </span>
              <span v-else>{{ slotProps.data.TD }}</span>
            </template>
          </Column>
          <Column field="TP" header="TP" class="!text-nowrap !border-r">
            <template #body="slotProps">
              <span v-if="slotProps.data.type === 'Volume horaires saisi' && slotProps.data.TP !== enseignementLocal.heures?.TP?.PN">
                <Tag severity="danger">{{ slotProps.data.TP }}</Tag>
              </span>
              <span v-else>{{ slotProps.data.TP }}</span>
            </template>
          </Column>
          <Column field="Projet" header="Projet" class="!text-nowrap !border-r">
            <template #body="slotProps">
              <span v-if="slotProps.data.type === 'Volume horaires saisi' && slotProps.data.Projet !== enseignementLocal.heures?.Projet?.PN">
                <Tag severity="danger">{{ slotProps.data.Projet }}</Tag>
              </span>
              <span v-else>{{ slotProps.data.Projet }}</span>
            </template>
          </Column>
          <Column field="Total" header="Total">
            <template #body="slotProps">
              <span v-if="slotProps.data.type === 'Volume horaires saisi' && slotProps.data.Total !== enseignementLocal.heures?.Total?.PN">
                <Tag severity="danger">{{ slotProps.data.Total }}</Tag>
              </span>
              <span v-else>{{ slotProps.data.Total }}</span>
            </template>
          </Column>
        </DataTable>
      </div>
      <div v-if="isAdmin & (enseignementLocal.heures?.Total?.IUT !== enseignementLocal.heures?.Total?.PN)" class="flex justify-center w-full items-center gap-4">
        <Message severity="error" icon="pi pi-exclamation-triangle">
          Attention, le volume horaire saisi ne correspond pas au volume horaire attendu dans la maquette.
        </Message>
        <Button label="Corriger le prévisionnel" severity="danger" @click="router.push('/administration/previsionnel/semestre')"> </Button>
      </div>
      <Divider/>
      <div class="text-xl font-bold">Cet enseignement dans l'APC</div>
      <div class="flex gap-2">
        <span class="font-bold text-lg">Compétence(s) ciblée(s) : </span>
        <ApcCompetenceBadge v-for="ue in uniqueCompetences" :key="ue.nomCourt" :competence="ue" />
        <span v-if="uniqueCompetences.length < 1">Aucune compétence</span>
      </div>
      <div class="flex gap-2 flex-wrap">
        <span class="font-bold text-lg">Apprentissage(s) critique(s) : </span>
        <ApcAcBadge v-for="ac in enseignementLocal.apprentissageCritique" :key="ac.code" :ac="ac" v-tooltip.top="`${ac.libelle}`">{{ ac.code }}</ApcAcBadge>
        <span v-if="enseignementLocal.apprentissageCritique?.length < 1">Aucun apprentissage critique</span>
      </div>
      <div><span class="font-bold text-lg">Prérequis : </span> {{ enseignementLocal.preRequis ?? 'Aucune ressource prérequise' }}</div>
      <div><span class="font-bold text-lg">Nombre de notes : </span> {{ enseignementLocal.nbNotes ?? 'Aucun nombre de note renseigné' }}</div>
    </div>
  </div>
</template>

<style scoped>
</style>
