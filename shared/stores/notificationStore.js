import { defineStore } from 'pinia';
import api from '@helpers/axios';

/**
 * Notifications de la personne connectée (cloche de la barre du haut).
 * Le compteur est léger et peut être interrogé souvent ; la liste n'est chargée qu'à l'ouverture du panneau.
 */
export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    items: [],
    nonLues: 0,
    chargement: false,
    erreur: null,
  }),

  actions: {
    async compter() {
      try {
        const { data } = await api.get('/api/notifications/compteur');
        this.nonLues = data.nonLues;
      } catch {
        // Cloche muette plutôt qu'une erreur : le compteur reviendra au prochain passage.
      }
    },

    async charger(limite = 20) {
      this.chargement = true;
      this.erreur = null;
      try {
        const { data } = await api.get('/api/notifications', { params: { limite } });
        this.items = data.notifications;
        this.nonLues = data.nonLues;
      } catch {
        this.erreur = 'Impossible de charger vos notifications.';
      } finally {
        this.chargement = false;
      }
    },

    async marquerLue(notification) {
      if (notification.lue) return;
      notification.lue = true;
      try {
        const { data } = await api.post(`/api/notifications/${notification.uuid}/lue`);
        this.nonLues = data.nonLues;
      } catch {
        notification.lue = false;
      }
    },

    async toutLire() {
      try {
        await api.post('/api/notifications/lues');
        this.items.forEach((n) => { n.lue = true; });
        this.nonLues = 0;
      } catch {
        // Le panneau garde son état : l'utilisateur peut réessayer.
      }
    },
  },
});

/** Chemin d'une notification pour le routeur de l'application (qui est monté sous /app/). */
export const cheminRouteur = (url) => {
  if (typeof url !== 'string' || !url.startsWith('/') || url.startsWith('//')) return null;

  return url.replace(/^\/app(?=\/|$)/, '') || '/';
};
