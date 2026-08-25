# Bilan des Fonctionnalités - Package Stage

Ce document récapitule les fonctionnalités manquantes identifiées pour le module de gestion des stages de la plateforme UniServices, ainsi que la feuille de route pour le développement à venir.

---

## 1. Fonctionnalités existantes dans le Package
Le module actuel gère les entités de base suivantes via l'API Platform :
- **Périodes de stage (`StagePeriode`)** : Définition des dates globales, des coefficients, des responsables académiques, des compétences et consignes.
- **Dossiers Étudiants (`StageEtudiant`)** : Liaison de l'étudiant avec l'entreprise d'accueil, le sujet du stage, le maître de stage, le tuteur académique et le statut de la convention.
- **Entreprises et Contacts (`Entreprise`, `Contact`)** : Base de données des entreprises partenaires et des interlocuteurs associés.
- **Portails frontend (Vue 3)** : Dashboards dédiés pour les Étudiants, les Enseignants (tuteurs), les Responsables de stage et les Super-Administrateurs.

---

## 2. Fonctionnalités prioritaires à implémenter

### A. Édition & Génération de Convention PDF (avec Gotenberg)
* **Persistance des modèles** : Création d'une entité `StageConventionTemplate` pour enregistrer en base de données le modèle HTML/texte éditable avec des variables dynamiques (`{etudiant.nom}`, `{entreprise.nom}`, etc.).
* **Génération PDF dynamique** :
  - Création d'un service de génération PDF en PHP faisant appel à un conteneur **Gotenberg** (conversion HTML vers PDF par Chromium).
  - Endpoint API dédié `/api/stage_etudiants/{id}/pdf` qui compile les données du stage, applique le modèle et retourne le flux PDF.
  - Connexion de l'éditeur du super-administrateur à l'API pour charger et sauvegarder le modèle.

### B. Gestion des Avenants aux Conventions
* **Entité Avenant (`StageAvenant`)** : Création d'une structure pour modéliser un avenant (modification de dates, d'horaires, de gratification, de tuteur, etc.).
* **Processus de validation (Workflow)** :
  - L'étudiant formule sa demande d'avenant depuis son espace.
  - Le responsable de stage peut valider ou rejeter l'avenant.
  - Une fois validé, un avenant PDF officiel est généré en fusionnant les données avec un modèle d'avenant.

---

## 3. Autres fonctionnalités (pour mémoire / étapes ultérieures)

* **Signature électronique réelle** : Intégration avec un tiers de confiance (Yousign, DocuSign) ou mise en place d'un système de validation électronique interne (approbations certifiées par jetons e-mail sécurisés).
* **Évaluation structurée & Grille de notation** : Formulaires de notation avec critères pondérés et lien d'évaluation à usage unique (tokenisé) envoyé au tuteur de stage en entreprise.
* **Soutenances et Planification** : Système de répartition des soutenances avec gestion des créneaux horaires, salles, jurys et invitations automatiques.
* **Suivi de stage avancé** : Fiches de visite de stage en entreprise par le tuteur universitaire et journal de bord de l'étudiant.
* **Bourse aux offres de stage** : Job board intégré pour le dépôt des offres par les entreprises, validation administrative et système de candidature pour les étudiants.
