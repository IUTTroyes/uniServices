<script setup>
import { ref } from 'vue';
import { formatDateFr } from '@helpers/date';
import { showDanger, showSuccess } from '@helpers/toast';
import { HeaderComponent, EmptyState, Card, Alert, LoadingBar } from '@components';
import SelectButton from 'primevue/selectbutton';
import ToggleSwitch from 'primevue/toggleswitch';
import notificationService from '../requests/notificationService';
import { messagesErreur } from '../requests/portfolioService';
import { useChargement } from '../composables/useChargement';

const enregistrement = ref(false);
const { donnees: prefs, chargement, erreur } = useChargement(() => notificationService.preferences(), {
  messageParDefaut: 'Impossible de charger vos préférences.',
});

/** Enregistre un changement (partiel) puis reflète l'état renvoyé par le serveur. */
const enregistrer = async (changement) => {
  enregistrement.value = true;
  try {
    const { data } = await notificationService.modifier(changement);
    prefs.value = data;
    showSuccess(undefined, 'Préférences enregistrées', 2000);
  } catch (e) {
    showDanger(messagesErreur(e).join(' '), 'Enregistrement impossible', 6000);
    prefs.value = (await notificationService.preferences()).data; // revient à l'état réel
  } finally {
    enregistrement.value = false;
  }
};

const changerMode = (type, mode) => { if (mode && mode !== type.mode) enregistrer({ reglages: { [type.cle]: mode } }); };
</script>

<template>
  <HeaderComponent
    icon="pi pi-bell"
    titre="Notifications"
    description="Choisissez ce que vous recevez par e-mail, et quand : tout de suite, regroupé dans un résumé, ou jamais."
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  />

  <LoadingBar v-if="chargement" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Erreur" :description="erreur" />

  <div v-else class="grid gap-4 xl:grid-cols-3 items-start">
    <div class="xl:col-span-2 flex flex-col gap-4">
      <Card bare tag="section" class="p-4 flex flex-col gap-4">
        <label class="flex items-center gap-3 cursor-pointer">
          <ToggleSwitch :model-value="prefs.actif" :disabled="enregistrement" input-id="notif-actif" @update:model-value="enregistrer({ actif: $event })" />
          <span class="flex flex-col">
            <span class="font-semibold">Recevoir des e-mails UniFolio</span>
            <span class="text-sm text-muted-color">Désactivé : aucun e-mail ne vous est envoyé, ni immédiat ni résumé. Vous retrouvez tout dans l'application.</span>
          </span>
        </label>
        <Alert v-if="!prefs.actif" icon-native :closable="false" severity="warn">Les notifications par e-mail sont désactivées.</Alert>
      </Card>

      <Card bare tag="section" class="p-4 flex flex-col gap-4" :class="{ 'opacity-60': !prefs.actif }">
        <h3 class="text-base font-semibold m-0">Que voulez-vous recevoir ?</h3>
        <div v-for="t in prefs.types" :key="t.cle" class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-100 pb-4 last:border-0 last:pb-0">
          <div class="flex flex-col min-w-60 flex-1">
            <span class="font-medium">{{ t.libelle }}</span>
            <span class="text-sm text-muted-color">{{ t.description }}</span>
          </div>
          <SelectButton
            :model-value="t.mode"
            :options="t.modes"
            option-label="libelle"
            option-value="value"
            :allow-empty="false"
            :disabled="!prefs.actif || enregistrement"
            :aria-label="t.libelle"
            @update:model-value="changerMode(t, $event)"
          />
        </div>
      </Card>
    </div>

    <Card bare tag="aside" class="p-4 flex flex-col gap-3" aria-label="Résumé">
      <h3 class="text-base font-semibold m-0"><i class="pi pi-inbox mr-1" /> Le résumé</h3>
      <p class="m-0 text-sm text-muted-color">
        Les événements marqués « Dans le résumé » sont regroupés en <strong>un seul e-mail</strong>. Plusieurs événements identiques
        (par exemple des commentaires sur la même trace) n'y forment qu'une ligne.
      </p>
      <div class="flex flex-col gap-1">
        <span class="text-sm font-medium">Rythme du résumé</span>
        <SelectButton
          :model-value="prefs.frequence.value"
          :options="prefs.frequences"
          option-label="libelle"
          option-value="value"
          :allow-empty="false"
          :disabled="!prefs.actif || enregistrement"
          aria-label="Rythme du résumé"
          @update:model-value="enregistrer({ frequence: $event })"
        />
      </div>
      <p class="m-0 text-sm">
        <strong>{{ prefs.enAttente }}</strong> événement(s) en attente du prochain résumé.
        <template v-if="prefs.dernierResume"><br /><span class="text-muted-color">Dernier résumé envoyé le {{ formatDateFr(prefs.dernierResume, 'moyenneHeure') }}.</span></template>
      </p>
    </Card>
  </div>
</template>
