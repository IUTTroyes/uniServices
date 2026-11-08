# Notifications du package Stages

Le package utilise le système commun (`docs/NOTIFICATIONS_SYSTEM.md`) : cloche + e-mail selon les préférences de chacun
(page *Notifications* du profil, rubrique « Stages »). Aucun e-mail n'est envoyé en dehors de `NotificationService`.

## Événements

| Clé | Destinataire | Déclencheur | Mode par défaut |
|---|---|---|---|
| `stage.demande_deposee` | responsables de la période (principal + co-) | l'étudiant dépose / modifie sa demande, ou un responsable la repasse « en attente » | résumé |
| `stage.saisie_ouverte` | étudiant | statut → `AUTORISE` | immédiat |
| `stage.convention_validee` | étudiant | statut → `VALIDE` | immédiat |
| `stage.convention_refusee` | étudiant | statut → `REFUS` (avec le motif saisi) | immédiat (pas « jamais ») |
| `stage.convention_a_signer` | étudiant | statut → `CONVENTION_IMPRIMEE` / `CONVENTION_ENVOYEE` (une seule fois) | immédiat (pas « jamais ») |
| `stage.convention_signee` | étudiant | statut → `CONVENTION_RECUE` | résumé |
| `stage.tuteur_affecte` | tuteur universitaire | affectation (ou changement) du tuteur | résumé |
| `stage.avenant_demande` | responsables de la période | `POST /api/stage_avenants` par l'étudiant | résumé |
| `stage.avenant_valide` | étudiant | `POST /api/stage_avenants/{id}/validate` | immédiat |
| `stage.soutenance_planifiee` | étudiant | créneau attribué / modifié (`/api/stage_soutenances/save`) | immédiat |
| `stage.jury_soutenance` | tuteur universitaire + assesseur | idem | immédiat |
| `stage.rapport_depose` | tuteur universitaire | dépôt du rapport (`POST /api/stage_etudiants/{id}/rapport`) | résumé |
| `stage.evaluation_entreprise_recue` | tuteur universitaire | le tuteur en entreprise rend son évaluation | résumé |
| `stage.relance_demandes` | responsables de la période | `stage:relances` : demandes déposées depuis plus de 7 jours | immédiat ou jamais |
| `stage.relance_convention` | étudiant | `stage:relances` : convention envoyée depuis plus de 14 jours | immédiat ou jamais |
| `stage.relance_rapport` | étudiant | `stage:relances` : soutenance dans 7 jours ou stage terminé, sans rapport | immédiat ou jamais |

L'auteur d'une action n'est jamais notifié. Les liens visent l'espace concerné (`/app/stage/etudiant`,
`/app/stage/enseignant`, `/app/stage/responsable/periode/{id}`) : les dépôts d'une même période fusionnent dans le résumé.

## Droits (rappel)

Règles centralisées dans `Security/StageAccess` (voter `STAGE_ETUDIANT_VIEW|EDIT|MANAGE` et `STAGE_PERIODE_MANAGE`,
extension `Doctrine/StageVisibiliteExtension`, processeur) :

- super administrateur : tout ;
- responsable des stages (`ROLE_STAGE_MANAGER`) : tout, **uniquement dans les départements où il a ce rôle**
  (département de la période : semestre → année → PN → diplôme) ; une période sans département n'est gérable que par
  le super administrateur ; les modèles de convention, communs à tous, sont réservés au super administrateur ;
- responsables (principal ou co-) d'une période : tout sur les dossiers de cette période ;
- tuteur universitaire : lecture, et seulement `suiviRencontres`, `evaluationNote`, `evaluationCommentaire` en écriture ;
- étudiant : son dossier ; il ne peut que le déposer (`DEPOSE`), pas écrire les champs réservés (tuteur, évaluation,
  suivi, dates de traitement), et plus rien une fois la saisie fermée (`INCOMPLET`) ou la convention signée.

## Tâches planifiées

```bash
php bin/console stage:taches:installer          # crée les tâches : relances (lundi 7 h), purge (le 1er du mois, 3 h)
php bin/console stage:relances --dry-run        # liste les relances sans rien envoyer
php bin/console stage:purger --dry-run          # compte les dossiers à anonymiser (terminés depuis plus de 5 ans)
```

## Évaluation par le tuteur en entreprise

- Grille de critères **par période** (onglet de la période, côté responsable), notés de 1 à 4.
- Le tuteur universitaire ou un responsable envoie le lien (convention envoyée ou signée) ; un renvoi invalide le lien
  précédent. L'e-mail (`stage.evaluation_entreprise`) contient un lien absolu construit avec `URL_FRONTEND`.
- Formulaire public `/app/stage-evaluation/<jeton>` : sans compte, jeton à usage unique valable 30 jours, seule son
  empreinte SHA-256 est stockée.
