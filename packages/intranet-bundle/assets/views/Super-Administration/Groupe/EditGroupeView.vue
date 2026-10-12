<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { ErrorView, HeaderComponent, Card } from "@components";

const router = useRouter();
const hasError = ref(false);
</script>

<template>
  <HeaderComponent
    icon="pi pi-users"
    color="blue"
    titre="Édition du groupe"
    description="Modifiez les informations et la structure du groupe."
    :show-back="true"
    back-url="/intranet/super-administration"
  />

  <Card title="Paramètres du groupe" icon="pi pi-pencil" color="blue">
    <ErrorView v-if="hasError" />
    <template v-else>
      <div class="p-4 text-muted-color">
        Édition des détails du groupe en cours de configuration.
      </div>
    </template>
  </Card>
</template>

<style scoped>
</style>

