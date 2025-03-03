<script setup>
import { useConfirm } from 'primevue/useconfirm'

const props = defineProps({
  'tooltip': {
    type: String,
    required: true
  },
})

const emit = defineEmits(['confirm-delete'])

const confirm = useConfirm()

const showConfirmDialog = () => {
  console.log('showConfirmDialog')
  confirm.require({
    message: 'Êtes-vous sûr de vouloir supprimer cet élément ?',
    header: 'Confirmation de suppression',
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
      emit('confirm-delete')
    }
  })
}
</script>

<template>
  <Button icon="pi pi-trash"
          outlined
          severity="danger"
          rounded
          v-tooltip.bottom="tooltip"
          class="mr-2"
          @click="showConfirmDialog"
           />
</template>

<style scoped>

</style>
