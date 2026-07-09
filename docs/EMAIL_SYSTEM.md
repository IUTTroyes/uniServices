# Système d'emails – Guide complet

Ce document décrit l'architecture du système d'emails d'UniServices, comment créer un nouvel email dans un package, déclarer ses variables, utiliser les templates, et comment l'interface d'administration permet aux départements de personnaliser les messages.

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

use App\Services\Email\AbstractEmailDefinition;

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
        App\Services\Email\AbstractEmailDefinition:
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
use App\Services\Email\EmailService;
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

## Personnalisation par département (interface admin)

L'interface d'administration est accessible dans :  
**Portail → Configuration → Communication → Modèles de mails**

> Accès requis : rôle `ROLE_SUPER_ADMIN`

### Fonctionnement

1. La page liste tous les emails enregistrés, **groupés par bundle**.
2. Un sélecteur de département permet de voir quels emails ont été personnalisés pour un département donné.
3. En cliquant sur un email, on accède à l'éditeur.

### Éditeur de template

L'éditeur permet de modifier :
- **L'objet du mail** — supporte les variables Twig (`{{ variable }}`)
- **Le corps HTML** — HTML complet, intégré automatiquement dans le layout commun

Le panneau latéral affiche :
- Les **variables disponibles** déclarées dans `getAvailableVariables()` — cliquer pour copier
- Les **filtres Twig autorisés** (voir section sandbox)
- Des exemples de conditions et boucles

### Réinitialisation

Le bouton "Réinitialiser au modèle par défaut" supprime la personnalisation en BDD — le package reprend la main.

---

## Priorité de résolution des templates

Lors d'un envoi avec un `$departement` fourni, la résolution suit cet ordre :

```
1. EmailTemplate en BDD pour ce département + cette clé
        ↓ (si absent)
2. EmailTemplate en BDD global (sans département) pour cette clé
        ↓ (si absent)
3. Fichier Twig du package (getHtmlTemplatePath())
```

Le rendu diffère selon la source :

| Source | Rendu |
|---|---|
| Fichier Twig | Rendu Twig standard (`twig->render()`) |
| Base de données | Rendu **sandboxé** (Twig avec restrictions de sécurité) |

---

## Sandbox Twig (sécurité)

Les templates stockés en base de données (personnalisations) sont rendus dans un **environnement Twig sandboxé** pour éviter que les utilisateurs n'exécutent du code arbitraire.

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
| [`back/src/Services/Email/AbstractEmailDefinition.php`](../back/src/Services/Email/AbstractEmailDefinition.php) | Classe abstraite de base |
| [`back/src/Services/Email/EmailService.php`](../back/src/Services/Email/EmailService.php) | Service d'envoi |
| [`back/src/Services/Email/EmailTemplateResolver.php`](../back/src/Services/Email/EmailTemplateResolver.php) | Logique de résolution BDD > fichier |
| [`back/src/Services/Email/EmailRegistry.php`](../back/src/Services/Email/EmailRegistry.php) | Registre global |
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
