import api from '@helpers/axios';

export const messageService = {
  getInbox(params = {}) {
    return api.get('/api/messages', { params });
  },

  getUnreadCount() {
    return api.get('/api/messages/unread-count');
  },

  getMessage(id) {
    return api.get(`/api/messages/${id}`);
  },

  toggleRead(id, isLu) {
    return api.patch(`/api/messages/${id}/read`, { isLu });
  },

  markAllAsRead() {
    return api.patch('/api/messages/read-all');
  },

  downloadPieceJointeUrl(messageId, pjId) {
    return `/api/messages/${messageId}/pieces-jointes/${pjId}`;
  },

  toggleArchive(id, isArchive) {
    return api.patch(`/api/messages/${id}/archive`, { isArchive });
  },

  getSentMessages() {
    return api.get('/api/messages/sent');
  },

  getSentMessageStats(id) {
    return api.get(`/api/messages/sent/${id}/stats`);
  },

  getTargets() {
    return api.get('/api/messages/targets');
  },

  sendMessage(payload) {
    if (payload instanceof FormData) {
      return api.post('/api/messages', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }
    return api.post('/api/messages', payload);
  },
};

export default messageService;

