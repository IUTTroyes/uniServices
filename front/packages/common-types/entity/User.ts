import type { ApiResource } from './JsonLd';
import {Groupe} from "@types";
import {ScolariteSemestre} from "@types";

export interface EtudiantFields {
    id?: number;
    prenom: string;
    nom: string;
    username: string;
    mailUniv: string;
    photoName: string;
    scolarite: ScolariteSemestre; //scolarite active
    scolarites: ScolariteSemestre[];
    groupes: Groupe[];
    statut: 'etudiant';
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

