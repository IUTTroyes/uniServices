import api from '@helpers/axios';

export const unifolioService = {
  getContexte() {
    return api.get('/api/unifolio/contexte');
  },
};

export default unifolioService;
