<script setup>
import { initializeToast } from '@helpers/toast.js'

initializeToast()
</script>

<template>
  <ConfirmDialog></ConfirmDialog>
  <router-view/>
</template>

<style scoped></style>
