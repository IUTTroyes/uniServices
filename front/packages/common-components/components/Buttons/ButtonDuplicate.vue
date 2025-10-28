<script setup>
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({
  'tooltip': {
    type: String,
    required: true
  },
})

const emit = defineEmits(['confirm-duplicate'])

const confirm = useConfirm()

const showConfirmDialog = () => {
  confirm.require({
    message: 'Êtes-vous sûr de vouloir dupliquer cet élément ?',
    header: 'Confirmation de duplication',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
      label: 'Annuler',
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: 'Confirmer',
      severity: 'warn'
    },
    accept: () => {
      emit('confirm-duplicate')
    }
  })
}
</script>

<template>
  <Button icon="pi pi-copy"
          outlined
          severity="warn"
          rounded
          v-tooltip.bottom="tooltip"
          class="mr-2"
          @click="showConfirmDialog"
  />
</template>

<style scoped>

</style>
