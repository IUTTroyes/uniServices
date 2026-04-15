<script setup lang="ts">
import {onMounted, ref} from "vue";
import ApcCompetence from "@/components/Pn/ApcCompetence.vue";
import {getReferentielCompetencesComplet} from '@requests'

interface Props {
  referentiel: object;
}

const isLoadingReferentiel = ref(true);
const props = defineProps<Props>();
const competences = ref([]);

onMounted(async () => {
  console.log('charger le referentiel')
  isLoadingReferentiel.value = true;
  competences.value = await getReferentielCompetencesComplet(props.referentiel.id);
  isLoadingReferentiel.value = false;
})
</script>

<template>
  <Tabs value="1" scrollable>
    <TabList>
      <Tab value="1">
        <span>Référentiel de compétences complet</span>
      </Tab>
      <Tab value="2">
        <span>Référentiel par parcours</span>
      </Tab>
    </TabList>

    <TabPanels v-if="!isLoadingReferentiel">
      <TabPanel value="1">
        <Tabs :value="`c${competences[0].id}`" scrollable>
          <TabList>
            <Tab :value="`c${competence.id}`" v-for="competence in competences" :key="competence.id">
              <span>{{ competence.nomCourt }}</span>
            </Tab>
          </TabList>
          <TabPanel :value="`c${competence.id}`" v-for="competence in competences" :key="competence.id">
            <ApcCompetence :competence="competence" />
          </TabPanel>
        </Tabs>
      </TabPanel>
      <TabPanel value="2">
        Parcours
      </TabPanel>
    </TabPanels>
  </Tabs>
</template>

<style scoped>

</style>
