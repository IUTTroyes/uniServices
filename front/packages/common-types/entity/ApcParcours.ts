import type { ApiResource } from './JsonLd';
import type { StructureDiplome } from './StructureDiplome';
import type { ApcNiveau } from './ApcNiveau';
import type { ApcReferentiel } from './ApcReferentiel';

export interface ApcParcoursFields {
  id?: number;
  libelle: string;
  sigle?: string | null;
  actif?: boolean | null;
  couleur?: string | null;
  diplome?: (string[] | StructureDiplome[]);
  groupes?: (string[] | ApiResource<{ id?: number; libelle?: string | null }>[]);
  niveaux?: (string[] | ApcNiveau[]);
  referentiel?: (string | ApcReferentiel | null);
}

export type ApcParcours = ApiResource<ApcParcoursFields>;
