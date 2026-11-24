<script setup>
import { onMounted, ref } from 'vue';
import { HeaderComponent, ListSkeleton } from '@components';
import { messageErreur } from './parcautoDates.js';
import { getParcService } from '../service/parcautoService';

defineProps({ titre: String, icon: String });
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
  <HeaderComponent :titre="titre" :icon="icon" color="blue" description="Gestion du parc automobile" />
  <Message v-if="erreur" severity="error">{{ erreur }}</Message>
  <ListSkeleton v-if="!parc && chargement" />
  <slot v-if="parc" :parc="parc" :actualiser="charger" />
</template>
