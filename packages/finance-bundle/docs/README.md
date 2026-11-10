# Finances & Achats — package `finance`

Demandes d'achat et bons de commande : demande, avis, vérification SIFAC, visa d'engagement, service fait, facture.

## Droits

Toutes les règles sont dans `Security/FinanceAccess` (utilisé par `FinanceVoter`, le processeur, le contrôleur et
`Doctrine/FinanceVisibiliteExtension`). Les rôles du package sont **toujours vérifiés dans le département de la demande**
(`DepartmentPermissionChecker`), jamais globalement. Le super administrateur a tous les droits.

| Rôle (par département) | Droits |
|---|---|
| `ROLE_FINANCE_VIEW` (accès par défaut au package) | voir ses propres demandes et celles dont on est le responsable désigné, consulter le référentiel des fournisseurs |
| `ROLE_FINANCE_DEMANDEUR` | créer des demandes dans ce département, modifier les siennes, supprimer les siennes encore en brouillon |
| `ROLE_FINANCE_VALIDEUR` | voir les demandes du département, rendre l'avis de l'étape 1 |
| `ROLE_FINANCE_MANAGER` | voir, modifier et supprimer les demandes du département ; gérer le référentiel des fournisseurs |

Les étudiants n'ont jamais accès au package.

## Circuit (côté serveur)

`Workflow/FinanceWorkflow` décrit tout le circuit ; le front n'en recopie aucune règle : chaque demande lue en détail
porte `droits` (actions possibles, champs modifiables, suppression, dépôt du document, gestion des réceptions).

```
BROUILLON ─soumettre→ SOUMIS ─avis_favorable→ VERIFICATION_FINANCIERE ─verifier→ VISA_ENGAGEMENT ─viser→
COMMANDE_VALIDEE ─transmettre→ COMMANDE_TRANSMISE ─constater_service_fait→ SERVICE_FAIT_CONSTATE
─certifier_service_fait→ SERVICE_FAIT_CERTIFIE ─recevoir_facture→ FACTURE_RECUE ─payer→ PAYE ─cloturer→ CLOTURE
demander_information → BROUILLON ; avis_defavorable / rejeter → REJETE ─reouvrir→ BROUILLON ;
rejeter_facture → SERVICE_FAIT_CERTIFIE ; une réception partielle permet d'en constater une autre.
```

| Action | Qui | Données |
|---|---|---|
| `soumettre`, `reouvrir` | demandeur, gestionnaire | — |
| `avis_favorable`, `avis_defavorable`, `demander_information` | valideur du département | `commentaire` (obligatoire sauf avis favorable) |
| `verifier` (n° SIFAC et fournisseur du référentiel obligatoires), `viser`, `transmettre`, `certifier_service_fait`, `cloturer` | gestionnaire | — |
| `constater_service_fait` | demandeur, responsable désigné, gestionnaire | `typeReception`, `dateEffectiveReception`, `montantTtcConstate`, `prestationsConformes`, `detailsReceptionPartielle`, `qualiteReceptionnaire` |
| `recevoir_facture`, `payer` | gestionnaire | `date` (AAAA-MM-JJ, défaut : aujourd'hui), `factureFinale` (payer) |
| `rejeter_facture`, `rejeter` | gestionnaire | `commentaire` obligatoire |

`POST /api/finance_bon_commandes/{id}/transitions/{action}` : 200, 403 (droit), 404, 409 (statut), 422 (données).
Le serveur fixe statut, dates, signataires (avis, vérification, visa, certification) et crée les réceptions.

**Champs modifiables (PATCH)** : `FinanceWorkflow::champsModifiables`. Le demandeur modifie sa demande tant qu'elle est en
brouillon ; le service financier la demande et les champs SIFAC jusqu'au visa, puis les seuls champs SIFAC (et la
facturation en fin de circuit) ; le valideur n'agit que par les transitions. Toute clé non permise est **refusée (403)**
(`State/FinanceBonCommandeProcessor`) : demandeur, statut, avis, dates de visa, chemin du document… À la création, le
demandeur est la personne connectée, la demande part en brouillon. Le département ne se choisit qu'à la création.
Le chemin du document signé n'est ni exposé ni modifiable : l'API ne renvoie que `pdfSigne` (booléen). Le dépôt du
document ne fait pas avancer le circuit.

## Montants et TVA

