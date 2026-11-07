# Système de notifications – Guide complet

Un mécanisme **commun à toute l'application** pour prévenir les gens : la cloche de la barre du haut, les e-mails
immédiats et un **résumé groupé** qui évite le spam. Chaque package déclare ses types de notification et appelle un
seul service ; le reste (préférences, regroupement, cloche, écran de réglages) est fourni par le cœur.

## Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Brancher un package en 3 étapes](#brancher-un-package-en-3-étapes)
- [Déclarer un type de notification](#déclarer-un-type-de-notification)
- [Envoyer une notification](#envoyer-une-notification)
- [Écrire de bons textes et de bons liens](#écrire-de-bons-textes-et-de-bons-liens)
- [Conserver un e-mail dédié](#conserver-un-e-mail-dédié)
- [Ce que voit l'utilisateur](#ce-que-voit-lutilisateur)
- [Planification (résumés)](#planification-résumés)
- [API HTTP](#api-http)
- [Front : cloche, store, page de préférences](#front--cloche-store-page-de-préférences)
- [Tester](#tester)
- [Règles de conception et pièges](#règles-de-conception-et-pièges)
- [Exemple complet](#exemple-complet)

## Vue d'ensemble

```
 package ──► NotificationService::notifier(destinataire, 'package.evenement', texte, url)
                   │
                   ├─► journal `notification`  ──► cloche (toujours, quelles que soient les préférences e-mail)
                   │
                   └─► préférence de la personne pour CE type :
                         tout de suite ─► e-mail immédiat
                         dans le résumé ─► mis de côté (fusionné avec les identiques)
                                           └─► `app:notifications:resume` : UN e-mail groupé par personne
                         jamais ────────► aucun e-mail
```

Principes :

- **La cloche reçoit toujours tout.** Les préférences ne concernent que les e-mails : « Jamais » veut dire « pas d'e-mail »,
  pas « pas d'information ».
- **Trois modes par type** : `immediat`, `resume`, `aucun` ; plus un **interrupteur général** des e-mails et le **rythme du
  résumé** (chaque soir à 18 h, ou chaque lundi à 8 h), au choix de chaque personne.
- **Anti-spam** : un seul résumé par personne et par passage pour *toute* l'application (toutes sources, une rubrique par
  package et par thème) ; événements identiques (même type, même lien) **fusionnés** (« ×10 ») ; préférences relues
  **à l'envoi** ; purge du journal à 90 jours.
- **Jamais bloquant** : une notification qui échoue (type inconnu, SMTP en panne…) est journalisée puis absorbée, elle ne
  fait jamais échouer l'action métier qui la déclenche.

Classes (cœur, `back/src/`) :

| Élément | Rôle |
|---|---|
| `App\Notification\NotificationType` | Déclaration d'un type (clé, libellés, public, mode par défaut, modes autorisés) |
| `App\Notification\NotificationTypeProviderInterface` | À implémenter par un package pour déclarer ses types |
| `App\Notification\NotificationTypeRegistry` | Ensemble des types de tous les packages |
| `App\Notification\NotificationService` | **Point d'entrée unique** : `notifier(...)` |
| `App\Notification\NotificationDigest` | Construit et envoie le résumé groupé |
| `App\Entity\Notification\Notification` | Journal (cloche + file du résumé), table `notification` |
| `App\Entity\Notification\PreferenceNotification` | Réglages d'une personne, table `notification_preference` |
| `App\Security\UserPackageResolver` | Packages dont dispose une personne (source unique, partagée avec le contexte de sécurité) |
| `App\Controller\NotificationController` | API `/api/notifications/*` |
| `App\Command\NotificationDigestCommand` | `app:notifications:resume` |
| `App\Command\NotificationTachesCommand` | `app:notifications:taches` (crée les tâches planifiées) |

Front (`shared/`) : `NotificationBell.vue` (cloche, dans la barre du haut), `notificationStore.js` (Pinia),
`pages/NotificationPreferences.vue` (route `/auth/notifications`).

## Brancher un package en 3 étapes

1. **Déclarer** ses types : une classe qui implémente `NotificationTypeProviderInterface` (rien à configurer, elle est
   enregistrée automatiquement).
2. **Notifier** : appeler `NotificationService::notifier()` là où l'événement se produit.
3. *(Facultatif)* **Garder un gabarit d'e-mail dédié** pour le mode « tout de suite ».

La cloche, l'écran de préférences et le résumé fonctionnent alors sans autre travail.

## Déclarer un type de notification

```php
namespace StageBundle\Service\Notification;

use App\Enum\ModeNotificationEnum;
use App\Notification\NotificationType;
use App\Notification\NotificationTypeProviderInterface;

final class StageNotificationTypes implements NotificationTypeProviderInterface
{
    public function getTypes(): iterable
    {
        yield new NotificationType(
            cle: 'stage.convention_validee',                 // « package.evenement », minuscules et _
            source: 'stage',                                  // identifiant du package = préfixe de la clé
            sourceLibelle: 'Stages',                          // nom affiché (cloche, préférences, résumé)
            libelle: 'Ma convention de stage est validée',    // phrase des préférences
            description: 'Le service des stages valide votre convention.',
            rubrique: 'Conventions',                          // titre de la rubrique dans le résumé
            audience: NotificationType::AUDIENCE_ETUDIANT,    // ou AUDIENCE_PERSONNEL
            modeParDefaut: ModeNotificationEnum::IMMEDIAT,
            modesAutorises: null,                             // null = les trois modes
        );
    }
}
```

| Champ | Règle |
|---|---|
| `cle` | `^[a-z0-9_]+\.[a-z0-9_]+$`, doit commencer par `source.` ; **unique dans l'application** (un doublon lève une exception au premier usage) |
| `source` | **Nom du package tel qu'il figure dans les packages activés** (applications d'un étudiant, packages du département d'un personnel : `unifolio`, `stage`, `documents`…). Les préférences ne proposent le type qu'aux personnes qui disposent de ce package ; `auth` et `core` sont toujours disponibles, le super administrateur voit tout |
| `audience` | `etudiant` ou `personnel` : seules les personnes de ce public voient le type dans leurs préférences, et notifier l'autre public est ignoré |
| `modeParDefaut` | Ce qui appelle une **réaction rapide** → `IMMEDIAT` ; l'information de fond → `RESUME` |
| `modesAutorises` | Restreindre quand le métier l'impose (voir [Règles](#règles-de-conception-et-pièges)) ; le mode par défaut doit en faire partie |

Astuce : si les types d'un package sont déjà un enum (c'est le cas d'UniFolio, `TypeNotificationEnum`), exposez une méthode
`definition(): NotificationType` et faites boucler le fournisseur sur `cases()`.

Le fournisseur est étiqueté `app.notification_type_provider` par le Kernel
(`registerForAutoconfiguration`) : tant que le service est **autoconfiguré** (cas par défaut des `services.yaml` de
package), il n'y a rien à écrire.

## Envoyer une notification

```php
use App\Notification\NotificationService;

final class ConventionManager
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function valider(Convention $c): void
    {
        // … logique métier, flush …

        $this->notifications->notifier(
            $c->getEtudiant(),                                   // Etudiant ou Personnel
            'stage.convention_validee',                          // clé déclarée
            sprintf('Votre convention avec %s a été validée.', $c->getEntreprise()),
            '/app/stage/conventions/'.$c->getUuidString(),       // où mène le clic (chemin interne)
        );
    }
}
```

Signature : `notifier(Etudiant|Personnel $destinataire, string $typeCle, string $texte, ?string $url = null, ?callable $envoiImmediat = null): ?Notification`.

- Appelez-la **après** le `flush` de l'action métier, jamais avant.
- Une personne par appel : pour plusieurs destinataires, bouclez (la fusion évite le bruit).
- Le retour est `null` si la notification a été ignorée (type inconnu, mauvais public) : à ne pas traiter comme une erreur.
- Elle ne lève pas d'exception ; elle journalise (`warning`).

## Écrire de bons textes et de bons liens

- **Texte** : une phrase complète, lisible hors contexte, avec **qui** et **quoi**
  (« Alice Dupont a commenté votre trace « Maquette ». »). Limité à 500 caractères (tronqué au-delà).
- **Lien** : un **chemin interne** de l'application, avec le préfixe `/app` comme dans les e-mails
  (`/app/unifolio/traces/…`). Tout ce qui n'est pas un chemin interne (`https://…`, `//hôte`, `javascript:…`, espace,
  antislash, plus de 255 caractères) est **refusé** à la création : la cloche ne peut pas servir de lien d'hameçonnage.
  Un lien est facultatif.
- **Fusion** : deux notifications du même type et du **même lien** destinées à la même personne, tant que la première attend
  son résumé, n'en forment qu'une (« ×2 », texte le plus récent). Choisissez donc un lien qui identifie *l'objet concerné*
  (la trace, la convention), pas la page d'accueil.

## Conserver un e-mail dédié

Par défaut, le mode « tout de suite » envoie l'e-mail générique `notification.immediate`. Pour garder un gabarit riche (voir
`docs/EMAIL_SYSTEM.md`), passez une fonction d'envoi : elle n'est appelée que si la personne a choisi « tout de suite ».

```php
$this->notifications->notifier(
    $etudiant,
    'unifolio.evaluation_rendue',
    sprintf('%s a évalué votre trace « %s ».', $evaluateur->getDisplay(), $trace->getTitre()),
    '/app/unifolio/traces/'.$trace->getUuidString(),
    fn (): bool => $this->envoyerMonEmail(...),   // true si l'e-mail est parti
);
```

- Retournez `true` si l'e-mail est parti. Si vous retournez `false` (SMTP en panne…), la notification reste en attente et
  **partira dans le prochain résumé** : rien n'est perdu.
- Sans adresse e-mail, l'événement n'est pas conservé pour un résumé qui ne partirait jamais.

## Ce que voit l'utilisateur

- **La cloche** (barre du haut, toutes les applications) : pastille de non-lues, panneau des 20 dernières notifications avec
  le nom du package, la date, « ×N » pour les fusions, « Tout marquer comme lu », lien vers les préférences. Cliquer ouvre
  le lien et marque la notification lue. Le compteur se rafraîchit toutes les minutes (onglet visible).
- **Préférences** (menu du profil → *Notifications*, route `/auth/notifications`) : limitées aux **packages dont la personne dispose** (règle de `App\Security\UserPackageResolver`, la même que le contexte de sécurité du front) ; interrupteur général des e-mails,
  rythme du résumé, et pour chaque type, regroupés par package, un choix *Tout de suite / Dans le résumé / Jamais*
  (seuls les modes autorisés sont proposés). Chaque changement est enregistré immédiatement.
- **Le résumé** : un e-mail unique « N nouveauté(s) de la journée / de la semaine », rubriques « Package — Rubrique »
  (8 lignes par rubrique puis « … et N autres »), lien de désinscription vers les préférences.

## Planification (résumés)

Le résumé est une commande lancée par le planificateur de l'application (table `SchedulerTask`) :

```bash
php bin/console app:notifications:taches            # crée (sans doublon) les tâches : résumé quotidien 18 h, hebdomadaire lundi 8 h
php bin/console app:notifications:taches --inactives # …créées désactivées, à activer depuis l'administration des tâches
php bin/console app:notifications:resume --frequence=quotidien [--dry-run]
```

`--dry-run` compte ce qui serait envoyé sans rien envoyer ni modifier. À lancer **une fois par environnement** après
déploiement. Un package qui a ses propres tâches périodiques fournit sa propre commande d'installation
(exemple : `unifolio:taches:installer` pour la relance des évaluateurs).

## API HTTP

Toutes les routes exigent d'être connecté et ne livrent que **ses propres** notifications.

| Méthode | Route | Rôle |
|---|---|---|
| GET | `/api/notifications?limite=30&nonLues=1` | Dernières notifications (max 50) + `nonLues` |
| GET | `/api/notifications/compteur` | `{ nonLues }`, léger (pastille de la cloche) |
| POST | `/api/notifications/{uuid}/lue` | Marque lue (404 si elle n'est pas à vous) |
| POST | `/api/notifications/lues` | Tout marquer comme lu |
| GET | `/api/notifications/preferences` | Réglages + types disponibles pour votre public |
| PUT | `/api/notifications/preferences` | `{ actif?, frequence?, reglages?: { "<cle>": "<mode>" } }` — champs partiels ; 422 si type ou mode non permis |

## Front : cloche, store, page de préférences

Rien à faire pour qu'un package alimente la cloche : elle est déjà dans la barre du haut (`AppTopbar.vue`).

- **Store** : `useNotificationStore()` (`@stores/notificationStore`) — `compter()`, `charger()`, `marquerLue()`, `toutLire()`.
- **Liens** : `cheminRouteur(url)` convertit `/app/xxx` en chemin du routeur (monté sous `/app/`) et renvoie `null` pour tout
  ce qui n'est pas interne.
- **Préférences** : la page est partagée (`@components/pages/NotificationPreferences.vue`) ; un package ne crée **pas** son
  propre écran, ses types apparaissent automatiquement sous son nom.

## Tester

- **Unitaire** : `NotificationType`, `PreferenceNotification` et `Notification` sont des objets simples, testables sans
  base (voir `tests/Notification/NotificationSystemTest.php`). Testez aussi que **toutes les définitions de votre package
  sont valides** (`new NotificationType(...)` lève une exception sinon).
- **De bout en bout** : lancez avec `MAILER_DSN=null://null` (aucun vrai e-mail) et capturez les messages avec un écouteur sur
  `Symfony\Component\Mailer\Event\MessageEvent` — ignorez `isQueued()` pour ne pas compter chaque message deux fois.
  Vérifiez : journal créé, e-mail immédiat ou non selon le mode, un seul e-mail de résumé, fusion, cloche indépendante des
  préférences, nettoyage de vos données de test.
- **Front** : mockez `@helpers/axios` ; ne testez pas la cloche à travers votre écran, elle est déjà couverte.

## Règles de conception et pièges

- **Ne jamais interdire « jamais » sans raison.** Restreignez `modesAutorises` seulement quand le métier l'exige : un rappel
  périodique n'a pas de sens « dans un résumé » (`IMMEDIAT`, `AUCUN`) ; une décision grave (retrait par la modération) ne
  doit pas pouvoir être ignorée (`IMMEDIAT`, `RESUME`). La cloche, elle, reçoit toujours tout.
- **Un type = un événement métier**, pas un écran. Peu de types, bien nommés : chacun devient une ligne de préférences.
- **Pas de données sensibles dans le texte** : il est stocké 90 jours et envoyé par e-mail.
- **Un événement répétitif ne doit pas multiplier les types** : gardez un seul type et un lien stable pour profiter de la fusion.
- **Ne notifiez pas l'auteur de l'action.** Le service ne le filtre pas : c'est à l'appelant de choisir les destinataires.
- **Changer la clé d'un type** orpheline les anciennes préférences et notifications (elles retombent sur le défaut / sont
  écartées du résumé) : choisissez-la une fois pour toutes.
- **Retirer un type** : ses notifications déjà en attente sont écartées du résumé, jamais envoyées.
- **Les packages inaccessibles ne s'affichent pas, mais n'empêchent pas la notification** : l'événement existe, la cloche le reçoit, seul le réglage n'est pas proposé. N'envoyez donc pas à quelqu'un qui n'a pas le package.
- **Visibilité d'un portfolio, simples modifications, accusés de réception…** : pas de notification. Ce n'est pas l'objet.

## Exemple complet

UniFolio est le premier consommateur :

- Types : `packages/unifolio-bundle/src/Enum/TypeNotificationEnum.php` + `Service/Notification/UnifolioNotificationTypes.php`
  (7 types : évaluation communiquée, révision demandée, commentaire d'un enseignant, publication retirée par la modération,
  trace déposée, commentaire d'un étudiant, rappel des évaluations).
- Appels : `EvaluationNotifier` (avec e-mails dédiés conservés via `$envoiImmediat`), `TraceManager::deposer()`,
  `CommentaireService::ajouter()`, `PublicationManager::moderer()`.
- Tâche périodique propre : `unifolio:taches:installer` (relance des évaluateurs).

Stages est le deuxième consommateur (détail : `packages/stage-bundle/docs/notifications.md`) :

- Types : `packages/stage-bundle/src/Enum/TypeNotificationEnum.php` + `Service/Notification/StageNotificationTypes.php`
  (11 types, étudiants et personnels), sans gabarit d'e-mail dédié (e-mail générique).
- Appels regroupés dans `StageNotifier`, déclenché par le processeur API Platform des dossiers
  (`State/StageEtudiantProcessor`, à partir du statut avant / après), celui des avenants et les contrôleurs
  (validation d'avenant, planification des soutenances).
