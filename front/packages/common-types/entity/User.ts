import type { ApiResource } from './JsonLd';

export interface EtudiantFields {
    id?: number;
    prenom: string;
    nom: string;
    username: string;
    mailUniv: string;
    photoName: string;
}

export type Etudiant = ApiResource<EtudiantFields>;

export interface PersonnelFields {
    id?: number;
    prenom: string;
    nom: string;
    username: string;
    mailUniv: string;
    photoName: string;
    statut: string;
}

export type Personnel = ApiResource<PersonnelFields>;

export type PersonType = Etudiant | Personnel;

