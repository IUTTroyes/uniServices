import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

const urlSemestre = (id) => `/api/intranet/previsionnel/semestres/${id}`;
const contexteAnnuel = 'ressource';

export const viderPrevisionnelService = (id, ids) => apiCall(
  api.delete,
  [`${urlSemestre(id)}/lignes`, { data: { ids }, contexteAnnuel }],
  'Prévisionnel vidé',
  'Impossible de vider le prévisionnel.',
);

export const importerPrevisionnelService = (id, lignes) => apiCall(
  api.post,
  [`${urlSemestre(id)}/import`, { lignes }, { contexteAnnuel }],
  'Prévisionnel importé',
  'Impossible d’importer le prévisionnel.',
);

export const copierPrevisionnelService = (id, source) => apiCall(
  api.post,
  [`${urlSemestre(id)}/copie`, { source }, { contexteAnnuel }],
  'Prévisionnel copié',
  'Impossible de copier le prévisionnel.',
);
