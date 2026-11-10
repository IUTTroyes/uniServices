<script setup>
import { onMounted, ref, nextTick } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import { ErrorState, ListSkeleton, EmptyState } from '@components';
import CustomDataTable from '@components/components/CustomDataTable.vue';
import {
  getServicesListeService,
  createServiceService,
  updateServiceService,
  deleteServiceService,
} from '@requests';

/**
 * Services de l'établissement (Informatique, Patrimoine…), à côté des départements : imputation des demandes d'achat,
 * helpdesk… Réservé au super administrateur (l'API le vérifie aussi).
 */
const LIBELLE_MAX = 50;
const toast = useToast();

const services = ref([]);
const chargement = ref(true);
const erreurChargement = ref(false);

const dialogue = ref(false);
const edite = ref(null); // null : création
const libelle = ref('');
const erreur = ref('');
const enregistrement = ref(false);
const champ = ref(null);

/** Message lisible d'une erreur API (violations de validation, conflit…). */
function messageErreur(e, defaut) {
  const data = e?.response?.data;
  if (Array.isArray(data?.violations) && data.violations.length) {
    return [...new Set(data.violations.map(v => v.message).filter(Boolean))].join('\n');
  }
  return data?.detail || data?.['hydra:description'] || data?.message || defaut;
}

async function charger() {
  chargement.value = true;
  erreurChargement.value = false;
  try {
    services.value = await getServicesListeService();
  } catch {
    erreurChargement.value = true;
  } finally {
    chargement.value = false;
  }
}

async function ouvrir(service = null) {
  edite.value = service;
  libelle.value = service?.libelle ?? '';
  erreur.value = '';
  dialogue.value = true;
  await nextTick();
  champ.value?.$el?.focus?.();
}

async function enregistrer() {
  const valeur = libelle.value.trim();
  if (!valeur) {
    erreur.value = 'Le libellé est obligatoire.';
    return;
  }
  enregistrement.value = true;
  erreur.value = '';
  try {
    if (edite.value) {
      await updateServiceService(edite.value.id, valeur);
    } else {
      await createServiceService(valeur);
    }
    toast.add({ severity: 'success', summary: edite.value ? 'Service modifié' : 'Service créé', detail: valeur, life: 2500 });
    dialogue.value = false;
    await charger();
  } catch (e) {
    erreur.value = messageErreur(e, 'Enregistrement impossible.');
  } finally {
    enregistrement.value = false;
  }
}

async function supprimer(service) {
  try {
    await deleteServiceService(service.id);
    toast.add({ severity: 'success', summary: 'Service supprimé', detail: service.libelle, life: 2500 });
    await charger();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Suppression impossible', detail: messageErreur(e, 'Le service n\'a pas pu être supprimé.'), life: 6000 });
  }
}

onMounted(charger);
</script>

<template>
  <div class="card">
    <div class="card-header">
      <h2 class="text-2xl! mb-0! font-bold">Services de l'établissement</h2>
      <em>Services (Informatique, Patrimoine…) proposés, à côté des départements, pour imputer une demande d'achat.</em>
    </div>

    <div class="card-body">
      <ErrorState v-if="erreurChargement" message="Impossible de charger les services." />
      <ListSkeleton v-else-if="chargement" />
      <CustomDataTable
        v-else
        :value="services"
        :columns="[{ field: 'libelle', header: 'Libellé', sortable: true }]"
        :action-add="{ handler: () => ouvrir() }"
        :actions="[{ type: 'edit', handler: ouvrir }, { type: 'delete', handler: supprimer }]"
        :rows-per-page-options="[30, 60, 120]"
        :show-footer="false"
      >
        <template #empty>
          <EmptyState compact title="Aucun service" description="Ajoutez les services de l'établissement." />
        </template>
      </CustomDataTable>
    </div>

    <Dialog v-model:visible="dialogue" modal :header="edite ? 'Modifier le service' : 'Nouveau service'" :style="{ width: '28rem' }">
      <form class="flex flex-col gap-3" @submit.prevent="enregistrer">
        <label for="service-libelle" class="font-medium">Libellé</label>
        <InputText id="service-libelle" ref="champ" v-model="libelle" :maxlength="LIBELLE_MAX" :invalid="!!erreur" autocomplete="off" />
        <small class="text-muted-color">{{ libelle.length }} / {{ LIBELLE_MAX }} caractères</small>
        <Message v-if="erreur" severity="error" :closable="false" class="whitespace-pre-line">{{ erreur }}</Message>
        <div class="flex justify-end gap-2 mt-2">
          <Button type="button" label="Annuler" severity="secondary" outlined @click="dialogue = false" />
          <Button type="submit" :label="edite ? 'Enregistrer' : 'Créer'" icon="pi pi-check" :loading="enregistrement" />
        </div>
      </form>
    </Dialog>
  </div>
</template>