HT, TTC et taux de TVA sont enregistrés. Le montant manquant est calculé (HT + taux → TTC, TTC + taux → HT) ; deux
montants saisis sont conservés tels quels (le front signale l'écart et propose de recalculer). Le taux est **recopié**
sur la demande : modifier le référentiel ne change pas les demandes existantes.

Référentiel `FinanceTauxTva` (`/api/finance_taux_tvas`) : lecture pour tous, gestion par le service financier ; un seul
taux par défaut ; un taux qui n'a plus cours est désactivé (plus proposé). Taux en vigueur à créer une fois par
environnement : `php bin/console finance:tva:initialiser` (20 / 10 / 5,5 / 2,1 / 0 %, sans doublon).

## Fournisseurs

Référentiel `/api/finance_fournisseurs` (recherche `?nomFournisseur=`), géré par le service financier : pas de doublon
(même numéro SIFAC ou même raison sociale, casse et espaces ignorés), pas de suppression d'un fournisseur utilisé (409).
Une demande désigne un fournisseur par IRI ; si le fournisseur n'existe pas, le demandeur le propose en clair
(`fournisseurPropose`) et le service financier l'ajoute au référentiel puis le rattache (obligatoire pour `verifier`).

Les listes (`GET /api/finance_bon_commandes`, `/api/finance_receptions`) sont filtrées par
`FinanceVisibiliteExtension` et paginées (30 par défaut, `itemsPerPage` jusqu'à 100).

## Notifications et e-mails

Types déclarés dans `Enum/TypeNotificationEnum` + `Service/Notification/FinanceNotificationTypes` (source `finance`,
public : personnels). `Service/Notification/FinanceNotifier` est appelé par `FinanceTransitionController` après le flush ;
il ne notifie jamais l'auteur de l'action.

| Événement | Type | Destinataires | Par défaut |
|---|---|---|---|
| soumission | `finance.demande_a_viser` | valideurs du département | tout de suite |
| avis favorable / précisions demandées | `finance.avis_rendu` | demandeur (« jamais » interdit) | tout de suite |
| avis favorable | `finance.demande_a_verifier` | gestionnaires du département | résumé |
| avis défavorable, rejet | `finance.demande_rejetee` | demandeur (« jamais » interdit) | tout de suite |
| visa d'engagement | `finance.commande_engagee` | demandeur | résumé |
| bon de commande transmis | `finance.service_fait_a_constater` | demandeur, responsable désigné | tout de suite |
| service fait constaté | `finance.service_fait_a_certifier` | gestionnaires du département | résumé |
| paiement | `finance.commande_payee` | demandeur | résumé |
| récapitulatif | `finance.recapitulatif` | gestionnaires et valideurs | tout de suite |

Destinataires = personnes qui ont **explicitement** le rôle dans le département de la demande
(`Service/Notification/FinanceDestinataires`) ; le super administrateur ne reçoit rien à ce seul titre.
Chaque type est un e-mail paramétrable dans l'éditeur (`/auth/configuration/emails`).

**Récapitulatif** : `php bin/console finance:recapitulatif [--dry-run]` envoie à chaque gestionnaire (toutes les demandes
en cours de ses départements, par statut) et à chaque valideur (demandes à viser) la liste de ce qui attend, avec
l'ancienneté de chaque demande ; rien n'est envoyé s'il n'y a rien en attente. E-mail dédié `finance.recapitulatif`
(`Email/FinanceRecapitulatifEmail`, gabarit `templates/emails/finance/recapitulatif.html.twig`). Planification :
`php bin/console finance:taches:installer` crée (sans doublon) deux tâches, jours ouvrés à 8 h et 13 h 30 ; les horaires se
modifient depuis l'administration des tâches. À lancer une fois par environnement.

## Documents signés

Stockés hors webroot par `Service/FinanceDocumentStorage`, dans `var/finance/documents` (variable
`FINANCE_DOCUMENTS_DIR` pour un volume persistant dédié). PDF uniquement (type vérifié sur le contenu), 10 Mo au plus.
Les anciens dépôts (`public/uploads/finance/…`) restent lisibles par leur seul nom de fichier ; ils peuvent être
déplacés puis le dossier public supprimé. Le fichier est supprimé avec la demande (`FinanceDocumentCleanupListener`).

| Route | Droit |
|---|---|
| `GET /api/finance_bon_commandes/{id}/pdf?step=1..5` | voir la demande |
| `POST /api/finance_bon_commandes/{id}/upload-signed-pdf` | demandeur (brouillon ou soumise), service financier (jusqu'à la clôture) |
| `GET /api/finance_bon_commandes/{id}/download-signed-pdf` | voir la demande |

## Front

Appels API dans `assets/requests/financeService.js` (suffixe `Service`), outils sans Vue dans `assets/utils/finance.js`
(étapes et couleurs, montants, IRI, formats), présentation des statuts dans `assets/utils/workflowConstants.js`.
Chaque étape du circuit a sa couleur (`ETAPES` : bordure gauche des cartes, frise, onglets) ; une carte « Résumé » reste
visible à côté des étapes. Les confirmations passent par `useConfirmation`, les statuts par `<Tag>`.
Configuration (`/finance/configuration`, `ROLE_FINANCE_MANAGER`) : gestion des taux de TVA.

## Migration

Ajouts du lot « circuit » : colonnes `taux_tva`, `fournisseur_propose`, `motif_rejet` de `finance_bon_commande`,
`montant_ht` nullable, table `finance_taux_tva`. `back/migrations` étant ignoré par git, générer la migration sur chaque
environnement (`doctrine:migrations:diff` puis `migrate`), puis lancer `finance:tva:initialiser`.

## Tests

`packages/finance-bundle/tests/Api` (suite `FinanceBundle` de `phpunit.xml.dist` : droits, circuit complet, champs par
étape, montants, référentiels) : noyau en environnement de test,
JWT réel, transaction annulée. Base `<base>_test` (`make test-db`, `back/.env.test.local`) ; sans elle, tests ignorés.

```bash
vendor/bin/phpunit --testsuite FinanceBundle   # + tests/Notification (types), tests/Api (droits, circuit, notifications)
npx vitest run packages/finance-bundle
```
