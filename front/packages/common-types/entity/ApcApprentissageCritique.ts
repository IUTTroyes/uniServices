import type { ApiResource } from './JsonLd';
import type { ScolEnseignement } from './_placeholders';
import type { ApcNiveau } from './ApcNiveau';

export interface ApcApprentissageCritiqueFields {
  id?: number;
  libelle: string;
  code?: string | null;
  enseignements?: (string[] | ScolEnseignement[]);
  niveau?: (string | ApcNiveau | null);
}

export type ApcApprentissageCritique = ApiResource<ApcApprentissageCritiqueFields>;
