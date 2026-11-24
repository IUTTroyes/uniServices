import api from '@helpers/axios';

const options = { contexteAnnuel: 'aucun' };
const prefixe = '/api/parcauto';

export const getParcService = async () => (await api.get(prefixe, options)).data;
export const getAccueilService = () => lireService('/accueil');
export const getPlanningService = (params) => lireService('/planning', params);
export const getSuiviService = (id) => lireService(`/vehicules/${id}/suivi`);
export const getTableauBordService = (params) => lireService('/tableau-bord', params);

async function lireService(chemin, params) {
  return (await api.get(prefixe + chemin, { ...options, params })).data;
}

export async function enregistrerParcautoService(chemin, donnees, modifier = false) {
  return (await api[modifier ? 'put' : 'post'](prefixe + chemin, donnees, options)).data;
}

export async function exporterParcautoService(params) {
  const { data } = await api.get(prefixe + '/export', { ...options, params, responseType: 'blob' });
  const url = URL.createObjectURL(data);
  const lien = document.createElement('a');
  lien.href = url;
  lien.download = 'parcauto.csv';
  lien.click();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export const supprimerValeurReferentielService = (groupe, code) =>
  api.delete(prefixe + '/referentiels/' + encodeURIComponent(groupe) + '/' + encodeURIComponent(code), options);
