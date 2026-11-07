import api from '@helpers/axios';

const base = '/api/unifolio/notifications/preferences';

export const notificationService = {
  preferences() {
    return api.get(base);
  },
  /** { actif?, frequence?, reglages?: { type: mode } } — seuls les champs fournis changent. */
  modifier(donnees) {
    return api.put(base, donnees);
  },
};

export default notificationService;
