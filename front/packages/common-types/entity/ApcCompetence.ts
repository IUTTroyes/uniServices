import type { ApiResource } from './JsonLd';
import type { ApcReferentiel } from './ApcReferentiel';
import type { ApcNiveau } from './ApcNiveau';
import type { StructureUe } from './_placeholders';

export interface ApcCompetenceFields {
  id?: number;
  libelle: string;
  nomCourt?: string | null;
  couleur?: string | null;
  referentiel?: (string | ApcReferentiel | null);
  niveaux?: (string[] | ApcNiveau[]);
  composantesEssentielles: string[];
  situationsProfessionnelles: string[];
  ues?: (string[] | StructureUe[]);
}

export type ApcCompetence = ApiResource<ApcCompetenceFields>;
