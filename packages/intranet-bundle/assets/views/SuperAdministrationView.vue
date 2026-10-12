<script setup>
import SuperAdminBlocDepartement from "@/components/SuperAdministration/blocs_super_admin/SuperAdminBlocDepartement.vue";
import SuperAdminBlocBac from "@/components/SuperAdministration/blocs_super_admin/SuperAdminBlocBac.vue";
import SuperAdminBlocTypeDiplome from "@/components/SuperAdministration/blocs_super_admin/SuperAdminBlocTypeDiplome.vue";
import SuperAdminBlocCalendrier from "@/components/SuperAdministration/blocs_super_admin/SuperAdminBlocCalendrier.vue";
import SuperAdminBlocTypeHrs from "@/components/SuperAdministration/blocs_super_admin/SuperAdminBlocTypeHrs.vue";
import { HeaderComponent, Card } from "@components";
</script>

<template>
  <HeaderComponent
    icon="pi pi-cog"
    color="blue"
    titre="Super Administration"
    description="Gestion globale des référentiels et paramètres de l'application"
    :show-back="false"
  />

  <Card>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <super-admin-bloc-departement />
      <super-admin-bloc-bac />
      <super-admin-bloc-type-diplome />
      <super-admin-bloc-calendrier />
      <super-admin-bloc-type-hrs />
    </div>
  </Card>

  <RouterView />
</template>

<style scoped>
</style>


