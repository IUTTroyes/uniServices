import type { ApiResource } from './JsonLd';
import type { StructurePn, StructureDepartement, StructureTypeDiplome, ApcParcours, Personnel, PersonnelEnseignantHrs } from './_placeholders';
import type { ApcReferentiel } from './ApcReferentiel';

export interface StructureDiplomeFields {
    id?: number;
    libelle: string;
    responsableDiplome?: (string | Personnel | null);
    assistantDiplome?: (string | Personnel | null);
    volumeHoraire: number;
    codeCelcatDepartement?: number | null;
    sigle?: string | null;
    actif: boolean;
    parent?: (string | StructureDiplome | null);
    enfants?: (string[] | StructureDiplome[]);
    logoPartenaireName?: string | null;
    pns?: (string[] | StructurePn[]);
    departement?: (string | StructureDepartement | null);
    apogeeCodeVersion?: string | null;
    apogeeCodeDiplome?: string | null;
    apogeeCodeDepartement?: string | null;
    typeDiplome?: (string | StructureTypeDiplome | null);
    referentiel?: (string | ApcReferentiel | null);
    parcours?: (string | ApcParcours | null);
    cleOreof?: number | null;
    enseignantHrs?: (string[] | PersonnelEnseignantHrs[]);
}

export type StructureDiplome = ApiResource<StructureDiplomeFields>;

