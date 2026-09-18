# Système de widgets dashboard (front + backend)

Ce document décrit l’architecture du rendu des widgets, le flux backend -> frontend, la gestion des droits par profil utilisateur (`Personnel` / `Etudiant`), et la marche à suivre pour ajouter de nouveaux widgets dans un bundle existant ou dans un nouveau bundle.

## Vue d’ensemble

Le rendu d’un widget repose sur 4 éléments :

1. Le backend décrit les widgets (`WidgetDefinition`) et leurs profils autorisés.
2. Le backend renvoie un catalogue filtré selon l’utilisateur connecté.
3. Le frontend résout `component` via un registre global (`widgetRegistry`).
4. `WidgetCard.vue` affiche dynamiquement le composant avec `<component :is="..." :data="..." :widget="..." />`.

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

### Contrat backend (droits d’accès)

- `back/src/Domain/Dashboard/WidgetDefinition.php`
  - Définit les constantes de profils : `PROFILE_PERSONNEL`, `PROFILE_ETUDIANT`
  - Ajoute `allowedProfiles` (par défaut : les deux profils)
  - Méthode `isAllowedForUser(Personnel|Etudiant $user)` pour centraliser le contrôle d’accès
- `back/src/Controller/DashboardController.php`
  - Filtre les widgets du catalogue et des widgets disponibles selon `isAllowedForUser(...)`
  - Bloque l’accès aux données d’un widget non autorisé (`403`)
- `back/src/Domain/Dashboard/WidgetDataProviderInterface.php`
  - Signature `getData(string $code, Personnel|Etudiant $user): array`

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

### Exemple de droits par profil

- **Personnel uniquement**
  - `auth.actus_int`
  - `intranet.actions_urgentes`
- **Etudiant uniquement**
  - `intranet.notes`
- **Personnel + Etudiant**
  - `intranet.emploi_du_temps`
  - (et tout widget qui ne précise pas `allowedProfiles`, car le défaut autorise les deux)

## Ajouter un widget dans un bundle existant

### 1) Backend : déclarer le widget et son nom de composant

Dans le provider backend du bundle (`...WidgetProvider.php`), déclarer un `WidgetDefinition` avec un nom `component` unique, et préciser `allowedProfiles` si le widget n’est pas accessible aux deux profils.

Exemple :

```php
use App\Domain\Dashboard\WidgetDefinition;

new WidgetDefinition(
    'questionnaire.pending',
    'questionnaire',
    'Questionnaires en attente',
    'pi pi-inbox',
    'QuestionnairePendingWidget',
    'medium',
    true,
    allowedProfiles: [WidgetDefinition::PROFILE_PERSONNEL]
)
```

Si `allowedProfiles` est omis, le widget est visible par `Personnel` et `Etudiant`.

### 2) Backend : fournir les données

Dans le data provider (`...WidgetDataProvider.php`), retourner la structure consommée par le composant Vue avec la signature suivante :

```php
public function getData(string $code, Personnel|Etudiant $user): array
```

Le contrôleur bloque déjà les widgets non autorisés. Le provider doit cependant rester compatible avec les deux types d’utilisateur.

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

### Symptôme: widget absent pour un profil donné

Vérifier dans cet ordre :

1. `allowedProfiles` dans la déclaration du widget (`WidgetDefinition`).
2. Que le profil de l’utilisateur connecté est bien `Personnel` ou `Etudiant` attendu.
3. Que l’endpoint appelé est celui du dashboard (`/api/widgets/catalog` ou `/api/widgets/available/{dashboardCode}`) et non une réponse cache obsolète.
4. En accès data direct (`/api/widgets/data/{code}`), qu’un `403` est renvoyé si le widget est non autorisé (comportement attendu).

## Résumé architecture

- **Backend**: définit `component` + `allowedProfiles` + payload data
- **Shell**: exécute `registerAllBundleWidgets()` au démarrage
- **Bundle**: enregistre ses composants via `registerWidgets`
- **Shared**: résout le composant et gère le fallback avec `DefaultWidget`

### Charger les services du bundle (important)

Après avoir ajouté les fichiers frontend (`assets/manifest.ts`, `assets/widgets/registerWidgets.js`) et les providers backend, s'assurer que le bundle charge son `services.yaml`. Si l'extension du bundle ne charge pas `services.yaml`, les providers backend (par ex. WidgetProvider) ne seront pas taggés et leurs widgets n'apparaîtront pas dans le `WidgetRegistry`.

Exemple d'Extension (packages/<votre-bundle>/src/DependencyInjection/YourBundleExtension.php) :

```php
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
$loader->load('routes.yaml');
$loader->load('services.yaml');
```

Vérifier également que `services.yaml` tagge correctement les providers, p.ex. :

```yaml
_instanceof:
  App\Domain\Dashboard\WidgetProviderInterface:
    tags: ['app.dashboard.widget_provider']
```

Après modification : vider le cache et redémarrer le serveur :

```
php bin/console cache:clear && symfony server:restart
```

Si le widget reste absent, vérifier l'API `/api/widgets/available/<dashboardCode>` et lister les services taggés :

```
php bin/console debug:container --tag=app.dashboard.widget_provider
```

