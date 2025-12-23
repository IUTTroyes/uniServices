import type { ApiResource } from './JsonLd';
import type { StructureDiplome } from './StructureDiplome';
import type { ApcNiveau } from './ApcNiveau';
import type { StructurePn } from './StructurePn';

export interface StructureAnneeFields {
  id?: number;
  libelle: string;
  ordre: number;
  libelleLong?: string | null;
  actif?: boolean | null;
  couleur?: string | null;
  apogeeCodeVersion?: string | null;
  apogeeCodeEtape?: string | null;
  niveau?: (string | ApcNiveau | null);
  pn?: (string | StructurePn | null);
  diplome?: (string | StructureDiplome | null);
  // semestres, scolarites, etc. intentionally omitted for now (can be added when needed)
}

export type StructureAnnee = ApiResource<StructureAnneeFields>;
