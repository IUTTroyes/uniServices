<script setup>
import { ref, defineProps, computed } from 'vue';
import { marked } from 'marked';
import {ApcCompetenceBadge, ApcAcBadge} from '@components';

const props = defineProps({
  enseignement: {
    type: Object,
    required: true
  },
})

const heures = props.enseignement.heures;
heures.Total = {
  PN: heures.CM.PN + heures.TD.PN + heures.TP.PN + heures.Projet.PN,
  IUT: heures.CM.IUT + heures.TD.IUT + heures.TP.IUT + heures.Projet.IUT
};

const uniqueCompetences = computed(() => {
  const competences = new Set();
  props.enseignement.scolEnseignementUes.forEach(ue => {
    if (ue.ue.apcCompetence?.nomCourt) {
      competences.add(ue.ue.apcCompetence);
    }
  });
  return Array.from(competences);
});

// transformer les enseignement.motsCles en tableau de mots clés séparateur virgule
const motsCles = computed(() => {
  return props.enseignement.motsCles.split(',');
});

const formatDescription = (description) => {
  if (!description) {
    return 'Aucune description disponible';
  }
  return marked(description);
};

console.log(props.enseignement)
</script>

<template>
  <div class="py-4 px-8 flex flex-col gap-4 w-full">
    <div>
      <div v-html="formatDescription(enseignement.description) ?? 'Aucune description disponible'"></div>
      <div class="text-primary underline">Voir plus...</div>
    </div>
    <div>
      <div class="font-bold">Mots clés :</div>
      <div class="flex gap-2"><Tag v-for="motCle in motsCles" class="lowercase">{{motCle}}</Tag></div>
    </div>

    <Divider/>

    <div class="text-xl font-bold">Structure de l'enseignement dans la formation</div>
    <div class="flex gap-24">
      <div><span class="font-bold">Parcours : </span> <span class="underline">Administration et Justice (FC)</span></div>
      <div><span class="font-bold">Semestre(s) : </span>S1</div>
      <div><span class="font-bold">Suspendue : </span><span v-if="enseignement.suspendu"><Tag severity="danger">Oui</Tag></span><span v-else><Tag severity="success">Non</Tag></span></div>
    </div>
    <div class="flex gap-12 w-full">
      <div class="font-bold text-nowrap">Volumes horaires : </div>
      <DataTable :value="[enseignement.heures]" tableStyle="min-width: 50rem" size="small" class="w-full" >
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
      <span class="font-bold">Compétence(s) ciblée(s) : </span>
      <ApcCompetenceBadge v-for="ue in uniqueCompetences" :key="ue.nomCourt" :competence="ue" />
      <span v-if="uniqueCompetences.length < 1">Aucune compétence</span>
    </div>
    <div class="flex gap-6 flex-wrap">
      <span class="font-bold">Apprentissage(s) critique(s) : </span>
      <ApcAcBadge v-for="ac in enseignement.apcApprentissageCritique" :key="ac.code" :ac="ac">{{ac.code}}</ApcAcBadge>
      <span v-if="enseignement.apcApprentissageCritique.length < 1">Aucun apprentissage critique</span>
    </div>
    <div><span class="font-bold">SAÉ concernée(s) : </span> {{enseignement.sae ?? 'Aucune SAÉ concernée'}}</div>
    <div><span class="font-bold">Prérequis : </span> {{enseignement.preRequis ?? 'Aucune ressource prérequise'}}</div>
  </div>


  <!--  <table>-->
  <!--    <tr class="border">-->
  <!--      <td class="font-bold p-4 border">Parcours</td>-->
  <!--      <td class="p-4">{{enseignement.parcours}}</td>-->
  <!--    </tr>-->
  <!--    <tr class="border">-->
  <!--      <td class="font-bold p-4 border">Semestres</td>-->
  <!--      <td class="p-4">{{enseignement.semestres}}</td>-->
  <!--    </tr>-->
  <!--    <tr class="border">-->
  <!--      <td class="font-bold p-4 border">Suspendue</td>-->
  <!--      <td class="p-4">{{enseignement.suspendu}}</td>-->
  <!--    </tr>-->
  <!--    <tr class="border">-->
  <!--      <td class="font-bold p-4 border">Compétence(s) ciblée(s)</td>-->
  <!--      <td class="p-4">{{enseignement.competences}}</td>-->
  <!--    </tr>-->
  <!--    <tr class="border">-->
  <!--      <td class="font-bold p-4 border">Apprentissages critiques</td>-->
  <!--      <td class="p-4">-->
  <!--        <span v-for="ac in enseignement.apcApprentissageCritique">{{ac.code}}</span>-->
  <!--      </td>-->
  <!--    </tr>-->
  <!--    <tr class="border">-->
  <!--      <td class="font-bold p-4 border">Saé concernée(s)</td>-->
  <!--      <td class="p-4">{{enseignement.sae}}</td>-->
  <!--    </tr>-->
  <!--    <tr class="border">-->
  <!--      <td class="font-bold p-4 border">Pré-requis</td>-->
  <!--      <td class="p-4">{{enseignement.preRequis ?? 'Aucune ressource prérequise'}}</td>-->
  <!--    </tr>-->
  <!--    <tr class="border">-->
  <!--      <td class="font-bold p-4 border">Description</td>-->
  <!--      <td class="border py-4 px-6" v-html="formatDescription(enseignement.description) ?? null"></td>-->
  <!--    </tr>-->
  <!--    <tr class="border">-->
  <!--      <td class="font-bold p-4 border">Mots clés</td>-->
  <!--      <td class="p-4">{{enseignement.motsCles}}</td>-->
  <!--    </tr>-->
  <!--    <tr class="border">-->
  <!--      <td class="font-bold p-4 border">Heures</td>-->
  <!--      <td class="p-4">-->
  <!--        <DataTable :value="[enseignement.heures]" tableStyle="min-width: 50rem" size="small">-->
  <!--          <ColumnGroup type="header">-->
  <!--            <Row>-->
  <!--              <Column header="CM" :colspan="2" class="!bg-purple-400 !bg-opacity-20 !text-nowrap !font-bold" />-->
  <!--              <Column header="TD" :colspan="2" class="!bg-green-400 !bg-opacity-20 !text-nowrap !font-bold"/>-->
  <!--              <Column header="TP" :colspan="2" class="!bg-amber-400 !bg-opacity-20 !text-nowrap !font-bold"/>-->
  <!--              <Column header="Projet" :colspan="2" class="!bg-blue-400 !bg-opacity-20 !text-nowrap !font-bold"/>-->
  <!--              <Column header="Total" :colspan="2" />-->
  <!--            </Row>-->
  <!--            <Row>-->
  <!--              <Column header="PN" class="!bg-purple-400 !bg-opacity-20 !text-nowrap !font-bold" />-->
  <!--              <Column header="IUT" class="!bg-purple-400 !bg-opacity-20 !text-nowrap !font-bold" />-->
  <!--              <Column header="PN" class="!bg-green-400 !bg-opacity-20 !text-nowrap !font-bold"/>-->
  <!--              <Column header="IUT" class="!bg-green-400 !bg-opacity-20 !text-nowrap !font-bold"/>-->
  <!--              <Column header="PN" class="!bg-amber-400 !bg-opacity-20 !text-nowrap !font-bold"/>-->
  <!--              <Column header="IUT" class="!bg-amber-400 !bg-opacity-20 !text-nowrap !font-bold"/>-->
  <!--              <Column header="PN" class="!bg-blue-400 !bg-opacity-20 !text-nowrap !font-bold"/>-->
  <!--              <Column header="IUT" class="!bg-blue-400 !bg-opacity-20 !text-nowrap !font-bold"/>-->
  <!--              <Column header="PN" />-->
  <!--              <Column header="IUT" />-->
  <!--            </Row>-->
  <!--          </ColumnGroup>-->
  <!--          <Column field="CM.PN" header="CM PN" class="!bg-purple-400 !bg-opacity-20 !text-nowrap" />-->
  <!--          <Column field="CM.IUT" header="CM IUT" class="!bg-purple-400 !bg-opacity-20 !text-nowrap" />-->
  <!--          <Column field="TD.PN" header="TD PN" class="!bg-green-400 !bg-opacity-20"/>-->
  <!--          <Column field="TD.IUT" header="TD IUT" class="!bg-green-400 !bg-opacity-20"/>-->
  <!--          <Column field="TP.PN" header="TP PN" class="!bg-amber-400 !bg-opacity-20 !text-nowrap"/>-->
  <!--          <Column field="TP.IUT" header="TP IUT" class="!bg-amber-400 !bg-opacity-20 !text-nowrap"/>-->
  <!--          <Column field="Projet.PN" header="Projet PN" class="!bg-blue-400 !bg-opacity-20 !text-nowrap"/>-->
  <!--          <Column field="Projet.IUT" header="Projet IUT" class="!bg-blue-400 !bg-opacity-20 !text-nowrap"/>-->
  <!--          <Column field="Total.PN" header="Total PN" />-->
  <!--          <Column field="Total.IUT" header="Total IUT" />-->
  <!--        </DataTable>-->
  <!--      </td>-->
  <!--    </tr>-->
  <!--    <tr class="border">-->
  <!--      <td class="font-bold p-4 border">Nb notes</td>-->
  <!--      <td class="p-4">{{enseignement.nbNotes}}</td>-->
  <!--    </tr>-->
  <!--  </table>-->

  <!--  <ul>-->
  <!--    <li>Parcours </li>-->
  <!--    <li>Semestres</li>-->
  <!--    <li>Suspendue = {{enseignement.suspendu}}</li>-->
  <!--    <li>Compétence(s) ciblée(s) = </li>-->
  <!--    <li class="flex gap-4">Apprentissages critiques = <span v-for="ac in enseignement.apcApprentissageCritique">{{ac.code}}</span></li>-->
  <!--    <li>Saé cocnernée(s) (link)</li>-->
  <!--    <li>Pré-requis = {{enseignement.preRequis ?? 'Aucune ressource prérequise'}}</li>-->
  <!--    <li>Description = <pre>{{enseignement.description ?? 'Aucune description disponible'}}</pre></li>-->
  <!--    <li>Mots clés = {{enseignement.motsCles}}</li>-->
  <!--    <li>Heures = {{enseignement.heures}}</li>-->
  <!--    <li>Nb notes = {{enseignement.nbNotes}}</li>-->
  <!--  </ul>-->
</template>

<style scoped>

</style>
