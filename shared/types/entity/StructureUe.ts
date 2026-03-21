import type { ApiResource } from './JsonLd';
import type { ApcCompetence } from './ApcCompetence';

export interface StructureUeFields {
  id?: number;
  libelle: string;
  numero: number;
  nbEcts: number;
  actif: boolean;
  bonification: boolean;
  codeElement: string;
  competence?: (string | ApcCompetence | null);
  semestre?: (string | ApiResource<{ id?: number; libelle?: string | null }> | null);
}

export type StructureUe = ApiResource<StructureUeFields>;
