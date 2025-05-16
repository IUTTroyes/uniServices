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

Le backend est développé avec Symfony 7, un framework PHP moderne. Il suit une architecture en couches avec une séparation claire des responsabilités :

- **Controllers** : Gèrent les requêtes HTTP et retournent les réponses
- **Entities** : Représentent les modèles de données et leurs relations
- **Repositories** : Fournissent des méthodes pour accéder aux données
- **Services** : Contiennent la logique métier
- **ApiDto** : Objets de transfert de données pour les réponses API
- **ApiResource** : Définitions des ressources API (API Platform)
- **State** : Fournisseurs d'état pour API Platform
- **DataFixtures** : Données de test
- **Command** : Commandes console
- **EventListener** : Écouteurs d'événements
- **Filter** : Filtres de requête
- **Security** : Code lié à la sécurité
- **Utils** : Fonctions utilitaires
- **ValueObject** : Objets valeur

### Frontend (Vue.js 3)

Le frontend est développé avec Vue.js 3 et organisé comme un monorepo avec plusieurs applications partageant des packages communs :

#### Applications
- **auth** : Gestion de l'authentification
- **edt** : Gestion des emplois du temps
- **intranet** : Application principale de l'intranet
- **tests** : Tests automatisés

#### Packages Partagés
- **common-components** : Composants UI réutilisables
- **common-global-data** : Données globales partagées
- **common-helpers** : Fonctions utilitaires
- **common-images** : Images partagées
- **common-requests** : Fonctions de requêtes API
- **common-stores** : Gestion d'état partagée (Pinia)
- **common-styles** : Styles CSS partagés

## Structure des Répertoires

```
uniServices/
├── back/                  # Backend Symfony
│   ├── bin/               # Exécutables
│   ├── config/            # Configuration
│   ├── migrations/        # Migrations de base de données
│   ├── public/            # Fichiers publics
│   ├── src/               # Code source
│   │   ├── ApiDto/        # Objets de transfert de données
│   │   ├── ApiResource/   # Ressources API
│   │   ├── Command/       # Commandes console
│   │   ├── Controller/    # Contrôleurs HTTP
│   │   ├── DataFixtures/  # Données de test
│   │   ├── DataProvider/  # Fournisseurs de données
│   │   ├── Entity/        # Entités de base de données
│   │   ├── Enum/          # Types énumération
│   │   ├── EventListener/ # Écouteurs d'événements
│   │   ├── Filter/        # Filtres de requête
│   │   ├── Interfaces/    # Définitions d'interfaces
│   │   ├── Repository/    # Repositories de données
│   │   ├── Security/      # Code lié à la sécurité
│   │   ├── Services/      # Services métier
│   │   ├── State/         # Fournisseurs d'état
│   │   ├── Utils/         # Fonctions utilitaires
│   │   └── ValueObject/   # Objets valeur
│   ├── templates/         # Templates Twig
│   ├── translations/      # Fichiers de traduction
│   ├── var/               # Fichiers variables (cache, logs)
│   └── vendor/            # Dépendances
├── front/                 # Frontend Vue.js
│   ├── apps/              # Applications
│   │   ├── auth/          # App d'authentification
│   │   ├── edt/           # App d'emploi du temps
│   │   ├── intranet/      # App principale
│   │   └── tests/         # Tests
│   ├── packages/          # Packages partagés
│   │   ├── common-components/ # Composants UI
│   │   ├── common-global-data/ # Données globales
│   │   ├── common-helpers/ # Fonctions utilitaires
│   │   ├── common-images/ # Images partagées
│   │   ├── common-requests/ # Requêtes API
│   │   ├── common-stores/ # Stores Pinia
│   │   └── common-styles/ # Styles CSS
│   ├── stories/           # Stories Storybook
│   └── .storybook/        # Configuration Storybook
├── cypress/               # Tests E2E Cypress
└── Makefile               # Commandes Make
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

- **@** : Accès au dossier `src` du projet
- **@components** : Composants communs dans `common-components/`
- **@styles** : Styles communs dans `common-styles/`
- **@helpers** : Fonctions communes dans `common-helpers/`
- **@stores** : Stores communs dans `common-stores/`
- **@requests** : Requêtes API communes dans `common-requests/`

## Flux de Développement

### Installation

1. Cloner le dépôt
2. Installer les dépendances backend : `cd back && composer install`
3. Installer les dépendances frontend : `cd front && pnpm install`
4. Créer les liens symboliques : `make create-symlinks`

### Démarrage du Projet

- Démarrer le backend : `make start-back`
- Démarrer le frontend : `make start-front`
- Démarrer les deux : `make start-all`
- Démarrer Storybook : `make start-storybook`
- Arrêter le projet : `make stop-all`

### Développement

1. Créer une branche pour chaque fonctionnalité ou correction
2. Suivre les standards de codage définis
3. Écrire des tests pour les nouvelles fonctionnalités
4. Soumettre une pull request pour révision

## Technologies Utilisées

### Backend
- PHP 8.2+
- Symfony 7
- API Platform
- Doctrine ORM
- PHPStan pour l'analyse statique

### Frontend
- Vue.js 3
- Vite
- Pinia pour la gestion d'état
- PrimeVue pour les composants UI
- Tailwind CSS pour le styling
- Storybook pour la documentation des composants
- Cypress pour les tests E2E
- ESLint et Prettier pour le formatage du code
- pnpm pour la gestion des packages

## Tests

### Backend
- PHPUnit pour les tests unitaires et fonctionnels
- Exécuter les tests : `cd back && php bin/phpunit`

### Frontend
- Vitest pour les tests unitaires
- Cypress pour les tests E2E
- Exécuter les tests unitaires : `cd front && pnpm test`
- Exécuter les tests E2E : `cd front && pnpm cypress:run`

## Déploiement

Le déploiement est géré via CI/CD (à compléter avec les détails spécifiques au projet).

## Contribution

1. Suivre les standards de codage définis
2. Documenter les nouvelles fonctionnalités
3. Écrire des tests pour les nouvelles fonctionnalités
4. Mettre à jour la documentation si nécessaire
