import api from '@helpers/axios';

export interface CommissionColumnUeLink {
  id: number;
  numero: number;
  label: string;
  libelle: string;
  coefficient: number;
  ects: number;
  color: string;
  competenceNomCourt?: string | null;
  competenceCouleur?: string | null;
}

export interface CommissionColumn {
  key: string;
  label: string;
  type: 'ue' | 'enseignement';
  parentKey?: string | null;
  coefficient?: number | null;
  ects?: number | null;
  sublabel?: string | null;
  nature?: 'ressource' | 'sae' | 'matiere' | 'ue';
  enseignementId?: number | null;
  ues?: CommissionColumnUeLink[] | null;
  color?: string | null;
  competenceNomCourt?: string | null;
  competenceCouleur?: string | null;
}

export interface CommissionRow {
  scolariteSemestreId: number;
  etudiantId: number | null;
  nom: string;
  prenom: string;
  cells: Record<string, number | null>;
  moyenne: number | null;
  rang?: number | null;
  decision?: boolean | null;
  decisionLibelle: string;
  propositionCalculee?: boolean | null;
  decisionJury?: boolean;
  motifJury?: string | null;
  uesValidees: number;
  numeroEtudiant?: string | null;
  dateNaissance?: string | null;
  bac?: string | null;
}

export interface CommissionStatistiques {
  effectif?: number;
  moyennePromo?: number | null;
  min?: number | null;
  max?: number | null;
  valides?: number;
  tauxReussite?: number | null;
}

export interface CommissionViewData {
  empreinte: string;
  erreurs: string[];
  validation: { etat: 'ouverte' | 'validee'; numeroRevision?: number; revision?: CommissionRevision };
  mode: 'apc' | 'classique';
  modeLibelle: string;
  semestre: { id: number; libelle: string };
  columns: CommissionColumn[];
  columnsByEnseignement?: CommissionColumn[];
  rows: CommissionRow[];
  statistiques: CommissionStatistiques;
}

/**
 * Récupère les données de la sous-commission pour un semestre donné.
 */
export const getCommissionSemestre = async (semestreId: number | string): Promise<CommissionViewData> => {
  const response = await api.get(`/api/intranet/commissions/semestre/${semestreId}`);
  return response.data;
};

export interface JuryDecision { inscriptionId: number; decision: boolean; motif?: string | null }
export interface CommissionRevision {
  id: number; numero: number; valideLe: string;
  validePar: { id: number; nom: string; prenom: string };
  invalideeLe: string | null; motifReouverture: string | null; vue: CommissionViewData;
}
export const validerCommission = async (id: number, empreinte: string, decisions: JuryDecision[]): Promise<CommissionViewData> =>
  (await api.post(`/api/intranet/commissions/semestre/${id}/valider`, { empreinte, decisions })).data;
export const rouvrirCommission = async (id: number, numeroRevision: number, motif: string): Promise<CommissionViewData> =>
  (await api.post(`/api/intranet/commissions/semestre/${id}/rouvrir`, { numeroRevision, motif })).data;
export const getHistoriqueCommission = async (id: number): Promise<CommissionRevision[]> =>
  (await api.get(`/api/intranet/commissions/semestre/${id}/historique`)).data;
