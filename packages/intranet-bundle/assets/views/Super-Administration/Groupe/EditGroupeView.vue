<script setup>
import {ref} from "vue";
import {ErrorView} from "@components"

const hasError = ref(false)
</script>

<template>
  <div class="card">
    <div class="card-title mb-8">
      <h1 class="text-2xl font-bold">Édition du groupe</h1>
      <p class="text-muted-color">Modifiez les informations du groupe.</p>
    </div>

    <ErrorView v-if="hasError"/>
    <template v-else>

    </template>
  </div>
</template>

<style scoped>

</style>
