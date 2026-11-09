import api from '@helpers/axios';
import { apiBaseUrl } from './traceService';

export const suiviService = {
  /** Étudiant : progression sur tout le cursus. */
  progression() {
    return api.get('/api/unifolio/progression');
  },
  campagnes() {
    return api.get('/api/unifolio/suivi/campagnes');
  },
  campagne(uuid) {
    return api.get(`/api/unifolio/suivi/campagnes/${uuid}`);
  },
  urlExport: (uuid) => `${apiBaseUrl()}/api/unifolio/suivi/campagnes/${uuid}/export.csv`,
};

export const pourcent = (score) => (score === null || score === undefined ? null : Math.round(score * 100));

/** Teinte d'un score 0-1 (rouge → vert) pour la matrice, avec une couleur de texte lisible. */
export const teinteScore = (score) => {
  if (score === null || score === undefined) return { background: 'transparent', color: 'inherit' };
  const teinte = Math.round(score * 120);

  return { background: `hsl(${teinte} 60% 88%)`, color: `hsl(${teinte} 70% 22%)` };
};

export const ALERTES = {
  sans_depot: { libelle: 'Aucun dépôt', severite: 'danger', icone: 'pi pi-inbox' },
  a_evaluer: { libelle: 'À évaluer', severite: 'warn', icone: 'pi pi-check-square' },
  evaluation_en_retard: { libelle: 'Évaluation en retard', severite: 'danger', icone: 'pi pi-clock' },
  ecart_notable: { libelle: 'Écart auto-évaluation / évaluation', severite: 'info', icone: 'pi pi-arrows-h' },
};
