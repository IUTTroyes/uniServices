# Conventions de développement UniServices

## Architecture du dépôt

| Emplacement | Responsabilité |
| --- | --- |
| `back/` | Noyau Symfony 8.1, API Platform 4 et Doctrine ORM. Les entités et services transverses sont sous `back/src/`, le paramétrage sous `back/config/`. |
| `packages/*-bundle/` | Modules applicatifs. Le PHP propre au module est sous `src/`, le code Vue sous `assets/`. Les bundles Symfony actifs sont déclarés dans [bundles.php](back/config/bundles.php). |
| `packages/shell/` | Point d’entrée Vue 3. Le [registre des bundles](packages/shell/assets/bundles-registry.js) raccorde leurs interfaces au shell. Vite génère l’application dans `back/public/app/`. |
| `shared/` | Composants, styles, composables, stores Pinia et clients API partagés. |
| `docs/` | Documentation métier et technique à maintenir avec les changements de comportement. |

Les versions de référence sont déclarées dans [les dépendances backend](back/composer.json) et [le workspace frontend](package.json). La version de Symfony verrouillée dans [composer.lock](back/composer.lock) requiert PHP 8.4.1 ou une version compatible plus récente. Le frontend utilise Vue 3, PrimeVue 4, Pinia et Vite, avec pnpm pour les workspaces.

## Backend

- Suivre PSR-12 et les espaces de noms PSR-4 déclarés par Composer. Utiliser PascalCase pour les classes et camelCase pour les méthodes et variables. Typer les entrées, retours et propriétés, et documenter en PHPDoc les formes de données que PHP ne permet pas d’exprimer directement.
- Déclarer les opérations API Platform, leurs groupes de sérialisation et leurs paramètres selon le modèle de la ressource existante. Limiter les groupes d’écriture aux champs modifiables. Utiliser un provider pour une lecture spécifique, un processor pour une opération d’écriture et un repository pour les requêtes Doctrine.
- Appliquer les autorisations côté serveur. Passer au voter l’objet du périmètre concerné. Si une écriture peut changer son rattachement, contrôler également l’objet dénormalisé. Filtrer les collections en requête avant pagination et appliquer les mêmes restrictions aux comptages et exports. [Autorisation côté serveur](docs/VOTERS.md)
- Pour un changement de mapping Doctrine, ajouter la migration correspondante sous `back/migrations/` et vérifier qu’elle peut être versionnée. Le fichier [back/.gitignore](back/.gitignore) prévoit des exceptions pour les migrations suivies. La reprise Intranet V3 utilise ses propres migrateurs sous [back/src/Migration/IntranetV3](back/src/Migration/IntranetV3).

### Contexte annuel

- Utiliser l’année universitaire sélectionnée par défaut. Le client Axios partagé transmet le contexte. Pour une autre année ou une exception, passer explicitement l’identifiant cible ou le mode `toutes`, `aucun` ou `ressource` selon la politique de l’opération. Changer la sélection via `useAnneeUnivStore.setSelectedAnneeUniv()`.
- Déclarer le chemin vers l’année des ressources ORM dans `PATHS` du [registre annuel](back/src/AnneeUniversitaire/PerimetreAnnuelRegistry.php). Les lectures transversales autorisées relèvent de `autoriseToutes()`. Les ressources indépendantes et les DTO ont leurs propres entrées. Les contrôleurs métier déclarent leur politique avec l’attribut `ContexteAnnuel`.
- Appliquer le périmètre dans les requêtes métier personnalisées utilisées hors de l’extension ORM d’API Platform. Le contexte annuel s’ajoute aux droits d’accès. Distinguer année sélectionnée, année active et année portée par une ressource identifiée. [Gestion des années universitaires et exemples complets](docs/ANNEES_UNIVERSITAIRES.md)

### Formation et scolarité

- Le PN rattache un diplôme à une année universitaire. L’année de formation contient les semestres. Un dossier annuel d’étudiant regroupe les inscriptions semestrielles. Utiliser l’inscription concernée pour les notes, groupes et résultats du semestre. [Structure des formations](docs/STRUCTURE_FORMATION.md)
- Calculer les nombres de groupes CM, TD et TP depuis les `StructureGroupe` rattachés au semestre. Les champs lus par le prévisionnel (`nbGroupesCm`, `nbGroupesTd`, `nbGroupesTp`) sont exposés par des getters calculés.
- La sous-commission ouverte calcule les résultats à partir des notes. La validation fige les résultats dans une révision et renseigne les résultats courants de l’inscription. Utiliser les opérations dédiées de réouverture ou de restauration pour leur cycle de vie. Les résultats V3 importés restent dans le contexte historique de l’inscription. La publication suit les indicateurs du dossier et des notes. [Scolarité et sous-commissions](docs/STRUCTURE_SCOLARITE.md)

