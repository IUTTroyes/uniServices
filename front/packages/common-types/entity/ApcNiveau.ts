import type { ApiResource } from './JsonLd';
import type { ApcParcours } from './_placeholders';
import type { ApcCompetence } from './ApcCompetence';
import type { ApcApprentissageCritique } from './ApcApprentissageCritique';

export interface ApcNiveauFields {
  id?: number;
  libelle: string;
  ordre: number;
  parcours?: (string[] | ApcParcours[]);
  competence?: (string | ApcCompetence | null);
  apprentissageCritique?: (string[] | ApcApprentissageCritique[]);
}

export type ApcNiveau = ApiResource<ApcNiveauFields>;
