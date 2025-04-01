<script setup>
import { ref } from 'vue'

import Step1 from '@/components/Applications/stage_etudiant/Step1.vue'
import Step2 from '@/components/Applications/stage_etudiant/Step2.vue'
import Step3 from '@/components/Applications/stage_etudiant/Step3.vue'
import Step4 from '@/components/Applications/stage_etudiant/Step4.vue'
import Step5 from '@/components/Applications/stage_etudiant/Step5.vue'
import Step6 from '@/components/Applications/stage_etudiant/Step6.vue'
import Step7 from '@/components/Applications/stage_etudiant/Step7.vue'

const valid = ref(true)

const steps = {
  1: {
    label: 'Vous',
    components: Step1
  },
  2: {
    label: 'L\'entreprise',
    components: Step2
  },
  3: {
    label: 'Le signataire',
    components: Step3
  },
  4: {
    label: 'Le tuteur',
    components: Step4
  },
  5: {
    label: 'L\'adresse',
    components: Step5
  },
  6: {
    label: 'Le stage',
    components: Step6
  },
  7: {
    label: 'Récapitulatif',
    components: Step7
  }
}
</script>

<template>
  <div class="card flex justify-start w-full" v-if="valid">
    <Stepper :value="1" class="w-full">
      <StepList>
        <Step v-for="(step, item) in steps" :value="parseInt(item)" :key="item">
          {{ step.label }}
        </Step>
      </StepList>
      <StepPanels>
        <StepPanel v-for="(step, item) in steps" :value="parseInt(item)" v-slot="{ activateCallback }" :key="item">
          <div class="flex flex-col h-48">
            <div class="">
              <component :is="step.components"></component>
            </div>
          </div>
          <div class="flex pt-6 justify-between">
            <Button label="Précédent" :disabled="parseInt(item) === 1" severity="secondary" icon="pi pi-arrow-left"
                    @click="activateCallback(parseInt(item)-1)" />
            <Button label="Suivant" :disabled="parseInt(item) === 7" icon="pi pi-arrow-right" iconPos="right"
                    @click="activateCallback(parseInt(item)+1)" />
          </div>
        </StepPanel>
      </StepPanels>
    </Stepper>
  </div>
  <div v-else>
    Informations sur la période de stage
  </div>
</template>

<style scoped>
</style>
