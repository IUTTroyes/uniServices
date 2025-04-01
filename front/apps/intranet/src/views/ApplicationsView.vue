<script setup>
import AppBlocStage from '../components/Applications/blocs_app/AppBlocStage.vue'
</script>

<template>
  <div class="card">
    <div class="card-body flex flex-col gap-10">
      <AppBlocStage class="w-full"/>
    </div>
  </div>

  <RouterView/>

</template>

<style scoped>

</style>
