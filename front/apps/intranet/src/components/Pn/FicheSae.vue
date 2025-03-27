<script setup>
import { ref, defineProps, computed } from 'vue';
import { marked } from 'marked';
import { ApcCompetenceBadge, ApcAcBadge } from '@components';

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
  if (props.enseignement.motsCles && props.enseignement.motsCles.length > 0) {
    return props.enseignement.motsCles.split(',');
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

console.log(props.parcours);
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
      <div class="font-bold text-lg">Objectifs et problématique professionnelle associée :</div>
      <div class="flex gap-2 flex-wrap">{{enseignement.objectif ?? 'Aucun objectif renseigné'}}</div>
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
      <div><span class="font-bold text-lg">Mutualisée : </span><span v-if="enseignement.mutualisee"><Tag severity="success">Oui</Tag></span><span v-else><Tag>Non</Tag></span></div>
      <div><span class="font-bold text-lg">Suspendue : </span><span v-if="enseignement.suspendu"><Tag severity="danger">Oui</Tag></span><span v-else><Tag severity="success">Non</Tag></span></div>
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
    <Divider/>
    <div class="text-xl font-bold">Cet enseignement dans l'APC</div>
    <div class="flex gap-2">
      <span class="font-bold text-lg">Compétence(s) ciblée(s) : </span>
      <ApcCompetenceBadge v-for="ue in uniqueCompetences" :key="ue.nomCourt" :competence="ue" />
      <span v-if="uniqueCompetences.length < 1">Aucune compétence</span>
    </div>
    <div class="flex gap-2 flex-wrap">
      <span class="font-bold text-lg">Apprentissage(s) critique(s) : </span>
      <ApcAcBadge v-for="ac in enseignement.apcApprentissageCritique" :key="ac.code" :ac="ac" v-tooltip.top="`${ac.libelle}`">{{ ac.code }}</ApcAcBadge>
      <span v-if="enseignement.apcApprentissageCritique.length < 1">Aucun apprentissage critique</span>
    </div>
    <div><span class="font-bold text-lg">SAÉ concernée(s) : </span> {{ enseignement.sae ?? 'Aucune SAÉ concernée' }}</div>
    <div><span class="font-bold text-lg">Prérequis : </span> {{ enseignement.preRequis ?? 'Aucune ressource prérequise' }}</div>
    <div><span class="font-bold text-lg">Nombre de notes : </span> {{ enseignement.nbNotes ?? 'Aucun nombre de note renseigné' }}</div>
  </div>
</template>

<style scoped>
</style>
