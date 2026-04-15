export interface Category {
  id: string;
  name: string;
  parentId?: string;
  children?: Category[];
  documentCount: number;
  icon: string;
  color: string;
}

export interface Document {
  id: string;
  title: string;
  type: DocumentType;
  size: number;
  lastModified: Date;
  description?: string;
  categoryId: string;
  isFavorite: boolean;
  author: string;
  version: string;
  tags: string[];
}

export type DocumentType = 'pdf' | 'excel' | 'word' | 'powerpoint' | 'image' | 'video' | 'audio' | 'text' | 'archive';

export type SortField = 'title' | 'lastModified' | 'size' | 'type';
export type SortOrder = 'asc' | 'desc';

export interface PaginationInfo {
  currentPage: number;
  totalPages: number;
  totalItems: number;
  itemsPerPage: number;
}

export type ViewMode = 'grid' | 'list';

// API Platform JSON-LD entities
export * from './entity/JsonLd';
export * from './entity/_placeholders';
export * from './entity/StructureDiplome';
export * from './entity/ApcReferentiel';
export * from './entity/ApcCompetence';
export * from './entity/ApcNiveau';
export * from './entity/ApcApprentissageCritique';
export * from './entity/StructurePn';
export * from './entity/ApcParcours';
export * from './entity/StructureAnnee';
export * from './entity/StructureUe';
export * from './entity/User';
export * from './entity/Groupe';
export * from './entity/Scolarite';

export * from './FilterState';
export * from './survey';


