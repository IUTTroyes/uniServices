<script setup>
import {ref, onMounted, computed} from "vue";
import { ValidatedInput, validationRules, ErrorView, PermissionGuard, ListSkeleton, Access } from "@components";

const hasError = ref(false)
</script>

<template>
  <div class="card">
    <div class="card-title mb-8">
      <h1 class="text-2xl font-bold">Nouveau département</h1>
      <p class="text-muted-color">Créez un nouveau département.</p>
    </div>

    <ErrorView v-if="hasError"/>
    <template v-else>

    </template>
  </div>
</template>

<style scoped>

</style>
