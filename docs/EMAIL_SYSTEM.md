# Système d'emails – Guide complet

Ce document décrit l'architecture du système d'emails d'UniServices, comment créer un nouvel email dans un package, déclarer ses variables, utiliser les templates, et comment l'éditeur d'administration permet de personnaliser **tous** les messages de l'application (packages et notifications), pour l'établissement ou un département.

---

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Créer un email dans un package](#créer-un-email-dans-un-package)
4. [Déclarer les variables disponibles](#déclarer-les-variables-disponibles)
5. [Créer les templates Twig](#créer-les-templates-twig)
6. [Enregistrer l'email dans le container](#enregistrer-lemail-dans-le-container)
7. [Envoyer un email depuis un service](#envoyer-un-email-depuis-un-service)
8. [Personnalisation par département (interface admin)](#personnalisation-par-département-interface-admin)
9. [Priorité de résolution des templates](#priorité-de-résolution-des-templates)
10. [Sandbox Twig (sécurité)](#sandbox-twig-sécurité)
11. [Fichiers clés](#fichiers-clés)

---

## Vue d'ensemble

Le système d'emails repose sur 4 concepts :

| Concept | Rôle |
|---|---|
| **`AbstractEmailDefinition`** | Classe abstraite décrivant un type d'email (clé, label, sujet, variables, template) |
| **`EmailRegistry`** | Registre global qui agrège toutes les définitions via l'injection de dépendance (tags Symfony) |
| **`EmailTemplateResolver`** | Résout quelle version du template utiliser (BDD > fichier Twig) |
| **`EmailService`** | Point d'entrée unique pour envoyer un email, gère le rendu Twig et le sandbox |

```
Service métier
    │
    ▼
EmailService::send(key, to, context, departement)
    │
    ▼
EmailTemplateResolver::resolve()
    ├── Entité EmailTemplate en BDD (personnalisation département) ← priorité haute
    └── Fichier Twig du package (défaut)                          ← priorité basse
    │
    ▼
Rendu Twig (normal ou sandboxé)
    │
    ▼
MailerInterface::send()
```

---

## Architecture

```
back/
├── src/
│   ├── Services/Email/
│   │   ├── AbstractEmailDefinition.php   ← classe de base à étendre
│   │   ├── EmailRegistry.php             ← registre global (auto-rempli)
│   │   ├── EmailService.php              ← service d'envoi
│   │   ├── EmailTemplateResolver.php     ← logique de priorité BDD > fichier
│   │   └── ResolvedEmailTemplate.php    ← DTO résultat de la résolution
│   ├── Entity/Email/
│   │   └── EmailTemplate.php            ← entité Doctrine (personnalisations BDD)
│   ├── Repository/Email/
│   │   └── EmailTemplateRepository.php  ← accès BDD
│   └── Controller/
│       └── EmailTemplateController.php  ← API REST pour l'interface admin
│
└── templates/emails/
    ├── layout.html.twig                 ← layout commun HTML
    └── layout.txt.twig                  ← layout commun texte

packages/{bundle}/
├── src/Email/
│   └── MonEmail.php                     ← définition de l'email
└── templates/emails/{bundle}/
    ├── mon_email.html.twig              ← template par défaut HTML
    └── mon_email.txt.twig              ← template par défaut texte (optionnel)
```

---

## Créer un email dans un package

### 1. Créer la classe de définition

Dans `packages/mon-bundle/src/Email/`, créer une classe qui étend `AbstractEmailDefinition` :

```php
<?php

namespace MonBundle\Email;

use App\Service\Email\AbstractEmailDefinition;

/**
 * Email envoyé quand une absence est enregistrée pour un étudiant.
 */
final class AbsenceNotificationEmail extends AbstractEmailDefinition
{
    /**
     * Clé unique : convention "{bundle}.{nom_email}"
     * Doit être unique dans tout le monorepo.
     */
    public function getKey(): string
    {
        return 'absences.notification';
    }

    /**
     * Label affiché dans l'interface d'administration.
     */
    public function getLabel(): string
    {
        return 'Notification d\'absence';
    }

    /**
     * Objet par défaut. Peut contenir des variables Twig.
     */
    public function getDefaultSubject(): string
    {
        return 'Absence enregistrée le {{ absence.date|date(\'d/m/Y\') }}';
    }

    /**
     * Chemin du template HTML, relatif au dossier templates/ (résolu par Twig).
     */
    public function getHtmlTemplatePath(): string
    {
        return 'emails/absences/notification.html.twig';
    }

    /**
     * Variables exposées dans le template, avec leur description.
     * C'est également ce qui est affiché dans l'interface d'administration.
     */
    public function getAvailableVariables(): array
    {
        return [
            'etudiant'   => 'Objet Etudiant (nom, prenom, email)',
            'absence'    => 'Objet Absence (date, duree, motif)',
            'formation'  => 'Libellé de la formation (string)',
        ];
    }

    /**
     * Description affichée dans l'interface admin (optionnelle).
     */
    public function getDescription(): string
    {
        return 'Envoyé à l\'étudiant quand une absence est saisie dans le système.';
    }
}
```

### Conventions de nommage des clés

| Pattern | Exemple |
|---|---|
| `{bundle}.{action}` | `questionnaire.invitation` |
| `{bundle}.{entite}_{action}` | `helpdesk.ticket_created` |
| `core.{action}` | `core.reset_password` |

Les clés doivent être en **snake_case**, séparées par un point.

---

## Déclarer les variables disponibles

`getAvailableVariables()` retourne un tableau `['nom_variable' => 'Description']`.

### Règle importante

**Les clés déclarées ici doivent correspondre exactement aux clés passées dans le `context` lors de l'envoi.**

```php
// Définition
public function getAvailableVariables(): array
{
    return [
        'etudiant' => 'Objet Etudiant',  // ← clé 'etudiant'
        'absence'  => 'Objet Absence',   // ← clé 'absence'
    ];
}

// Envoi – les clés du context doivent matcher
$emailService->send(
    emailKey: 'absences.notification',
    to: $etudiant->getMailUniv(),
    context: [
        'etudiant' => $etudiant,  // ← même clé
        'absence'  => $absence,   // ← même clé
    ],
);
```

### Variables automatiques (injectées par EmailService)

Ces variables sont **toujours disponibles** dans les templates sans les déclarer :

| Variable | Valeur |
|---|---|
| `departement_libelle` | Libellé du département (si fourni) |
| `departement_couleur` | Couleur hex du département (ex: `#1a3a5c`) |

### Variables optionnelles

Pour les variables qui ne sont pas toujours présentes, utilisez `|default()` dans le template :

```twig
{{ expiresAt|date('d/m/Y')|default('Non définie') }}
```

Et dans `getAvailableVariables()`, mentionnez-le dans la description :

```php
'expiresAt' => 'Date d\'expiration (optionnel, peut être null)',
```

---

## Créer les templates Twig

### Template HTML

Créer `packages/mon-bundle/templates/emails/absences/notification.html.twig` :

```twig
{% extends 'emails/layout.html.twig' %}

{% block subject %}Absence enregistrée – {{ etudiant.prenom }} {{ etudiant.nom }}{% endblock %}

{% block preheader %}Une absence a été enregistrée pour {{ etudiant.prenom }} le {{ absence.date|date('d/m/Y') }}.{% endblock %}

{% block body %}
    <h1>Notification d'absence</h1>

    <p>Bonjour {{ etudiant.prenom }},</p>

    <p>
        Une absence a été enregistrée pour vous le
        <strong>{{ absence.date|date('d/m/Y') }}</strong>.
    </p>

    {% if absence.motif %}
    <p>Motif : <em>{{ absence.motif }}</em></p>
    {% endif %}

    <p>
        Formation : {{ formation }}
    </p>

    <hr class="divider">

    <p style="font-size:13px;color:#718096;">
        Si vous pensez que cette absence est erronée, contactez votre département.
    </p>
{% endblock %}
```

### Blocs disponibles dans le layout

| Bloc | Description |
|---|---|
| `{% block subject %}` | Sujet de l'email (optionnel, remplace le sujet par défaut) |
| `{% block preheader %}` | Texte d'aperçu visible dans le client mail avant ouverture |
| `{% block body %}` | **Contenu principal** — obligatoire |

### Classes CSS prédéfinies dans le layout

| Classe | Rendu |
|---|---|
| `class="btn"` | Bouton d'appel à l'action (fond coloré, centré) |
| `class="alert"` | Bloc d'alerte (fond orange clair) |
| `class="divider"` | Séparateur horizontal (`<hr>`) |

### Template texte (optionnel)

Créer `packages/mon-bundle/templates/emails/absences/notification.txt.twig` :

```twig
{% extends 'emails/layout.txt.twig' %}

{% block body %}
Notification d'absence
----------------------

Bonjour {{ etudiant.prenom }},

Une absence a été enregistrée pour vous le {{ absence.date|date('d/m/Y') }}.
{% if absence.motif %}Motif : {{ absence.motif }}{% endif %}

Formation : {{ formation }}

Si vous pensez que cette absence est erronée, contactez votre département.
{% endblock %}
```

> Si le fichier `.txt.twig` n'existe pas, `EmailService` génère automatiquement le texte brut en supprimant les balises HTML.

---

## Enregistrer l'email dans le container

Dans `packages/mon-bundle/config/services.yaml`, ajouter la règle `instanceof` :

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    MonBundle\:
        resource: '../src/'
        exclude: '../src/{Entity,Repository,Tests}'

    _instanceof:
        App\Service\Email\AbstractEmailDefinition:
            tags: [ 'app.email_definition' ]
```

Le tag `app.email_definition` permet à l'`EmailRegistry` de collecter automatiquement toutes les définitions via `!tagged_iterator`.

> **Vérification** : Après avoir créé votre classe, lancez :
> ```bash
> php bin/console debug:container --tag=app.email_definition
> ```
> Votre classe doit apparaître dans la liste.

---

## Envoyer un email depuis un service

Injectez `EmailService` dans votre service métier :

```php
use App\Service\Email\EmailService;
use App\Entity\Structure\StructureDepartement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AbsenceService
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function notifierAbsence(
        Etudiant $etudiant,
        Absence $absence,
        StructureDepartement $departement,
    ): void {
        $this->emailService->send(
            emailKey: 'absences.notification',
            to: $etudiant->getMailUniv(),
            context: [
                'etudiant'  => $etudiant,
                'absence'   => $absence,
                'formation' => $etudiant->getSemestre()?->getDiplome()?->getLibelle() ?? '',
            ],
            departement: $departement,  // ← permet la résolution du template personnalisé
        );
    }
}
```

### Paramètres de `send()`

| Paramètre | Type | Description |
|---|---|---|
| `emailKey` | `string` | Clé unique de l'email (ex: `absences.notification`) |
| `to` | `string\|string[]` | Un ou plusieurs destinataires |
| `context` | `array` | Variables passées au template Twig |
| `departement` | `?StructureDepartement` | Département pour la résolution BDD (optionnel) |
| `replyTo` | `?string` | Adresse de réponse (optionnel) |
| `locale` | `string` | Locale (défaut: `fr`) |

---

## Personnalisation (éditeur d'administration)

L'éditeur est accessible dans **Portail → Configuration → Communication → Modèles de mails** (`/auth/configuration/emails`).

> Accès requis : `SUPER_ADMIN`.

### Ce qui est paramétrable

- Tous les e-mails déclarés par le cœur et les packages (`AbstractEmailDefinition`).
- **Chaque type de notification** : l'`EmailRegistry` crée automatiquement une `NotificationEmailDefinition` (même clé
  que le type, ex. `stage.convention_validee`) pour tout type qui n'a pas d'e-mail dédié. Elle utilise le gabarit
  générique `notification.immediate` et **hérite de sa personnalisation** tant qu'elle n'est pas personnalisée elle-même
  (`getParentKey()`). Un package n'a donc rien à écrire pour que ses notifications soient paramétrables.
- Les e-mails sont regroupés par application (clé du package : « Stage », « Unifolio »… ; « Core » pour les modèles communs).

### Niveaux

- **Établissement** (aucun département sélectionné) : vaut pour tout l'établissement.
- **Département** : prime sur l'établissement, pour les envois qui fournissent `departement` à `EmailService::send()`
  (les notifications n'en fournissent pas : elles utilisent le niveau établissement).

### Éditeur

- On part du **message réellement envoyé** : personnalisation du niveau, sinon le contenu du bloc `body` du gabarit du
  package (`EmailService::corpsParDefaut()`).
- Insertion des variables déclarées et de fragments (paragraphe, bouton, encadré, condition) à la position du curseur.
- **Vérification à l'enregistrement** : le modèle est rendu dans le sandbox avec des valeurs d'exemple ; une construction
  interdite ou une erreur de syntaxe est refusée (422) avec un message indiquant la ligne.
- **Aperçu** (valeurs d'exemple `[nom_variable]`, dates et liens fictifs) et **envoi d'un test** à soi-même.
- **Réinitialiser** supprime la personnalisation du niveau : le niveau supérieur reprend la main.

API (`EmailTemplateController`, `shared/requests/emailTemplateService.js`) :

| Méthode | Route | Rôle |
|---|---|---|
| GET | `/api/email/definitions?departement=` | E-mails groupés, avec `personnalisation` : `departement`, `etablissement`, `parent`, `defaut` |
| GET | `/api/email/templates/{key}?departement=` | Modèle du niveau + `defaultSubject`, `defaultBodyHtml`, `parentKey`, variables |
| POST | `/api/email/templates` | Enregistre (après vérification) |
| POST | `/api/email/apercu` | `{ subject, html }` avec des valeurs d'exemple |
| POST | `/api/email/test` | Envoie l'aperçu à l'administrateur connecté |
| DELETE | `/api/email/templates/{id}` | Supprime la personnalisation |

---

### Habillage de l'établissement et mentions imposées

Les gabarits par défaut sont des fichiers Twig versionnés : `back/templates/emails/` (layout commun `layout.html.twig`
/ `layout.txt.twig`, modèles du cœur dans `core/`) et `packages/<package>/templates/emails/`. Chaque e-mail ne définit
que son bloc `body` ; **l'en-tête, la signature et le pied de page appartiennent au layout** et sont toujours imposés :
une personnalisation ne remplace que le corps (`EmailTemplate::useLayout` est ignoré).

L'écran **Habillage des e-mails** (`/auth/configuration/emails/habillage`, API `GET|PUT /api/email/habillage`,
`POST /api/email/habillage/apercu`) règle, pour l'établissement (`Etablissement::getHabillageEmails()`, dans `settings`) :

| Réglage | Effet |
|---|---|
| `couleur` | Couleur principale (un département qui a sa propre couleur l'utilise pour ses e-mails) |
| `expediteur_nom`, `reply_to` | Nom de l'expéditeur, adresse de réponse par défaut |
| `signature` | Ajoutée sous le corps de chaque e-mail |
| `mentions_legales`, `contact_rgpd` | Pied de page : mentions de l'établissement, exercice des droits RGPD |

`EmailService::habiller()` fournit ces valeurs au layout (`habillage`, `couleur_principale`) et **écrase** toute variable
du même nom passée par un package. Le logo et le nom viennent des données de l'établissement.

### Catégories : ce que le destinataire peut couper

`AbstractEmailDefinition::getCategorie()` :

- `notification` : réception réglée par le destinataire ; le pied de page porte **toujours** le lien « gérer mes
  préférences de notification ». Un type de notification peut interdire « Jamais » (`modesAutorises`, ex.
  `stage.convention_refusee`) : l'éditeur l'affiche comme « ne peut pas être désactivée ».
- `transactionnel` (défaut) : mot de passe, invitations, liens à usage unique… toujours envoyé, sans lien de
  désabonnement, mais avec les mentions légales.


## Priorité de résolution des templates

Lors d'un envoi avec un `$departement` fourni, la résolution suit cet ordre :

```
1. EmailTemplate en BDD pour ce département + cette clé
        ↓ (si absent)
2. EmailTemplate en BDD de l'établissement (sans département) pour cette clé
        ↓ (si absent)
3. Personnalisation (département, puis établissement) de l'e-mail parent (getParentKey(), ex. notification.immediate)
        ↓ (si absent)
4. Fichier Twig du package (getHtmlTemplatePath())
```

Une personnalisation qui échoue au rendu (modèle invalide écrit hors de l'éditeur…) **n'empêche jamais l'envoi** :
l'erreur est journalisée et le gabarit du package est utilisé.

À l'envoi, les liens et images relatifs (`href="/app/…"`) sont rendus **absolus** avec `URL_FRONTEND` : un package peut
passer des chemins internes (`/app/stage/etudiant`).

Le rendu diffère selon la source :

| Source | Rendu |
|---|---|
| Fichier Twig | Rendu Twig standard (`twig->render()`) |
| Base de données | Rendu **sandboxé** (Twig avec restrictions de sécurité) |

---

## Sandbox Twig (sécurité)

Les templates stockés en base de données (personnalisations) — **objet compris** — sont rendus dans un **environnement Twig sandboxé** pour éviter que les utilisateurs n'exécutent du code arbitraire. L'objet est ensuite converti en texte brut (pas d'entités HTML).

Conséquence pour les packages : **passer des valeurs simples** (chaînes, nombres, dates) dans le `context`, pas des
entités — le sandbox n'autorise aucun appel de méthode, et un modèle personnalisé ne pourrait pas lire `{{ etudiant.nom }}`.

### Ce qui est autorisé

| Élément | Autorisé |
|---|---|
| **Tags** | `if`, `for`, `set`, `block`, `spaceless` |
| **Filtres** | `e`, `escape`, `upper`, `lower`, `date`, `nl2br`, `trim`, `length`, `default`, `raw` |
| **Fonctions** | `date` |
| **Méthodes** | aucune (accès aux propriétés public uniquement) |

### Ce qui est interdit

- Appels de méthodes PHP : `{{ objet.methode() }}`
- Inclusion de fichiers : `{% include %}`, `{% import %}`
- Extensions Twig personnalisées non listées
- Tout accès à des variables non déclarées dans `getAvailableVariables()`

> Pour les templates par défaut (fichiers Twig dans les packages), **aucune restriction ne s'applique** — vous avez accès à tout Twig.

---

## Fichiers clés

| Fichier | Description |
|---|---|
| [`back/src/Services/Email/AbstractEmailDefinition.php`](../back/src/Service/Email/AbstractEmailDefinition.php) | Classe abstraite de base |
| [`back/src/Services/Email/EmailService.php`](../back/src/Service/Email/EmailService.php) | Service d'envoi |
| [`back/src/Services/Email/EmailTemplateResolver.php`](../back/src/Service/Email/EmailTemplateResolver.php) | Logique de résolution BDD > fichier |
| [`back/src/Services/Email/EmailRegistry.php`](../back/src/Service/Email/EmailRegistry.php) | Registre global |
| [`back/src/Entity/Email/EmailTemplate.php`](../back/src/Entity/Email/EmailTemplate.php) | Entité de personnalisation |
| [`back/src/Controller/EmailTemplateController.php`](../back/src/Controller/EmailTemplateController.php) | API REST admin |
| [`back/templates/emails/layout.html.twig`](../back/templates/emails/layout.html.twig) | Layout HTML commun |
| [`back/templates/emails/layout.txt.twig`](../back/templates/emails/layout.txt.twig) | Layout texte commun |
| [`packages/auth-bundle/assets/views/configuration/emails/EmailsView.vue`](../packages/auth-bundle/assets/views/configuration/emails/EmailsView.vue) | Interface admin – liste |
| [`packages/auth-bundle/assets/views/configuration/emails/EmailEditView.vue`](../packages/auth-bundle/assets/views/configuration/emails/EmailEditView.vue) | Interface admin – éditeur |
| [`packages/questionnaire-bundle/src/Email/QuestionnaireInvitationEmail.php`](../packages/questionnaire-bundle/src/Email/QuestionnaireInvitationEmail.php) | Exemple d'implémentation |

---

## Exemple complet : email de rappel helpdesk

### 1. Définition

`packages/helpdesk-bundle/src/Email/TicketRappelEmail.php`

```php
final class TicketRappelEmail extends AbstractEmailDefinition
{
    public function getKey(): string        { return 'helpdesk.ticket_rappel'; }
    public function getLabel(): string      { return 'Rappel de ticket en attente'; }
    public function getDefaultSubject(): string
    {
        return '[HelpDesk] Rappel – Ticket #{{ ticket.id }} : {{ ticket.titre }}';
    }
    public function getHtmlTemplatePath(): string
    {
        return 'emails/helpdesk/ticket_rappel.html.twig';
    }
    public function getAvailableVariables(): array
    {
        return [
            'ticket'      => 'Objet Ticket (id, titre, statut, createdAt)',
            'demandeur'   => 'Objet Personnel demandeur (nom, prenom, email)',
            'ticketUrl'   => 'URL directe vers le ticket',
            'joursOuverts'=> 'Nombre de jours depuis l\'ouverture (integer)',
        ];
    }
    public function getDescription(): string
    {
        return 'Envoyé automatiquement après X jours sans réponse sur un ticket.';
    }
}
```

### 2. Template HTML

`packages/helpdesk-bundle/templates/emails/helpdesk/ticket_rappel.html.twig`

```twig
{% extends 'emails/layout.html.twig' %}

{% block preheader %}Votre ticket #{{ ticket.id }} attend une réponse depuis {{ joursOuverts }} jour(s).{% endblock %}

{% block body %}
    <h1>Rappel – Ticket en attente</h1>

    <p>Bonjour {{ demandeur.prenom }},</p>

    <p>
        Votre ticket <strong>#{{ ticket.id }} – {{ ticket.titre }}</strong>
        est ouvert depuis <strong>{{ joursOuverts }} jour(s)</strong> et attend toujours une réponse.
    </p>

    <p>
        <a href="{{ ticketUrl }}" class="btn">Voir le ticket</a>
    </p>
{% endblock %}
```

### 3. Envoi

```php
$this->emailService->send(
    emailKey: 'helpdesk.ticket_rappel',
    to: $ticket->getDemandeur()->getMailUniv(),
    context: [
        'ticket'       => $ticket,
        'demandeur'    => $ticket->getDemandeur(),
        'ticketUrl'    => $this->urlGenerator->generate(
            'helpdesk_ticket_show',
            ['id' => $ticket->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        ),
        'joursOuverts' => (new \DateTime())->diff($ticket->getCreatedAt())->days,
    ],
    departement: $ticket->getDepartement(),
);
```
