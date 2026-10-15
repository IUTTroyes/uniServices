import api from '@helpers/axios';

export interface CommissionColumn {
  key: string;
  label: string;
  type: 'ue' | 'enseignement';
  parentKey?: string | null;
  coefficient?: number | null;
  ects?: number | null;
  sublabel?: string | null;
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
  mode: 'apc' | 'classique';
  modeLibelle: string;
  semestre: { id: number; libelle: string };
  columns: CommissionColumn[];
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
