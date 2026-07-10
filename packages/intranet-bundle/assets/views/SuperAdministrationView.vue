<script setup>
import SuperAdminBlocDepartement from "@/components/SuperAdministration/blocs_super_admin/SuperAdminBlocDepartement.vue";
import { HeaderComponent } from "@components";
</script>

<template>
  <HeaderComponent
      icon="pi pi-cog"
      titre="Super Administration"
      description="Gestion de la super administration"
  />
  <div class="card">
    <div class="card-body flex flex-col gap-10">
      <div class="flex flex-row justify-between gap-10">
        <super-admin-bloc-departement class="w-full"/>
      </div>
    </div>
  </div>

  <RouterView/>
</template>

<style scoped>

</style>

