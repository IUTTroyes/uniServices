import type { ApiResource } from './JsonLd';

// Minimal shared placeholders for related entities so we can express relations
export interface StructureDepartement extends ApiResource<{ id?: number; libelle?: string | null }>{}
export interface StructureTypeDiplome extends ApiResource<{ id?: number; libelle?: string | null }>{}
export interface ScolEnseignement extends ApiResource<{ id?: number; libelle?: string | null }>{}
export interface PersonnelEnseignantHrs extends ApiResource<{ id?: number }>{}
