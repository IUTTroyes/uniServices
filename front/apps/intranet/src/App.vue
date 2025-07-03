<script setup>
import { initializeToast } from '@helpers/toast.js'
import { initializeAppData } from '@requests/initializeData'
import { onMounted } from 'vue'

initializeToast()

onMounted(async () => {
  await initializeAppData()
})
</script>

<template>
  <ConfirmDialog></ConfirmDialog>
  <router-view />
</template>

<style scoped></style>
