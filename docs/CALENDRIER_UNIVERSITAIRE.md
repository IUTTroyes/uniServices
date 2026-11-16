# Calendrier universitaire calculé

## Paramétrage

Chaque `StructureAnneeUniversitaire` possède une date facultative `dateDebutSemaineFormation` : le lundi de la semaine de formation n°1, communiqué en interne. La date se saisit dans la création ou la modification d’une année, ou dans **Super-administration → Calendrier**.

Le compteur avance chaque semaine, vacances comprises. La fin incluse est le **31 août N+1**, où N est `StructureAnneeUniversitaire.annee`. Une dernière semaine peut être partielle. Aucun plafond de 52 semaines n’est imposé.

Une date absente correspond à un calendrier non configuré. Le service refuse les dates de référence autres qu’un lundi ou postérieures à la fin de l’année. Avant le début et après le 31 août, une date est hors calendrier : elle n’est jamais convertie en semaine 0.

## Calcul et API

`App\Service\Calendrier\CalendrierUniversitaireService` centralise :

- la conversion d’une date civile en numéro de semaine de formation ;
- la conversion inverse, avec lundi, fin bornée, numéro et année ISO ;
- la génération de toutes les semaines de l’année.

Les calculs utilisent des dates civiles et des écarts en jours, sans division de timestamps. Les changements d’heure n’affectent pas les résultats.

API authentifiée :

```text
GET /api/annees-universitaires/{id}/calendrier
GET /api/annees-universitaires/{id}/calendrier?date=2026-09-14
```

La première réponse contient `semaines`. La seconde contient `semaine`, ou `null` si la date est hors calendrier. Une date mal formée donne HTTP 400 ; un calendrier non configuré ou invalide donne HTTP 422.

Les quatre vues EDT transmettent une date civile, au lieu d’un numéro ISO. Elles ne recherchent pas d’événements lorsqu’aucune semaine n’est disponible. `EdtEvent.semaineFormation` et la progression des enseignements restent inchangés.

## Contraintes

`POST /api/edt-calendrier-contraintes` attend désormais :

```json
{"semestreId": 12, "dateLundi": "2026-09-14", "contrainte": "stage"}
```

Le serveur vérifie les droits EDT et l’appartenance de la date au calendrier de l’année universitaire du semestre. Les contraintes sont stockées dans `EdtContraintesSemestre`, par année et semestre, avec des clés `AAAA-MM-JJ`. L’ancien paramètre `weekId` n’est plus utilisé.

L’ancien DTO `PersonnelsContraintes`, dont le provider était absent du dépôt, et les anciens provider/filtre du calendrier ont été retirés. Les semaines n’ont plus d’identifiant persistant ni d’opérations CRUD ; la page de calendrier configure la date annuelle et affiche le résultat calculé.

## Installation et reprise des données

Appliquer la migration **avant de servir le nouveau code** :

```sh
cd back
php bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20261116150000' --up
```

La migration :

1. Vérifie les anciennes correspondances : lundi valide, numéro ISO cohérent, absence de doublon et compteur continu.
2. Déduit le lundi de semaine 1, même si sa ligne manque, et renseigne l’année universitaire.
3. Convertit les clés des contraintes historiques en dates de lundi ; les clés introuvables ou ambiguës provoquent un arrêt.
4. Remplace les éventuelles clés étrangères vers les anciennes semaines par une colonne `date_lundi` dans les tables historiques concernées.
5. Supprime `structure_calendrier` et crée la table des contraintes de semestre si elle est absente.

Les vérifications de données précèdent les DDL. Un calendrier incohérent doit être corrigé avant reprise ; aucune date arbitraire n’est inventée. Les années sans calendrier restent non configurées. La migration MySQL n’est pas transactionnelle ; sa restauration nécessite une sauvegarde, notamment pour retrouver les anciens identifiants. Les éventuelles tables historiques sans entité Doctrine restent hors du modèle courant.

Le migrateur V3 `CalendrierMigrator` conserve son nom et sa dépendance aux années universitaires, mais reprend uniquement la date de référence annuelle. Il vérifie également la continuité et refuse d’écraser une date configurée différente.
