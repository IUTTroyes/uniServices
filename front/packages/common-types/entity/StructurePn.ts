import type { ApiResource } from './JsonLd';
import type { ApcReferentiel } from './ApcReferentiel';
import type { StructureDiplome } from './StructureDiplome';
import type { ApiResource as JsonLdApi } from './JsonLd';
import type { StructureAnnee } from './StructureAnnee';

export interface StructurePnFields {
  id?: number;
  libelle: string;
  anneePublication: number;
  diplome?: (string | StructureDiplome | null);
  anneeUniversitaire?: (string | JsonLdApi<{ id?: number; libelle?: string | null }> | null);
  apcReferentiel?: (string | ApcReferentiel | null);
  annees?: (string[] | StructureAnnee[]);
}

export type StructurePn = ApiResource<StructurePnFields>;
