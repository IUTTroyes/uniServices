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

Pour ajouter un nouveau bundle au projet (ex: `nom-bundle`), utilisez le script d'automatisation :

```bash
php scripts/create-bundle.php nom-bundle
```

Le script vous demandera interactivement :
- **Nom de l'outil** (ex: `Mon Appli`)
- **Description** (ex: `Gestion de mes données`)
- **URL Slug** (ex: `mon-appli` pour la structure)
- **URL complète** (ex: `http://localhost:3000/mon-appli/` pour le lien dans la navigation)

Vous pouvez également passer ces informations via des arguments pour éviter les questions :

```bash
php scripts/create-bundle.php nom-bundle \
  --display-name="Nom lisible" \
  --description="Description de l'outil" \
  --url-slug="slug" \
  --url="http://localhost:3000/slug/"
```

Le logo est par défaut `LogoIut`.

### Ce que fait le script :
1. Crée l'arborescence du bundle dans `packages/nom-bundle/`.
2. Génère la classe PHP du Bundle (`NomBundle.php`).
3. Génère les fichiers `composer.json`, `package.json` et `vite.config.js` pré-configurés.
4. Crée un fichier `packages/nom-bundle/bundle.meta.json` (nom, description, urlSlug, url) utilisé pour le registre des outils.
5. Met à jour le `composer.json` à la racine et dans `back/` pour l'autoloading.
6. Enregistre le bundle dans `back/config/bundles.php`.
7. Reconstruit automatiquement `shared/global-data/tools.generated.json` en scannant tous les bundles locaux et les outils externes (dans `shared/global-data/external-tools/`).

### Après l'exécution :
1. Installez les nouvelles dépendances et liez le workspace : `pnpm install`
2. Mettez à jour l'autoload PHP : `composer dump-autoload`
3. (Optionnel) Ajoutez des commandes `dev` et `build` dans le `Makefile` racine pour faciliter la gestion de ce bundle.

## Activation et Désactivation d'un Bundle

Si vous souhaitez désactiver temporairement un bundle sans supprimer ses fichiers (pour qu'il ne soit plus chargé par Symfony et n'apparaisse plus dans le registre des outils), utilisez :

```bash
php scripts/deactivate-bundle.php nom-bundle
```

Pour réactiver un bundle précédemment désactivé :

```bash
php scripts/activate-bundle.php nom-bundle
```

### Ce que font ces scripts :
1. **Désactivation** :
    - Retire le bundle de `back/config/bundles.php`.
    - Retire l'autoloading du `composer.json` racine et de `back/composer.json`.
    - Renomme `bundle.meta.json` en `.disabled` pour le masquer du registre.
    - Met à jour automatiquement l'autoloading PHP et vide le cache Symfony.
2. **Activation** :
    - Restaure le bundle dans `back/config/bundles.php`.
    - Restaure l'autoloading dans les fichiers `composer.json`.
    - Réactive le fichier de méta-données.
    - Met à jour l'autoloading PHP et vide le cache Symfony.

## Désinstallation d'un Bundle

Si vous souhaitez retirer un bundle du projet (par exemple `nom-bundle`), utilisez le script d'automatisation :

```bash
php scripts/remove-bundle.php nom-bundle
```

### Ce que fait le script :
1. Supprime le répertoire du bundle dans `packages/`.
2. Retire les configurations d'autoloading dans le `composer.json` racine et dans `back/`.
3. Retire le bundle de `back/config/bundles.php`.
4. Reconstruit automatiquement `shared/global-data/tools.generated.json` (mise à jour de la liste des outils côté front).

### Après l'exécution :
1. Mettez à jour les workspaces : `pnpm install`
2. Mettez à jour l'autoload PHP : `composer dump-autoload` (à la racine et dans `back/`)
3. Videz le cache Symfony : `cd back && bin/console cache:clear`

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
test