# Tests Cypress pour UniServices

Ce dossier contient les tests end-to-end (E2E) pour l'application UniServices, utilisant le framework Cypress.

## Structure des tests

```
cypress/
├── e2e/                    # Tests E2E
│   ├── api/                # Tests API
│   │   └── api.cy.js       # Tests des endpoints API
│   └── intranet/           # Tests de l'interface intranet
│       ├── login.cy.js     # Tests d'authentification
│       └── previsionnel.cy.js # Tests du module Previsionnel
├── fixtures/               # Données de test
├── support/                # Fichiers de support
│   ├── commands.js         # Commandes personnalisées
│   ├── component.js        # Support pour les tests de composants
│   └── e2e.js              # Configuration pour les tests E2E
├── logs/                   # Logs des tests
├── reports/                # Rapports de test générés
└── run-tests.js            # Script pour exécuter les tests
```

## Commandes personnalisées

Des commandes personnalisées ont été créées pour faciliter l'écriture des tests :

- `cy.login()` - Se connecte à l'application
- `cy.navigateToIntranet()` - Navigue vers l'intranet
- `cy.navigateToPrevisionnelPersonnel()` - Navigue vers la vue Previsionnel Personnel
- `cy.navigateToPrevisionnelSemestre()` - Navigue vers la vue Previsionnel Semestre
- `cy.selectAnneeUniv()` - Sélectionne une année universitaire
- `cy.selectPersonnel()` - Sélectionne un personnel
- `cy.selectSemestre()` - Sélectionne un semestre
- `cy.waitForLoading()` - Attend que les indicateurs de chargement disparaissent
- `cy.searchInTable()` - Recherche dans un tableau

## Exécution des tests

Plusieurs scripts npm ont été configurés pour exécuter les tests :

```bash
# Ouvrir l'interface Cypress pour exécuter les tests interactivement
pnpm run cypress:open

# Exécuter tous les tests en mode headless
pnpm run cypress:run

# Exécuter uniquement les tests de login
pnpm run cypress:run:login

# Exécuter uniquement les tests du module Previsionnel
pnpm run cypress:run:previsionnel

# Exécuter uniquement les tests API
pnpm run cypress:run:api

# Exécuter tous les tests E2E et générer un rapport
pnpm run test:e2e

# Exécuter tous les tests (unitaires et E2E)
pnpm run test:all
```

## Rapports de test

Les rapports de test sont générés dans le dossier `cypress/reports/html` après l'exécution de la commande `pnpm run test:e2e`. Ces rapports HTML fournissent une vue détaillée des résultats des tests, y compris les captures d'écran et les vidéos des tests qui ont échoué.

## Bonnes pratiques

1. **Sélecteurs robustes** : Les tests utilisent des sélecteurs robustes avec des fallbacks pour s'adapter aux changements dans l'interface.

2. **Attente des requêtes API** : Les tests attendent que les requêtes API soient terminées avant de continuer, en utilisant `cy.intercept()` et `cy.wait()`.

3. **Isolation des tests** : Chaque test est isolé et ne dépend pas de l'état des autres tests.

4. **Tests de performance** : Des tests de performance sont inclus pour s'assurer que l'application répond rapidement.

5. **Tests API** : Les endpoints API sont testés directement pour s'assurer qu'ils fonctionnent correctement, indépendamment de l'interface utilisateur.

## Maintenance des tests

Pour maintenir les tests à jour :

1. Mettez à jour les sélecteurs si l'interface change
2. Ajoutez des tests pour les nouvelles fonctionnalités
3. Vérifiez régulièrement que tous les tests passent
4. Mettez à jour les commandes personnalisées si nécessaire

## Dépannage

Si les tests échouent :

1. Vérifiez que l'application est en cours d'exécution sur `http://localhost:3000`
2. Vérifiez que les identifiants de test sont corrects
3. Examinez les captures d'écran et les vidéos dans le dossier `cypress/screenshots` et `cypress/videos`
4. Consultez les logs dans le dossier `cypress/logs`
