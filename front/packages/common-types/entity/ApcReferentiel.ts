import type { ApiResource } from './JsonLd';
import type { StructureDepartement, StructureTypeDiplome, ApcParcours, StructurePn } from './_placeholders';
import type { ApcCompetence } from './ApcCompetence';

export interface ApcReferentielFields {
  id?: number;
  libelle: string;
  description?: string | null;
  anneePublication?: number | null;
  diplomes?: (string[] | import('./StructureDiplome').StructureDiplome[]);
  departement?: (string | StructureDepartement | null);
  competences?: (string[] | ApcCompetence[]);
  anneeUniversitaire?: (string | ApiResource<{ id?: number; libelle?: string | null }> | null);
  parcours?: (string[] | ApcParcours[]);
  pn?: (string[] | StructurePn[]);
  typeDiplome?: (string | StructureTypeDiplome | null);
}

export type ApcReferentiel = ApiResource<ApcReferentielFields>;
