export interface GroupesMap {
  CM: string[];
  TD: string[];
  TP: string[];
}

export interface GroupesIdsMap {
  CM: number[];
  TD: number[];
  TP: number[];
}

export interface StudentCardItem {
  id: number | string;
  prenom: string;
  nom: string;
  mailUniv?: string;
  mailPerso?: string;
  photoName?: string;
  scolarites?: any[];
  scolarite: string;
  semestre: number | string | null;
  semestreLibelle: string;
  groupes: GroupesMap;
  groupesIds: GroupesIdsMap;
  numEtudiant: string;
  bac?: string;
  anneeSortie?: number;
  status: 'active' | 'inactive';
}

export interface StaffCardItem {
  id: number | string;
  prenom: string;
  nom: string;
  mailUniv?: string;
  photoName?: string;
  statut?: string;
  displayStatut?: string;
  statutSeverity?: string;
  bureau?: string;
  telBureau?: string;
  posteInterne?: string;
  domaines?: string;
  position?: string;
  department?: string;
}

export type PersonItem = StudentCardItem | StaffCardItem;

export function isStudentCard(person: PersonItem): person is StudentCardItem {
  return 'scolarite' in person || 'scolarites' in person || 'groupes' in person;
}

