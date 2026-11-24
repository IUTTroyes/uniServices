<script setup>
import { onMounted, ref } from 'vue';
import { ActionButtonVertical, HeaderComponent, ListSkeleton, PermissionGuard } from '@components';
import GestionParcauto from '../components/GestionParcauto.vue';
import PilotageParcauto from '../components/PilotageParcauto.vue';
import PlanningParcauto from '../components/PlanningParcauto.vue';
import { messageErreur } from '../components/parcautoDates';
import { getParcService } from '../service/parcautoService';

defineProps({ page: String, titre: String, icon: String });
const parc = ref(null);
const erreur = ref('');
const chargement = ref(false);

async function charger() {
  chargement.value = true;
  erreur.value = '';
  try {
    parc.value = await getParcService();
  } catch (e) {
    erreur.value = messageErreur(e);
  } finally {
    chargement.value = false;
  }
}

onMounted(charger);
</script>

<template>
  <HeaderComponent :titre="titre" :icon="icon" color="blue">
    <template #actions>
      <ActionButtonVertical label="Actualiser le parc" icon="pi pi-refresh" severity="secondary" :disabled="chargement" @click="charger" />
    </template>
  </HeaderComponent>
  <Message v-if="erreur" severity="error">{{ erreur }}</Message>
  <ListSkeleton v-if="!parc && chargement" />
  <template v-if="parc">
    <PlanningParcauto v-if="page === 'planning'" :vehicules="parc.vehicules" :gestion="parc.gestion" @actualiser="charger" />
    <GestionParcauto v-else-if="page === 'vehicules'" v-bind="parc" @actualiser="charger" />
    <PermissionGuard v-else :permission="parc.gestion && 'canManageParcauto'" show-fallback>
      <PilotageParcauto :vehicules="parc.vehicules" />
      <template #fallback>
        <Message severity="warn">L’accès au pilotage est réservé aux gestionnaires du parc.</Message>
      </template>
    </PermissionGuard>
  </template>
</template>
