# uniServices

## Auteurs

- [@cyndelherolt](https://www.github.com/cyndelherolt)
- [@dannebicque](https://www.github.com/dannebicque)

# Documentation Technique

## Aperçu du Projet

uniServices est un projet de gestion de services universitaires, conçu comme une refonte de l'intranet V3 pour le rendre plus modulaire et plus facile à maintenir. Le projet met l'accent sur une ergonomie et une expérience utilisateur (UX) repensées.

## Architecture

Le projet est structuré en deux parties principales :

### Backend (Symfony 7)

Le backend est développé avec Symfony 7. L'architecture a évolué vers une structure modulaire basée sur des **Symfony Bundles** indépendants situés dans le dossier `packages/`.

Le projet `back/` sert de noyau et utilise ces bundles. Les responsabilités sont réparties comme suit :

- **Bundles (`packages/`)** : Contiennent la logique métier spécifique à chaque application (Auth, Edt, Intranet), incluant leurs propres contrôleurs API et assets Vue.js.
- **Entities (`back/src/Entity`)** : Représentent les modèles de données partagés.
- **Repositories (`back/src/Repository`)** : Accès aux données.
- **ApiResource (`back/src/ApiResource`)** : Définitions API Platform.
- **State (`back/src/State`)** : Providers et Processors pour API Platform.

### Frontend (Vue.js 3 - Architecture Bundles)

Le frontend est intégré au sein des Symfony Bundles dans `packages/{app}-bundle/assets/`. Il utilise une architecture monorepo gérée par `pnpm` et `monorepo-builder`.

#### Bundles Applicatifs (`packages/`)
- **auth-bundle** : Gestion de l'authentification.
- **edt-bundle** : Gestion des emplois du temps.
- **intranet-bundle** : Application principale de l'intranet.

#### Ressources Partagées (`shared/`)
Les ressources communes sont centralisées dans le dossier `shared/` et importées par les bundles via des alias :
- **components** : Composants PrimeVue réutilisables.
- **stores** : Gestion d'état (Pinia).
- **requests** : Fonctions de requêtes API et services CRUD.
- **styles** : Styles SCSS et Tailwind partagés.
- **helpers** : Fonctions utilitaires transverses.
- **images** : Assets graphiques communs.
- **global-data** : Configuration et données statiques (ex: traductions).
- **types** : Définitions TypeScript.
- **utils** : Utilitaires techniques (directives Vue, etc.).

## Structure des Répertoires

```
uniServices/
├── packages/               # Bundles Symfony indépendants
│   ├── auth-bundle/        # Bundle d'authentification
│   ├── edt-bundle/         # Bundle emploi du temps
│   └── intranet-bundle/    # Bundle intranet principal
│       ├── assets/         # Code source Vue.js du bundle
│       ├── src/            # Code PHP (Bundle, DI, Controllers)
│       │   ├── Resources/
│       │   │   ├── config/ # Routes et configuration Symfony
│       │   │   └── public/ # Assets Vue compilés
│       │   └── Controller/ # Contrôleurs API du bundle
│       └── vite.config.js  # Configuration de build du bundle
├── shared/                 # Ressources frontend partagées
│   ├── components/         # Composants UI
│   ├── stores/             # Stores Pinia
│   ├── requests/           # Requêtes API
│   ├── styles/             # SCSS et Tailwind
│   └── ...
├── back/                   # Noyau Backend Symfony
│   ├── config/             # Configuration globale
│   ├── src/                # Entités, Repositories, API Resources
│   └── ...
├── monorepo-builder.php    # Gestion du monorepo PHP
├── package.json            # Configuration monorepo JS (pnpm workspaces)
├── docker-compose.yml      # Infrastructure Docker
└── Makefile                # Commandes de gestion centralisées
```

## Standards de Codage et Conventions

### Backend (Symfony 7)

- Suivre les standards PSR-1, PSR-4, PSR-12
- Utiliser les annotations/attributs pour la configuration
- Nommer les classes en PascalCase
- Nommer les méthodes et variables en camelCase
- Documenter les classes et méthodes avec PHPDoc

### Frontend (Vue.js 3)

- Structure des fichiers .vue : script, puis template, puis styles
- Nommer les composants en PascalCase
- Nommer les méthodes en camelCase
- Placer les requêtes API dans des fichiers séparés dans le dossier `common-requests/`
- Utiliser l'intercepteur axios pour chaque requête
- Utiliser les Skeleton de PrimeVue comme loaders
- Ajouter 'Service' à la fin du nom des méthodes qui font les requêtes API
- **Pour les requêtes HTTP simples (GET, POST, PATCH, DELETE) qui ne nécessitent pas de manipulation spécifique, utiliser apiCall.js et apiService.js**:
  - `apiService.js` fournit des méthodes génériques pour les opérations CRUD
  - `apiCall.js` est un wrapper qui gère les messages de succès/erreur et le traitement des réponses
- Récupérer l'année universitaire sélectionnée par l'utilisateur dans le localStorage via : `const selectedAnneeUniversitaire = JSON.parse(localStorage.getItem('selectedAnneeUniv'));`

  #### Gestion des notifications toast

  - Le paramètre `showToast` (par défaut à `true` pour les opérations de modification, `false` pour les récupérations de données) permet de contrôler l'affichage des notifications toast
  - Conventions d'affichage des toasts:
    - Pour les fonctions GET: `showToast = false` par défaut (pas de notification pour la simple récupération de données)
    - Pour les fonctions UPDATE, CREATE, DELETE: `showToast = true` par défaut (notification pour confirmer les modifications)
    - Les messages d'erreur dans les blocs try-catch affichent toujours des notifications

  - Exemple d'utilisation:
    ```javascript
    import createApiService from '@requests/apiService';
    import apiCall from '@helpers/apiCall';

    // Créer un service API pour une ressource spécifique
    const userService = createApiService('/api/users');

    // Récupération de données (sans toast par défaut)
    const getUsers = async (showToast = false) => {
      try {
        const response = await apiCall(
          userService.getAll, 
          [], 
          'Utilisateurs récupérés avec succès', 
          'Erreur lors de la récupération des utilisateurs',
          showToast
        );
        return response;
      } catch (error) {
        console.error('Erreur dans getUsers:', error);
        throw error;
      }
    };

    // Modification de données (avec toast par défaut)
    const createUser = async (userData, showToast = true) => {
      try {
        const response = await apiCall(
          userService.create, 
          [userData], 
          'Utilisateur créé avec succès', 
          'Erreur lors de la création de l\'utilisateur',
          showToast
        );
        return response;
      } catch (error) {
        console.error('Erreur dans createUser:', error);
        throw error;
      }
    };
    ```

### Alias Frontend

Les alias permettent d'accéder aux ressources partagées depuis n'importe quel bundle :

- **@** : Accès au dossier `assets` du bundle courant
- **@components** : Composants partagés (`shared/components`)
- **@styles** : Styles partagés (`shared/styles`)
- **@helpers** : Fonctions utilitaires partagées (`shared/helpers`)
- **@stores** : Gestion d'état partagée (`shared/stores`)
- **@requests** : Requêtes API partagées (`shared/requests`)
- **@images** : Images partagées (`shared/images`)
- **@config** : Données de configuration et traductions (`shared/global-data`)

## Flux de Développement

### Installation

1. Cloner le dépôt
2. Installer les dépendances backend : `cd back && composer install`
3. Installer les dépendances du monorepo (JS) : `pnpm install`
4. Fusionner les configurations Composer des bundles : `vendor/bin/monorepo-builder merge`

### Démarrage du Projet

Le `Makefile` à la racine permet de piloter l'ensemble de la structure :

- Démarrer le backend (Symfony) : `make start-back`
- Démarrer les serveurs de dev Vite (tous les bundles) : `make start-front`
- Démarrer tout le projet : `make start-all`

Pour lancer un bundle spécifique en développement :
- Auth : `npm run dev:auth`
- Edt : `npm run dev:edt`
- Intranet : `npm run dev:intranet`

### Build des Assets

Chaque bundle doit être compilé indépendamment pour que ses assets soient disponibles pour Symfony :

- Build complet : `npm run build:auth && npm run build:edt && npm run build:intranet`
- Les fichiers compilés sont générés dans `packages/{bundle}/src/Resources/public/`

### Développement

1. Créer une branche pour chaque fonctionnalité ou correction
2. Suivre les standards de codage définis
3. Écrire des tests pour les nouvelles fonctionnalités
4. Soumettre une pull request pour révision

## Création d'un Nouveau Bundle

Pour ajouter un nouveau bundle au projet (ex: `nom-bundle`), suivez ces étapes :

### 1. Création de la structure du dossier
Créez un nouveau dossier dans `packages/nom-bundle` avec la structure suivante :
```bash
packages/nom-bundle/
├── assets/             # Code source Vue.js
├── src/                # Code source PHP
│   └── NomBundle.php   # Classe du Bundle
├── composer.json
├── package.json
└── vite.config.js
```

### 2. Configuration PHP (Composer)
Dans `packages/nom-bundle/composer.json` :
```json
{
  "name": "iuttroyes/nom-bundle",
  "type": "symfony-bundle",
  "autoload": {
    "psr-4": { "NomBundle\\": "src/" }
  }
}
```

Dans le `composer.json` à la **racine du projet** :
- Ajoutez le namespace dans `autoload` -> `psr-4` : `"NomBundle\\": "packages/nom-bundle/src/"`
- Ajoutez le bundle dans `replace` : `"iuttroyes/nom-bundle": "self.version"`

Créez la classe `packages/nom-bundle/src/NomBundle.php` :
```php
<?php
namespace NomBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NomBundle extends Bundle {
    public function getPath(): string {
        return \dirname(__DIR__);
    }
}
```

Activez le bundle dans `back/config/bundles.php` :
```php
NomBundle\NomBundle::class => ['all' => true],
```

### 3. Configuration Frontend (Vue.js)
Dans `packages/nom-bundle/package.json` :
```json
{
  "name": "@uni/nom-bundle",
  "private": true,
  "scripts": {
    "dev": "vite",
    "build": "vite build"
  }
}
```

Copiez et adaptez un fichier `vite.config.js` d'un autre bundle pour configurer le build vers `src/Resources/public`.

### 4. Finalisation
1. Installez les dépendances : `pnpm install`
2. Mettez à jour autoload : `composer dump-autoload` (ou `composer install`)

## Désinstallation d'un Bundle

Si vous souhaitez retirer un bundle du projet (par exemple `edt-bundle`) :

1. **Supprimer le répertoire** : `rm -rf packages/edt-bundle`
2. **Nettoyer le cache pnpm** : `pnpm install` (mettra à jour les workspaces)
3. **Mettre à jour Composer** :
    - Supprimer manuellement les références au bundle dans le `composer.json` racine (sections `autoload`, `autoload-dev` et `replace`).
    - Exécuter `composer update` pour nettoyer les dépendances.
4. **Nettoyer Symfony** :
    - Vérifier si le bundle était enregistré dans `back/config/bundles.php` et le retirer si nécessaire.
    - Supprimer les éventuelles configurations spécifiques dans `back/config/packages/`.

## Technologies Utilisées

### Backend
- PHP 8.2+
- Symfony 7
- API Platform 4
- Doctrine ORM
- Monorepo Builder (Symplify)

### Frontend
- Vue.js 3
- Vite 6
- Tailwind CSS v4
- Pinia (Gestion d'état)
- PrimeVue v4 (Composants UI)
- pnpm (Gestion des packages)
- Vitest / Cypress (Tests)

## Tests

### Backend
- PHPUnit pour les tests unitaires et fonctionnels
- Exécuter les tests : `cd back && php bin/phpunit`

### Frontend (Bundles)
- Exécuter les tests unitaires (depuis la racine) : `pnpm test`
- Exécuter les tests E2E : `pnpm cypress:run`

## Déploiement

Le déploiement est géré via CI/CD (à compléter avec les détails spécifiques au projet).

## Contribution

1. Suivre les standards de codage définis dans `ruleset.md`
2. Documenter les nouvelles fonctionnalités
3. Écrire des tests pour les nouvelles fonctionnalités
4. Mettre à jour la documentation si nécessaire
