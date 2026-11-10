<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { HeaderComponent } from '@components';
import Tag from 'primevue/tag';
import EvaluationLot from '../components/evaluation/EvaluationLot.vue';

const route = useRoute();
const lot = ref(null);

const portee = computed(() => ({ type: 'bilans', campagne: route.params.campagne, etudiant: route.params.etudiant }));
const retour = computed(() => `/unifolio/evaluations/etudiants/${route.params.campagne}/${route.params.etudiant}`);
</script>

<template>
  <HeaderComponent
    icon="pi pi-check-square"
    :titre="lot ? `Bilans de ${lot.etudiant.nom}` : 'Bilans de l\'année'"
    :description="lot?.campagne ? lot.campagne.libelle : 'Évaluez en une fois tous les éléments de l\'année.'"
    :show-back="true"
    :back-url="retour"
    color="emerald"
  >
    <template #actions>
      <Tag v-if="lot?.campagne" :value="lot.campagne.phase.libelle" :severity="lot.campagne.phase.badge" />
    </template>
  </HeaderComponent>

  <EvaluationLot :portee="portee" @charge="(d) => (lot = d)" />
</template>
