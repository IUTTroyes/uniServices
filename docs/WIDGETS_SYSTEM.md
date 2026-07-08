# Système de widgets dashboard (front)

Ce document décrit l’architecture du rendu des widgets, le flux backend -> frontend, et la marche à suivre pour ajouter de nouveaux widgets dans un bundle existant ou dans un nouveau bundle.

## Vue d’ensemble

Le rendu d’un widget repose sur 3 éléments :

1. Le backend renvoie un catalogue de widgets avec un champ `component` (ex: `EmploiDuTempsWidget`).
2. Le frontend résout ce nom via un registre global (`widgetRegistry`).
3. `WidgetCard.vue` affiche dynamiquement le composant avec `<component :is="..." :data="..." :widget="..." />`.

Si aucun composant n’est enregistré pour un nom donné, le fallback `DefaultWidget` affiche les données brutes de façon lisible.

## Fichiers clés

### Infra partagée

- `shared/components/components/Dashboard/WidgetCard.vue`
  - Résout le composant via `resolveWidgetComponent(widget.component)`
  - Rend le composant avec les props `data` et `widget`
- `shared/components/components/Dashboard/widgets/widgetRegistry.js`
  - Contient le registre global en mémoire
  - Expose:
    - `registerWidgetComponent(name, component)`
    - `resolveWidgetComponent(name)`
  - Fallback: `DefaultWidget`

### Initialisation globale

- `packages/shell/assets/widgets/registerBundleWidgets.js`
  - Parcourt `bundles` et appelle `bundle.registerWidgets?.()`
  - Idempotent (exécuté une seule fois)
- `packages/shell/assets/main.js`
  - Appelle `registerAllBundleWidgets()` avant le montage de l’app

### Contrat par bundle

Chaque bundle peut exposer un hook `registerWidgets` dans son `manifest.ts`.

Exemple :

```ts
export default {
  name: 'questionnaire',
  primaryColor: 'blue',
  registerWidgets,
  routes: [...],
  menu: {...}
}
```

Le hook pointe vers un module qui enregistre les composants du bundle via `registerWidgetComponent(...)`.

## Bundles actuellement couverts

- `intranet`
  - `EmploiDuTempsWidget`
  - `ActionsUrgentesWidget`
  - `DocumentsRecentsWidget`
  - `NotesWidget`
- `questionnaire`
  - `QuestionnairePendingWidget`
  - `QuestionnaireStatsWidget`
  - `QuestionnaireLastAnswersWidget`
- `unifolio` (portfolio)
  - `PortfolioToCorrectWidget`
  - `PortfolioProgressWidget`
  - `PortfolioAlertsWidget`

## Ajouter un widget dans un bundle existant

### 1) Backend : déclarer le widget et son nom de composant

Dans le provider backend du bundle (`...WidgetProvider.php`), déclarer un `WidgetDefinition` avec un nom `component` unique.

Exemple :

```php
new WidgetDefinition(
    'questionnaire.pending',
    'questionnaire',
    'Questionnaires en attente',
    'pi pi-inbox',
    'QuestionnairePendingWidget',
    'medium',
    true
)
```

### 2) Backend : fournir les données

Dans le data provider (`...WidgetDataProvider.php`), retourner la structure consommée par le composant Vue.

### 3) Front bundle : créer le composant Vue

Créer le composant dans le bundle (ex: `assets/widgets/widgets/MonWidget.vue`) et définir la prop `data`.

### 4) Front bundle : enregistrer le composant

Dans le module du bundle `assets/widgets/registerWidgets.js` :

```js
import { registerWidgetComponent } from '@components';
import MonWidget from './widgets/MonWidget.vue';

export const registerWidgets = () => {
  registerWidgetComponent('MonWidget', MonWidget);
};
```

### 5) Front bundle : exposer le hook dans le manifest

Ajouter `registerWidgets` dans `assets/manifest.ts` :

```ts
import { registerWidgets } from './widgets/registerWidgets';

export default {
  ...,
  registerWidgets,
};
```

### 6) Vérification

- Vérifier que `widget.component` (backend) correspond exactement à la clé enregistrée côté front.
- Ouvrir la page dashboard et contrôler que le fallback JSON n’apparaît pas.

## Ajouter des widgets dans un nouveau bundle

Pour un nouveau bundle, appliquer la même convention :

1. Créer `assets/widgets/registerWidgets.js`
2. Créer les composants dans `assets/widgets/widgets/...`
3. Exposer `registerWidgets` dans `assets/manifest.ts`
4. Ajouter le manifest du bundle dans `packages/shell/assets/bundles-registry.js`

Une fois le bundle présent dans `bundles-registry`, l’initialisation globale appelle automatiquement `registerWidgets` au démarrage.

## Bonnes pratiques

- Laisser l’infra (`widgetRegistry`, `WidgetCard`, `DefaultWidget`) dans `shared`.
- Garder les composants métier dans leur bundle.
- Ne pas enregistrer les widgets dans des vues (`PortailView`, `Dashboard.vue`) : l’initialisation globale suffit.
- Favoriser des composants robustes sur les données (`items || []`, valeurs par défaut).

## Dépannage

### Symptôme: affichage JSON brut au lieu du composant

Vérifier dans cet ordre :

1. Le nom backend `component` correspond exactement au nom enregistré (`registerWidgetComponent`).
2. Le module `registerWidgets` du bundle est bien exporté dans le manifest (`registerWidgets`).
3. Le manifest du bundle est présent dans `packages/shell/assets/bundles-registry.js`.
4. Le composant Vue s’importe correctement (chemin valide).

### Symptôme: widget présent dans le catalogue mais vide

- Vérifier la structure renvoyée par le data provider backend.
- Vérifier les clés attendues dans le composant Vue.

## Résumé architecture

- **Backend**: définit `component` + payload data
- **Shell**: exécute `registerAllBundleWidgets()` au démarrage
- **Bundle**: enregistre ses composants via `registerWidgets`
- **Shared**: résout le composant et gère le fallback avec `DefaultWidget`
