<script setup>
import SuperAdminBlocAnneeUniv from "@/components/SuperAdministration/blocs_super_admin/SuperAdminBlocAnneeUniv.vue";
import SuperAdminBlocDepartement from "@/components/SuperAdministration/blocs_super_admin/SuperAdminBlocDepartement.vue";
</script>

<template>
  <div class="card">
    <div class="card-body flex flex-col gap-10">
      <super-admin-bloc-annee-univ class="w-full"/>
      <div class="flex flex-row justify-between gap-10">
        <super-admin-bloc-departement class="w-full"/>
        <super-admin-bloc-annee-univ class="w-full"/>
      </div>
    </div>
  </div>

  <RouterView/>
</template>

<style scoped>

</style>

