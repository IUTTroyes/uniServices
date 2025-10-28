<script setup>
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({
  'tooltip': {
    type: String,
    required: true
  },
})

const emit = defineEmits(['confirm-save'])

const confirm = useConfirm()

const showConfirmDialog = () => {
  confirm.require({
    message: 'Êtes-vous sûr de vouloir enregistrer les modifications de cet élément ?',
    header: 'Confirmation d\'enregistrement',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: {
      label: 'Annuler',
      severity: 'secondary',
      outlined: true
    },
    acceptProps: {
      label: 'Confirmer',
      severity: 'danger'
    },
    accept: () => {
      emit('confirm-save')
    }
  })
}
</script>

<template>
  <Button icon="pi pi-save"
          outlined
          severity="success"
          rounded
          v-tooltip.bottom="tooltip"
          class="mr-2"
          @click="showConfirmDialog"
           />
</template>

<style scoped>

</style>
