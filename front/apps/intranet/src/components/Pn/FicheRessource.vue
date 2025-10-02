<script setup>
import { ref, defineProps, computed, onMounted } from 'vue';
import { marked } from 'marked';
import { ApcCompetenceBadge, ApcAcBadge } from '@components';
import { getEnseignementService } from '@requests';
import Loader from '@components/loader/GlobalLoader.vue';

// Définition des props
const props = defineProps({
  enseignement: {
    type: Number,
    required: true
  },
  parcours: {
    type: Object
  },
  semestre: {
    type: Object,
    required: true
  }
});

// Références et variables réactives
const isLoading = ref(false);
const enseignementLocal = ref([]);
const isDescriptionExpanded = ref(false);
const isObjectifExpanded = ref(false);

// Fonctions computed
const uniqueCompetences = computed(() => {
  const competences = new Set();
  enseignementLocal.value.enseignementUes?.forEach(ue => {
    if (ue.ue.apcCompetence) {
      competences.add(ue.ue.apcCompetence);
    }
  });
  return Array.from(competences);
});

const motsCles = computed(() => {
  if (enseignementLocal.value.motsCles && enseignementLocal.value.motsCles.length > 0) {
    return enseignementLocal.value.motsCles.split(',');
  }
});

// Fonctions utilitaires
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
  isObjectifExpanded.value = !isObjectifExpanded.value;
};

const showEnfantParent = async (id) => {
  enseignementLocal.value = await getEnseignementService(id);
};

// Hook onMounted
onMounted(async () => {
  isLoading.value = true;
  try {
    enseignementLocal.value = await getEnseignementService(props.enseignement);
  } catch (error) {
    console.error('Erreur lors de la récupération de l\'enseignement :', error);
  } finally {
    console.log(enseignementLocal.value)
    isLoading.value = false;
  }
});
</script>

<template>
  <Loader v-if="isLoading" />
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
        <div class="flex gap-2 flex-wrap">{{enseignement.objectif ?? 'Aucun objectif renseigné'}}</div>
      </div>
      <div>
        <div class="font-bold">Mots clés :</div>
        <div class="flex gap-2 flex-wrap"><Tag v-for="motCle in motsCles" :key="motCle" class="lowercase">{{ motCle }}</Tag><span v-if="!motsCles">Aucun mot clé renseigné</span></div>
      </div>

      <Divider/>

      <div class="text-xl font-bold">Structure de l'enseignement dans la formation</div>
      <div class="flex flex-wrap gap-x-24 gap-y-4">
        <div>
          <span class="font-bold text-lg">Parcours : </span>
          <span v-if="parcours">{{parcours.libelle }}</span>
          <span v-else>Aucun parcours renseigné</span>
        </div>
        <div>
          <span class="font-bold text-lg">Semestre : </span>
          <span v-if="semestre">{{semestre.libelle }}</span>
          <span v-else>Aucun semestre renseigné</span>
        </div>
        <div><span class="font-bold">Mutualisée : </span><span v-if="enseignementLocal.mutualisee"><Tag severity="success">Oui</Tag></span><span v-else><Tag>Non</Tag></span></div>
        <div><span class="font-bold text-lg">Suspendue : </span><span v-if="enseignementLocal.suspendu"><Tag severity="danger">Oui</Tag></span><span v-else><Tag severity="success">Non</Tag></span></div>
      </div>
      <div class="flex gap-12 w-full">
        <div class="font-bold text-nowrap text-lg">Volumes horaires : </div>
        <DataTable :value="[enseignementLocal.heures]" tableStyle="min-width: 50rem" size="small" class="w-full">
          <Column field="CM.IUT" header="CM" class="!text-nowrap !border-r" />
          <Column field="TD.IUT" header="TD" class="!text-nowrap !border-r"/>
          <Column field="TP.IUT" header="TP" class="!text-nowrap !border-r"/>
          <Column field="Projet.IUT" header="Projet" class="!text-nowrap !border-r"/>
          <Column field="Total.IUT" header="Total" />
        </DataTable>
      </div>
      <Divider/>
      <div class="text-xl font-bold">Cet enseignement dans l'APC</div>
      <div class="flex gap-2">
        <span class="font-bold text-lg">Compétence(s) ciblée(s) : </span>
        <ApcCompetenceBadge v-for="ue in uniqueCompetences" :key="ue.nomCourt" :competence="ue" />
        <span v-if="uniqueCompetences.length === 0">Aucune compétence</span>
      </div>
<!--      <div class="flex gap-2 flex-wrap">-->
<!--        <span class="font-bold text-lg">Apprentissage(s) critique(s) : </span>-->
<!--        <ApcAcBadge v-for="ac in enseignementLocal.apprentissageCritique" :key="ac.code" :ac="ac" v-tooltip.top="`${ac.libelle}`">{{ ac.code }}</ApcAcBadge>-->
<!--        <span v-if="enseignementLocal.apprentissageCritique.length === 0">Aucun apprentissage critique</span>-->
<!--      </div>-->
      <div><span class="font-bold text-lg">SAÉ concernée(s) : </span> {{ enseignementLocal.sae ?? 'Aucune SAÉ renseignée' }}</div>
      <div><span class="font-bold text-lg">Prérequis : </span> {{ enseignementLocal.preRequis ?? 'Aucune ressource renseignée' }}</div>
      <div><span class="font-bold text-lg">Nombre de notes : </span> {{ enseignementLocal.nbNotes ?? 'Aucun nombre de note renseigné' }}</div>
    </div>
  </div>
</template>

<style scoped>
</style>
