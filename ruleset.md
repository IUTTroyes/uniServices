# Guide suprême de l'Itranet V4

## Backend - SF7

- 
- 

## Frontend - VueJs3

- L'ordre doit être script, puis template et styles dans un fichier .vue
- Les composants doivent être en PascalCase
- Les méthodes doivent être en camelCase
- Les requêtes API doivent être dans un fichier js à part dans le dossier ``common-requests/`` et sont ensuite exploiter dans les stores si nécessaire ``common-stores/``
- Utiliser l'intercepteur axios pour chaque requête pour tester le token et exploiter la variable d'environnement qui contient l'URL du backoffice
	```javascript
	import api from '@helpers/axios';
	api.get(`/api/hello`)
	```
- Utiliser les *Skeleton* de primevue comme loader -> en faire des composants si nécessaire
- Inclure 'Service' à la fin du nom des méthodes qui font les requêtes API (dans le dossier ``common-requests/``) exemple : `getUserService`

### Alias

#### Forms

- Gestion des validations et des messages d'erreurs : [voir la documentation](../../packages/common-components/components/Forms/README.md)

#### src

Dans chaque projet front, l'alias `@` est utilisé pour accéder au dossier `src` du projet.

#### Packages

Pour accéder aux packages communs utiliser les alias suivants :

- **@components** : pour les composants communs (ex: bouton, input, etc) dans le dossier ``common-components/``
- **@styles** : pour les styles communs (ex: variables, mixins, etc) dans le dossier ``common-styles/``
- **@helpers** : pour les fonctions communes (ex: formatage de date, etc) dans le dossier ``common-helpers/``
- **@stores** : pour les stores communs (ex: auth, etc) dans le dossier ``common-stores/``
- **@requests** : pour les requêtes API communes (ex: matières, diplômes, etc) dans le dossier ``common-requests/``


### Composables de filtres (PrimeVue) — conventions d'usage

Cette section formalise l'usage des filtres côté Front pour les listes/exports et l'alignement avec le Back. Elle s'applique notamment aux DataTable PrimeVue et aux appels d'API paginés.

- Emplacement des composables: `front/apps/intranet/src/composables/filters/`
  - Factory générique: `createFilters.ts`
  - Composables métier: `usersFilters/useEtudiantFilters.ts`, etc.
- Nommage: `useXxxFilters` pour les composables domaine (ex: `useEtudiantFilters`).
- Structure d'un filtre: chaque clé est un objet `{ value, matchMode }` (aligné sur PrimeVue `FilterMatchMode`).

#### API générique: `createFilters`

```ts
// front/apps/intranet/src/composables/filters/createFilters.ts
import { ref, watch, type Ref, type WatchOptions } from 'vue';

export function createFilters<F extends Record<string, any>>(defaultState: F) {
  const filters = ref({ ...defaultState }) as Ref<F>;

  const updateFilters = (patch: Partial<F>) => {
    filters.value = { ...filters.value, ...patch } as F;
  };

  const resetFilters = () => {
    filters.value = { ...defaultState } as F;
  };

  const watchChanges = (cb: (newF: F, oldF: F) => any, options?: WatchOptions) => {
    return watch(filters, cb, { deep: true, ...options });
  };

  return { filters, updateFilters, resetFilters, watchChanges };
}
```

Exposé par tous les composables métier:
- `filters`: Ref de l'état courant (profond) des filtres
- `updateFilters(patch)`: mise à jour partielle, immuable
- `resetFilters()`: remet l'état par défaut
- `watchChanges(cb, options)`: observe les changements profonds pour relancer une recherche serveur, etc.

#### Exemple de composable métier

```ts
// front/apps/intranet/src/composables/filters/usersFilters/useEtudiantFilters.ts
import { createFilters } from '../createFilters';
import { FilterMatchMode } from '@primevue/core/api';

const defaultEtudiantFilters = {
  numEtudiant: { value: null, matchMode: FilterMatchMode.STARTS_WITH },
  nom:         { value: null, matchMode: FilterMatchMode.STARTS_WITH },
  prenom:      { value: null, matchMode: FilterMatchMode.STARTS_WITH },
};

export function useEtudiantFilters() {
  return createFilters(defaultEtudiantFilters);
}
```

Règles:
- Déclarer tout le schéma par défaut dans le composable (pas d'ajout dynamique de clés à chaud).
- Préférer `FilterMatchMode.*` de PrimeVue pour rester homogène avec la DataTable et l'API.

#### Utilisation dans un composant Vue (schéma recommandé)

- Importer le composable métier et l'initialiser dans `<script setup>`.
- Passer `filters.value` tel quel au service/API.
- Observer les changements via `watchChanges` pour relancer le chargement.
- Lier les champs d'entrée à `filters.<clé>.value` et prévoir un bouton de réinitialisation.

Exemple simplifié (pseudocode):
```
const { filters, resetFilters, watchChanges } = useEtudiantFilters();

async function load() {
  await getEtudiantScolariteSemestresService({
    /* autres params */
    filters: filters.value,
  });
}

watchChanges(load);
```

Notes:
- Lier les champs d'entrée directement à `filters.<clé>.value` pour conserver la structure attendue par les DataTable et l'API.
- `watchChanges` est l'endroit recommandé pour déclencher les rechargements serveur, exports, recalculs, etc.

#### Alignement Backend

- Le back attend un objet `filters` sérialisable reprenant la même structure `{ champ: { value, matchMode } }`.
- Les services existants (ex: `getEtudiantScolariteSemestresService`) acceptent un paramètre `filters` déjà conforme.
- Pour le comptage fiable vs pagination, voir l'exemple dans `AffectationGroupeView.vue` qui envoie `filters` aux deux endpoints.

#### Bonnes pratiques

- Toujours initialiser toutes les clés nécessaires dans l'état par défaut du composable.
- Ne pas muter profondément des sous-objets de façon non contrôlée; utiliser `updateFilters` pour patcher.
- Utiliser `resetFilters()` pour revenir à l'état initial (utile lors d'un changement de contexte: semestre, année, route, etc.).
- Ajouter un debounce/throttle côté appelant si les frappes clavier doivent éviter des requêtes trop fréquentes.
- Conserver la compatibilité TypeScript en typant le schéma par défaut et en laissant `createFilters` inférer `F`.
- Centraliser les composables par domaine (ex: `usersFilters/`, `coursesFilters/`) pour éviter la duplication.
