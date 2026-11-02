import { defineStore } from 'pinia';
import messageService from '../requests/messageService';

export const useMessageStore = defineStore('message', {
  state: () => ({
    messages: [],
    sentMessages: [],
    currentMessage: null,
    unreadCount: 0,
    isLoading: false,
    activeFilter: 'all', // 'all', 'unread', 'archived'
    searchQuery: '',
    error: null,
  }),

  getters: {
    hasUnread: (state) => state.unreadCount > 0,
  },

  actions: {
    async fetchUnreadCount() {
      try {
        const response = await messageService.getUnreadCount();
        this.unreadCount = response.data?.unreadCount ?? 0;
        return this.unreadCount;
      } catch (err) {
        console.error('Erreur lors de la récupération des messages non lus:', err);
        return 0;
      }
    },

    async fetchInbox(params = {}) {
      this.isLoading = true;
      this.error = null;
      try {
        const mergedParams = {
          status: this.activeFilter,
          search: this.searchQuery,
          ...params,
        };
        const response = await messageService.getInbox(mergedParams);
        this.messages = response.data?.messages ?? [];
        this.unreadCount = response.data?.unreadCount ?? this.unreadCount;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erreur lors du chargement des messages';
        console.error(err);
      } finally {
        this.isLoading = false;
      }
    },

    async fetchMessage(id) {
      this.isLoading = true;
      this.error = null;
      try {
        const response = await messageService.getMessage(id);
        this.currentMessage = response.data;
        // Décrémente le compteur local si le message vient d'être lu
        if (response.data?.isLu && this.unreadCount > 0) {
          this.unreadCount = Math.max(0, this.unreadCount - 1);
        }
        return response.data;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erreur lors de la lecture du message';
        throw err;
      } finally {
        this.isLoading = false;
      }
    },

    async toggleRead(id, isLu) {
      try {
        const response = await messageService.toggleRead(id, isLu);
        if (response.data?.success) {
          const item = this.messages.find(m => m.id === id);
          if (item) {
            item.isLu = response.data.isLu;
          }
          if (this.currentMessage && this.currentMessage.id === id) {
            this.currentMessage.isLu = response.data.isLu;
          }
          if (typeof response.data.unreadCount === 'number') {
            this.unreadCount = response.data.unreadCount;
          }
        }
      } catch (err) {
        console.error('Erreur toggle read:', err);
      }
    },

    async markAllAsRead() {
      try {
        const response = await messageService.markAllAsRead();
        if (response.data?.success) {
          this.messages.forEach(m => {
            m.isLu = true;
          });
          this.unreadCount = 0;
        }
        return response.data;
      } catch (err) {
        console.error('Erreur mark all as read:', err);
        throw err;
      }
    },

    async toggleArchive(id, isArchive) {
      try {
        const response = await messageService.toggleArchive(id, isArchive);
        if (response.data?.success) {
          const item = this.messages.find(m => m.id === id);
          if (item) {
            item.isArchive = response.data.isArchive;
          }
          // Si on est dans la vue boîte de réception normale, on retire le message archivé
          if (this.activeFilter !== 'archived' && response.data.isArchive) {
            this.messages = this.messages.filter(m => m.id !== id);
          }
        }
      } catch (err) {
        console.error('Erreur toggle archive:', err);
      }
    },

    async fetchSentMessages() {
      this.isLoading = true;
      try {
        const response = await messageService.getSentMessages();
        this.sentMessages = response.data?.messages ?? [];
      } catch (err) {
        console.error('Erreur fetch sent messages:', err);
      } finally {
        this.isLoading = false;
      }
    },

    async sendMessage(payload) {
      this.isLoading = true;
      try {
        const response = await messageService.sendMessage(payload);
        return response.data;
      } catch (err) {
        this.error = err.response?.data?.message || "Erreur lors de l'envoi du message";
        throw err;
      } finally {
        this.isLoading = false;
      }
    },
  },
});

