<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import Popover from 'primevue/popover';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { formatDateFr } from '@helpers/date';
import { cheminRouteur, useNotificationStore } from '@stores/notificationStore';

/**
 * Cloche de notifications : pastille de non-lues, panneau des dernières notifications de toute l'application
 * (chaque package alimente la même liste via App\Notification\NotificationService, voir docs/NOTIFICATIONS_SYSTEM.md).
 */
const INTERVALLE = 60_000;

const router = useRouter();
const store = useNotificationStore();
const panneau = ref(null);
let minuteur = null;

const rafraichir = () => { if (document.visibilityState === 'visible') store.compter(); };

onMounted(() => {
  store.compter();
  minuteur = setInterval(rafraichir, INTERVALLE);
  document.addEventListener('visibilitychange', rafraichir);
});
onBeforeUnmount(() => {
  clearInterval(minuteur);
  document.removeEventListener('visibilitychange', rafraichir);
});

const basculer = (event) => {
  panneau.value.toggle(event);
  store.charger();
};

const ouvrir = async (notification) => {
  store.marquerLue(notification);
  const chemin = cheminRouteur(notification.url);
  panneau.value.hide();
  if (chemin) router.push(chemin);
};

const preferences = () => {
  panneau.value.hide();
  router.push('/auth/notifications');
};
</script>

<template>
  <button type="button" class="layout-topbar-action relative" aria-label="Notifications" aria-haspopup="true" @click="basculer">
    <i class="pi pi-bell"></i>
    <span
      v-if="store.nonLues > 0"
      class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold rounded-full h-4 min-w-4 px-1 flex items-center justify-center pointer-events-none"
      :aria-label="`${store.nonLues} notification(s) non lue(s)`"
    >
      {{ store.nonLues > 99 ? '99+' : store.nonLues }}
    </span>
    <span>Notifications</span>
  </button>

  <Popover ref="panneau" class="w-[24rem] max-w-[95vw]">
    <div class="flex flex-col gap-2">
      <div class="flex items-center justify-between gap-2">
        <strong>Notifications</strong>
        <Button v-if="store.nonLues > 0" label="Tout marquer comme lu" size="small" text @click="store.toutLire()" />
      </div>

      <p v-if="store.erreur" class="m-0 text-sm text-red-600">{{ store.erreur }}</p>
      <p v-else-if="!store.items.length && !store.chargement" class="m-0 text-sm text-muted-color py-4 text-center">Aucune notification pour le moment.</p>

      <ul v-else class="list-none m-0 p-0 flex flex-col max-h-[26rem] overflow-y-auto">
        <li v-for="n in store.items" :key="n.uuid">
          <button
            type="button"
            class="w-full text-left bg-transparent border-0 p-2 rounded-lg cursor-pointer flex gap-2 items-start hover:bg-surface-100 dark:hover:bg-surface-800"
            :class="{ 'font-semibold': !n.lue }"
            @click="ouvrir(n)"
          >
            <span class="mt-1.5 w-2 h-2 rounded-full shrink-0" :class="n.lue ? 'bg-transparent' : 'bg-primary'" aria-hidden="true"></span>
            <span class="flex flex-col gap-1 min-w-0">
              <span class="text-sm">{{ n.texte }}<span v-if="n.nombre > 1" class="text-muted-color"> (×{{ n.nombre }})</span></span>
              <span class="flex items-center gap-2 text-xs text-muted-color font-normal">
                <Tag :value="n.source.libelle" severity="secondary" />
                {{ formatDateFr(n.date, 'jourMoisHeure') }}
              </span>
            </span>
          </button>
        </li>
      </ul>

      <div class="border-t border-surface-200 pt-2">
        <Button label="Préférences de notification" icon="pi pi-cog" size="small" text @click="preferences" />
      </div>
    </div>
  </Popover>
</template>