## Frontend

- Ordonner les fichiers Vue avec `<script setup>`, puis `<template>`, puis `<style>` si nécessaire. Nommer les composants en PascalCase, les fonctions en camelCase et les composables `useXxx`.
- Définir les appels HTTP partagés dans `shared/requests/`. Placer les appels propres à un module dans ses services, par exemple [commissionService.ts](packages/intranet-bundle/assets/service/commissionService.ts). Conserver le suffixe `Service` des fonctions de requête et utiliser Pinia pour l’état partagé entre écrans.
- Utiliser [le client Axios partagé](shared/helpers/axios.js) via `@helpers/axios`. Il résout l’URL du backend avec `VITE_BASE_URL`, envoie les cookies et gère le rafraîchissement de session et le contexte annuel. Réutiliser `apiCall` selon les conventions du service pour les erreurs et notifications.
- Utiliser les composants et les styles partagés pour les états de chargement, de liste vide et d’erreur. Le [guide du design system](docs/STYLEGUIDE_AI_AGENTS.md) couvre notamment `ListSkeleton`, `SimpleSkeleton`, `EmptyState` et les tableaux. La démonstration est disponible sur `/auth/configuration/styleguide`. Pour les formulaires, suivre [les conventions de validation et de messages](shared/components/components/Forms/README.md).

### Alias Vite

`@/` pointe vers le dossier `assets/` du bundle qui effectue l’import, ou vers celui du shell pour son propre code. La [configuration Vite du shell](packages/shell/vite.config.js) déclare les alias partagés.

| Alias | Répertoire |
| --- | --- |
| `@components` | `shared/components/` |
| `@composables` | `shared/composables/` |
| `@config` | `shared/global-data/` |
| `@dialogs` | `shared/dialogs/` |
| `@helpers` | `shared/helpers/` |
| `@images`, `@common-images` | `shared/images/` |
| `@requests` | `shared/requests/` |
| `@stores` | `shared/stores/` |
| `@styles` | `shared/styles/` |
| `@types` | `shared/types/` |
| `@utils` | `shared/utils/` |

### Filtres PrimeVue et pagination

- Réutiliser [createFilters](shared/composables/filters/createFilters.ts) et les composables métier dans `shared/composables/filters/`, par exemple [useEtudiantFilters](shared/composables/filters/usersFilters/useEtudiantFilters.ts). Déclarer les clés et modes de recherche dans les valeurs initiales. Lier les champs à l’état des filtres et utiliser `watchChanges` pour relancer la recherche serveur, avec un debounce pour la saisie libre si nécessaire.
- Garder la structure PrimeVue `{ value, matchMode }` dans l’interface. Adapter les paramètres au contrat de l’opération dans le service. Le [service des inscriptions semestrielles](shared/requests/etudiant_services/etudiantScolariteSemestreService.js) traduit notamment `nom`, `prenom` et `numEtudiant` en paramètres lus par [le filtre backend](back/src/Filter/EtudiantScolariteSemestreFilter.php).
- Pour un tableau paginé côté serveur, remettre la page à la première quand les critères changent. Utiliser `totalItems` lorsque le service expose la réponse paginée complète. Si le service renvoie seulement `member`, récupérer le total via un contrat qui le fournit et transmettre les mêmes filtres et périmètres à la liste, au comptage et à l’export.

## Vérifications locales

Les commandes de référence sont déclarées dans le [Makefile](Makefile) et le [Makefile backend](back/Makefile).

| Objectif | Commande depuis la racine |
| --- | --- |
| Démarrer Symfony et le shell en développement | `make start-back` et `make start-front` |
| Vérifier le conteneur Symfony et le mapping Doctrine | `make lint-container` et `make doctrine-validate` |
| Lancer l’analyse statique PHP | `make phpstan` |
| Exécuter une suite PHP ciblée, selon la configuration du dépôt | `vendor/bin/phpunit <chemin>` |
| Exécuter les tests frontend ciblés | `pnpm exec vitest run <chemin>` |
| Compiler le shell pour vérifier l’intégration des modules | `make build-front` |
