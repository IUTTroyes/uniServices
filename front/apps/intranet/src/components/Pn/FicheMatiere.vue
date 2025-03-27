<script setup>
import { ref, defineProps, computed } from 'vue';
import { marked } from 'marked';
import { ApcCompetenceBadge, ApcAcBadge } from '@components';

const props = defineProps({
  enseignement: {
    type: Object,
    required: true
  },
  semestre: {
    type: Object,
    required: true
  },
  diplome: {
    type: Object,
    required: true
  }
});

const heures = props.enseignement.heures;
heures.Total = {
  PN: heures.CM.PN + heures.TD.PN + heures.TP.PN + heures.Projet.PN,
  IUT: heures.CM.IUT + heures.TD.IUT + heures.TP.IUT + heures.Projet.IUT
};

const uniqueCompetences = computed(() => {
  const competences = new Set();
  props.enseignement.scolEnseignementUes.forEach(ue => {
    if (ue.ue.apcCompetence) {
      competences.add(ue.ue.apcCompetence);
    }
  });
  return Array.from(competences);
});

const motsCles = computed(() => {
  if (props.enseignement.motsCles && props.enseignement.motsCles.length > 1) {
    return props.enseignement.motsCles.split(',');
  } else {
    return [];
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

console.log(props.enseignement);
</script>

<template>
  <div class="py-4 px-8 flex flex-col gap-4 w-full">
    <div>
      <div class="font-bold text-lg">Description :</div>
      <div v-html="formatDescription(enseignement.description)"></div>
      <div v-if="enseignement.description && enseignement.description.length > 500" class="text-primary underline cursor-pointer" @click="toggleDescription">
        {{ isDescriptionExpanded ? 'Voir moins' : 'Voir plus...' }}
      </div>
    </div>
    <div>
      <div class="font-bold text-lg">Mots clés :</div>
      <div class="flex gap-2 flex-wrap"><Tag v-for="motCle in motsCles" class="lowercase">{{ motCle }}</Tag><span v-if="!motsCles || motsCles.length < 1">Aucun mot clé renseigné</span></div>
    </div>

    <Divider/>

    <div class="text-xl font-bold">Structure de l'enseignement dans la formation</div>
    <div class="flex flex-wrap gap-x-24 gap-y-4">
      <div>
        <span class="font-bold text-lg">Diplome : </span>
        <span v-if="props.diplome">{{props.diplome.libelle }}</span>
        <span v-else>Aucun diplome renseigné</span>
      </div>
      <div>
        <span class="font-bold text-lg">Semestre : </span>
        <span v-if="props.semestre">{{props.semestre.libelle }}</span>
        <span v-else>Aucun semestre renseigné</span>
      </div>
      <div><span class="font-bold text-lg">Mutualisée : </span><span v-if="enseignement.mutualisee"><Tag severity="success">Oui</Tag></span><span v-else><Tag>Non</Tag></span></div>
      <div><span class="font-bold text-lg">Suspendue : </span><span v-if="enseignement.suspendu"><Tag severity="danger">Oui</Tag></span><span v-else><Tag severity="success">Non</Tag></span></div>
      <div><span class="font-bold text-lg">Nombre de notes : </span> {{ enseignement.nbNotes ?? 'Aucun nombre de note renseigné' }}</div>
    </div>
    <div class="flex gap-12 w-full">
      <div class="font-bold text-nowrap text-lg">Volumes horaires : </div>
      <DataTable :value="[enseignement.heures]" tableStyle="min-width: 50rem" size="small" class="w-full">
        <ColumnGroup type="header">
          <Row>
            <Column header="CM" :colspan="2" class="!text-nowrap !font-bold !border-r" />
            <Column header="TD" :colspan="2" class="!text-nowrap !font-bold !border-r"/>
            <Column header="TP" :colspan="2" class="!text-nowrap !font-bold !border-r"/>
            <Column header="Autonomie" :colspan="2" class="!text-nowrap !font-bold !border-r"/>
            <Column header="Total" :colspan="2" />
          </Row>
          <Row>
            <Column header="PN" class="!text-nowrap !font-bold !border-b !border-b-black !border-opacity-100" />
            <Column header="IUT" class="!text-nowrap !font-bold !border-b !border-b-black !border-opacity-100 !border-r" />
            <Column header="PN" class="!text-nowrap !font-bold !border-b !border-b-black !border-opacity-100"/>
            <Column header="IUT" class="!text-nowrap !font-bold !border-b !border-b-black !border-opacity-100 !border-r"/>
            <Column header="PN" class="!text-nowrap !font-bold !border-b !border-b-black !border-opacity-100"/>
            <Column header="IUT" class="!text-nowrap !font-bold !border-b !border-b-black !border-opacity-100 !border-r"/>
            <Column header="PN" class="!text-nowrap !font-bold !border-b !border-b-black !border-opacity-100"/>
            <Column header="IUT" class="!text-nowrap !font-bold !border-b !border-b-black !border-opacity-100 !border-r"/>
            <Column header="PN" class="!text-nowrap !font-bold !border-b !border-b-black !border-opacity-100"/>
            <Column header="IUT" class="!text-nowrap !font-bold !border-b !border-b-black !border-opacity-100"/>
          </Row>
        </ColumnGroup>
        <Column field="CM.PN" header="CM PN" class="!text-nowrap" />
        <Column field="CM.IUT" header="CM IUT" class="!text-nowrap !border-r" />
        <Column field="TD.PN" header="TD PN" class="!text-nowrap"/>
        <Column field="TD.IUT" header="TD IUT" class="!text-nowrap !border-r"/>
        <Column field="TP.PN" header="TP PN" class="!text-nowrap"/>
        <Column field="TP.IUT" header="TP IUT" class="!text-nowrap !border-r"/>
        <Column field="Projet.PN" header="Projet PN" class="!text-nowrap"/>
        <Column field="Projet.IUT" header="Projet IUT" class="!text-nowrap !border-r"/>
        <Column field="Total.PN" header="Total PN" />
        <Column field="Total.IUT" header="Total IUT" />
      </DataTable>
    </div>
    <div>
      <div class="font-bold text-lg">Objectif(s) de la matière :</div>
      <div v-if="enseignement.objectifs" v-html="enseignement.objectifs"></div>
      <div v-else>Aucun objectif renseigné</div>
    </div>
  </div>
</template>

<style scoped>
</style>
