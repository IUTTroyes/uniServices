import api from '@helpers/axios';

export const getSpecialitesOrebutService = async () => (await api.get('/api/orebut/specialites')).data;

// simulation : true pour prévisualiser, false pour écrire.
export const synchroniserOrebutService = async (params) => (await api.post('/api/orebut/synchronisation', params)).data;
