# Notifications du package Stages

Le package utilise le système commun (`docs/NOTIFICATIONS_SYSTEM.md`) : cloche + e-mail selon les préférences de chacun
(page *Notifications* du profil, rubrique « Stages »). Aucun e-mail n'est envoyé en dehors de `NotificationService`.

## Événements

| Clé | Destinataire | Déclencheur | Mode par défaut |
|---|---|---|---|
| `stage.demande_deposee` | responsables de la période (principal + co-) | l'étudiant dépose / modifie sa demande, ou un responsable la repasse « en attente » | résumé |
| `stage.saisie_ouverte` | étudiant | statut → `AUTORISE` | immédiat |
| `stage.convention_validee` | étudiant | statut → `VALIDE` | immédiat |
| `stage.convention_refusee` | étudiant | statut → `REFUS` | immédiat (pas « jamais ») |
| `stage.convention_a_signer` | étudiant | statut → `CONVENTION_IMPRIMEE` / `CONVENTION_ENVOYEE` (une seule fois) | immédiat (pas « jamais ») |
| `stage.convention_signee` | étudiant | statut → `CONVENTION_RECUE` | résumé |
| `stage.tuteur_affecte` | tuteur universitaire | affectation (ou changement) du tuteur | résumé |
| `stage.avenant_demande` | responsables de la période | `POST /api/stage_avenants` par l'étudiant | résumé |
| `stage.avenant_valide` | étudiant | `POST /api/stage_avenants/{id}/validate` | immédiat |
| `stage.soutenance_planifiee` | étudiant | créneau attribué / modifié (`/api/stage_soutenances/save`) | immédiat |
| `stage.jury_soutenance` | tuteur universitaire + assesseur | idem | immédiat |

L'auteur d'une action n'est jamais notifié. Les liens visent l'espace concerné (`/app/stage/etudiant`,
`/app/stage/enseignant`, `/app/stage/responsable/periode/{id}`) : les dépôts d'une même période fusionnent dans le résumé.

## Droits (rappel)

Règles centralisées dans `Security/StageAccess` (voter `STAGE_ETUDIANT_VIEW|EDIT|MANAGE`, extension de collection,
processeur) :

- gestionnaire (`ROLE_STAGE_MANAGER`, super admin) et responsables de la période : tout sur les dossiers ;
- tuteur universitaire : lecture, et seulement `suiviRencontres`, `evaluationNote`, `evaluationCommentaire` en écriture ;
- étudiant : son dossier ; il ne peut que le déposer (`DEPOSE`), pas écrire les champs réservés (tuteur, évaluation,
  suivi, dates de traitement), et plus rien une fois la saisie fermée (`INCOMPLET`) ou la convention signée.
