<script setup>
import { initializeToast } from '@helpers/toast.js'
import Toast from 'primevue/toast'

initializeToast()
</script>

<template>
  <Toast />
  <ConfirmDialog></ConfirmDialog>
  <router-view/>
</template>

<style scoped></style>
