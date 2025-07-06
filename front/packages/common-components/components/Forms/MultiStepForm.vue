<script setup>
import DynamicForm from '@components/components/Forms/DynamicForm.vue';
import { ref, computed } from 'vue';

const props = defineProps({
  steps: Array, // [{ label, formConfig, formOptions, initialValues }]
});

const emit = defineEmits(['submit']);

const currentStep = ref(0);

const onNext = () => {
  if (currentStep.value < props.steps.length - 1) currentStep.value++;
};
const onPrev = () => {
  if (currentStep.value > 0) currentStep.value--;
};
const onSubmit = (values) => {
  emit('submit', values);
};
</script>

<template>
    <Stepper value="1" class="basis-[50rem]">
      <StepList>
        <Step :value="(index+1).toString()" v-for="(step, index) in steps">{{ step.label }}</Step>
      </StepList>
      <StepPanels>
        <StepPanel v-slot="{ activateCallback }" :value="(index+1).toString()" v-for="(step, index) in steps">
            <DynamicForm
                :form-config="step.formConfig"
                :form-options="step.formOptions"
                :initial-values="step.initialValues"
            ></DynamicForm>
          <div class="flex pt-6 justify-end">
            <Button label="Next" icon="pi pi-arrow-right" iconPos="right" @click="activateCallback('2')" />
          </div>
        </StepPanel>
<!--        <StepPanel v-slot="{ activateCallback }" value="2">-->
<!--          <div class="flex flex-col h-48">-->
<!--            <div class="border-2 border-dashed border-surface-200 dark:border-surface-700 rounded bg-surface-50 dark:bg-surface-950 flex-auto flex justify-center items-center font-medium">Content II</div>-->
<!--          </div>-->
<!--          <div class="flex pt-6 justify-between">-->
<!--            <Button label="Back" severity="secondary" icon="pi pi-arrow-left" @click="activateCallback('1')" />-->
<!--            <Button label="Next" icon="pi pi-arrow-right" iconPos="right" @click="activateCallback('3')" />-->
<!--          </div>-->
<!--        </StepPanel>-->
<!--        <StepPanel v-slot="{ activateCallback }" value="3">-->
<!--          <div class="flex flex-col h-48">-->
<!--            <div class="border-2 border-dashed border-surface-200 dark:border-surface-700 rounded bg-surface-50 dark:bg-surface-950 flex-auto flex justify-center items-center font-medium">Content III</div>-->
<!--          </div>-->
<!--          <div class="pt-6">-->
<!--            <Button label="Back" severity="secondary" icon="pi pi-arrow-left" @click="activateCallback('2')" />-->
<!--          </div>-->
<!--        </StepPanel>-->
      </StepPanels>
    </Stepper>
</template>


