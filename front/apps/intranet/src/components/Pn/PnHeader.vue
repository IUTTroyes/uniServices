<script setup>
import { SimpleSkeleton } from "@components"

defineProps({
  isLoading: {
    type: Boolean,
    required: true
  },
  diplomes: {
    type: Array,
    required: true
  },
  selectedDiplome: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['changeDiplome'])
</script>

<template>
  <SimpleSkeleton v-if="isLoading" class="w-1/2" />
  <div v-else>
    <h2 class="text-2xl font-bold">Programmes pédagogiques nationaux</h2>
    <Divider/>
    <Tabs :value="selectedDiplome?.id || diplomes[0]?.id" scrollable>
      <TabList>
        <Tab v-for="diplome in diplomes" 
             :key="diplome.libelle" 
             :value="diplome.id" 
             @click="emit('changeDiplome', diplome)">
          <span>{{ diplome.typeDiplome?.sigle }}</span> | <span>{{ diplome.sigle }}</span>
        </Tab>
      </TabList>
    </Tabs>
  </div>
</template> 