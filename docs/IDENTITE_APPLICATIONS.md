# Identité des applications

Chaque application d’UniService est décrite **une seule fois**, dans le `bundle.meta.json` de son package
(`shared/global-data/external-tools/*.meta.json` pour les outils externes). Le front et le serveur lisent cette
description : on ne recopie ni un nom, ni une couleur, ni une icône ailleurs.

## Modifier une application

1. Modifier son `bundle.meta.json`.
2. Lancer `pnpm registry` (PHP est nécessaire).
3. Lancer `pnpm test`, puis reconstruire le front. Vider le cache Symfony en production.
4. Versionner les fichiers générés avec la modification.

## Champs

| Champ | Rôle |
| --- | --- |
| `name` | Nom affiché : menu, en-tête, badge, titre d’onglet, logo, rôles, objets d’e-mails |
| `description` | Phrase courte décrivant l’outil |
| `urlSlug`, `url` | Identifiant technique et lien du portail. **Ne pas changer pour un simple renommage** (droits, routes) |
| `shortLabel` | Facultatif, égal à `name` par défaut |
| `icon` | Icône PrimeIcons des menus, en-têtes et badges ; la choisir proche du pictogramme |
| `aliases` | Autres codes désignant la même application (`portfolio` pour `unifolio`) |
| `brand.key` | Clé unique de la palette (`uni-<key>` dans Tailwind) |
| `brand.tagline` | Signature affichée sous le logo |
| `brand.color` | Couleur dominante `#RRGGBB`, lisible avec du texte blanc (contraste 4,5:1 au minimum) |
| `brand.colorOnDark` | Facultatif : couleur sur fond sombre, calculée sinon |
| `brand.accent` | Facultatif : seconde couleur du « U » |
| `brand.pictogram` | Tracé SVG (grille 24 × 24, contour) de l’icône d’application |
| `isDefault`, `showInPortal` | Comportement dans le portail |

Sans bloc `brand`, un champ `primaryColor` (couleur Tailwind) reste accepté.

## Ce qui est généré (ne pas modifier à la main)

- `shared/global-data/tools.generated.json` : registre lu par le front ;
- `shared/styles/uni-brand.css` : palettes de 50 à 950 et classes composées à l’exécution ;
- `shared/global-data/application-colors.json` : liste des couleurs disponibles ;
- `packages/shell/assets/assets/favicon.svg` : favicon du portail.

## Utiliser l’identité

Côté Vue :

```js
import { getApplicationMeta } from '@utils/applicationColors';

const app = getApplicationMeta('helpdesk'); // app.name, app.icon, app.textClass, app.cardClasses…
```

- `<HeaderComponent app="helpdesk" />` : titre, icône et couleur de la page (`titre` et `icon` restent possibles) ;
- `<ApplicationBadge app="helpdesk" />` : badge de l’application, utilisé sur les widgets ;
- `<UniLogo service="ticket" />` : logo, `service` étant ici la **clé d’identité** (`brand.key`).

Côté PHP :

```php
use App\Application\ApplicationCatalog;

ApplicationCatalog::nom('helpdesk'); // « UniTicket »
```

Le catalogue lit `packages/*/bundle.meta.json` : ces fichiers doivent être présents sur le serveur. Un widget
déclare seulement son code d’application (`getBundleCode()`), le libellé en est déduit.

## Garde-fous

`shared/utils/applicationRegistry.test.js` échoue si les fichiers générés ne sont plus à jour, si un alias est
attribué deux fois ou si une couleur passe sous le seuil de contraste WCAG AA.

## À mettre à jour à la main

Les `apple-touch-icon.png`, images fixes, ne sont pas régénérées.
