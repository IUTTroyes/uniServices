<script setup>
import { computed, ref, watch } from 'vue'
import { recopiePlanCoursService } from '@requests'
import { showSuccess, showDanger } from '@helpers/toast'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  targetPlanCoursId: {
    type: Number,
    default: null
  },
  availablePlans: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:visible', 'recopied'])

const isVisible = computed({
  get: () => props.visible,
  set: (val) => emit('update:visible', val)
})

const selectedSourceId = ref(null)
const isSubmitting = ref(false)

watch(() => props.visible, (val) => {
  if (val) {
    selectedSourceId.value = null
  }
})

const planOptions = computed(() => {
  return props.availablePlans
    .filter(p => p.planCours?.id && p.planCours.id !== props.targetPlanCoursId)
    .map(p => ({
      id: p.planCours.id,
      label: `${p.enseignement.codeEnseignement} - ${p.enseignement.libelle} (${p.planCours.etatLibelle})`
    }))
})

const handleRecopie = async () => {
  if (!selectedSourceId.value || !props.targetPlanCoursId) return

  isSubmitting.value = true
  try {
    await recopiePlanCoursService(props.targetPlanCoursId, selectedSourceId.value, false)
    showSuccess('Le plan de cours a été recopié avec succès.')
    isVisible.value = false
    emit('recopied')
  } catch (error) {
    showDanger('Erreur lors de la recopie du plan de cours.')
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <Dialog
    v-model:visible="isVisible"
    modal
    :style="{ width: '500px' }"
    header="Recopier un plan de cours"
    class="p-fluid"
  >
    <div class="flex flex-col gap-4 py-2">
      <Message severity="info" :closable="false">
        La recopie dupliquera les objectifs, la description, les volumes horaires prévus, les modalités d'évaluation et l'ensemble des séquences d'apprentissage vers votre plan de cours actuel.
      </Message>

      <div class="flex flex-col gap-2">
        <label for="sourcePlan" class="font-semibold text-sm">Sélectionnez le plan de cours source :</label>
        <Select
          id="sourcePlan"
          v-model="selectedSourceId"
          :options="planOptions"
          optionLabel="label"
          optionValue="id"
          placeholder="Choisir un module à copier..."
          filter
          class="w-full"
        />
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="Annuler" severity="secondary" @click="isVisible = false" />
        <Button
          label="Recopier ce plan"
          icon="pi pi-copy"
          severity="primary"
          :loading="isSubmitting"
          :disabled="!selectedSourceId"
          @click={handleRecopie}
        />
      </div>
    </template>
  </Dialog>
</template>
