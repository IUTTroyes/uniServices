# Documentation technique — Dashboard widgets

## Objectif

Mettre en place un dashboard **dynamique et personnalisable** pour les personnels, avec :

- composition pilotée par le backend ;
- rendu dynamique côté Vue ;
- contrôle des droits et du contexte (département) côté serveur ;
- persistance des préférences utilisateur (activation, taille, ordre, réduction, config), **par `StructureDepartementPersonnel`**.

---

## Architecture globale

Le système repose sur 3 couches :

1. **Définition des widgets** (backend)
2. **Préférences utilisateur** (backend + base)
3. **Rendu et interactions UI** (frontend)

### Flux principal

1. Le front appelle `GET /api/dashboard`.
2. Le backend calcule la liste des widgets autorisés pour l’utilisateur courant.
3. Le backend fusionne définition widget + préférence utilisateur (si existante) pour la structure active.
4. Le front affiche la grille et instancie dynamiquement le composant Vue de chaque widget.
5. Chaque widget chargé appelle `GET /api/dashboard/widgets/{key}` pour ses données.
6. Lors d’une action UI (toggle, collapse, resize), le front appelle `PATCH /api/dashboard/widgets/{key}/layout` avec le contexte structure.

---

## Backend (Symfony / API)

### Contrat widget

Fichier : `back/src/Domain/Dashboard/DashboardWidgetInterface.php`

Ce contrat standardise chaque widget :

- identité (`getKey`, `getLabel`) ;
- composant Vue cible (`getVueComponent`) ;
- règles d’éligibilité (`supports`) ;
- configuration et layout par défaut ;
- endpoint de données (`getDataUrl`) ;
- charge utile du widget (`getData`).

### Contexte dashboard

Fichier : `back/src/Domain/Dashboard/DashboardContext.php`

Encapsule le contexte d’exécution (actuellement `departementId`) et expose des helpers (`hasDepartement`).

### Registre des widgets

Fichier : `back/src/Services/Dashboard/DashboardWidgetRegistry.php`

Le registre référence les widgets disponibles et permet :

- `all()` : itération de tous les widgets ;
- `get(key)` : accès ciblé pour l’endpoint de données.

Widgets implémentés :

- `EmploiDuTempsWidget`
- `ActionsUrgentesWidget`
- `DocumentsRecentsWidget`

### Préférences utilisateur

Entité : `back/src/Entity/Dashboard/DashboardPreference.php`  
Repository : `back/src/Repository/Dashboard/DashboardPreferenceRepository.php`

Données persistées :

- `widgetKey`
- `structureDepartementPersonnel` (nullable, mais utilisée pour isoler les dashboards par structure active)
- `enabled`
- `collapsed`
- `position`
- `size`
- `config` (JSON)

Contrainte d’unicité : `(personnel_id, widget_key, structure_departement_personnel_id)`.

### API

Contrôleur : `back/src/Controller/DashboardController.php`

Endpoints :

- `GET /api/dashboard`
  - retourne la composition finale de dashboard pour l’utilisateur.
- `GET /api/dashboard/widgets/{key}`
  - retourne les données d’un widget, avec contrôle d’accès/relation contexte.
- `PATCH /api/dashboard/widgets/{key}/layout`
  - met à jour la préférence utilisateur pour le widget ciblé.

Paramètre de contexte supporté sur ces endpoints :

- `structureDepartementPersonnelId` : identifiant de la structure active du personnel.

---

## Frontend (Vue)

### Point d’entrée dashboard

Fichier : `packages/intranet-bundle/assets/components/Personnel/Dashboard.vue`

Responsabilités :

- charger le dashboard (`getDashboardService`) ;
- charger les données widget (`getDashboardWidgetDataService`) ;
- persister les changements de layout (`patchDashboardWidgetLayoutService`) ;
- ordonner les widgets par `position` ;
- mapper la taille vers les classes de grille (`small`, `medium`, `large`).
- propager le contexte `structureDepartementPersonnelId` vers les appels API dashboard.

### Page de configuration des widgets

Fichier : `packages/intranet-bundle/assets/components/Personnel/dashboard/DashboardWidgetConfiguration.vue`

Responsabilités :

- afficher tous les widgets disponibles pour le personnel courant (déjà filtrés côté backend selon rôle + contexte) ;
- permettre d’activer/désactiver chaque widget ;
- persister immédiatement le choix utilisateur via `PATCH /api/dashboard/widgets/{key}/layout` ;
- proposer un accès/retour rapide au dashboard principal.

### Shell générique de widget

Fichier : `packages/intranet-bundle/assets/components/Personnel/dashboard/DashboardWidgetShell.vue`

Responsabilités :

- en-tête standardisé (titre + actions) ;
- gestion des états UI (`loading`, `error`) ;
- émission des événements d’interaction (`refresh`, `toggle-enabled`, `toggle-collapsed`, `resize`).

### Registre des composants Vue

Fichier : `packages/intranet-bundle/assets/components/Personnel/dashboard/dashboardWidgetRegistry.js`

Mappe la chaîne `component` venant de l’API vers le composant Vue concret.

### Services HTTP

Fichier : `shared/requests/dashboardService.js` (+ export dans `shared/requests/index.ts`)

Expose les appels API du dashboard côté front.

---

## Contrôle d’accès et sécurité

- Le backend est la **source de vérité** des widgets visibles.
- Le front n’affiche que ce que `GET /api/dashboard` retourne.
- Les données de widget sont filtrées via `supports(user, context)` (dont les rôles utilisateur) et les vérifications côté endpoint.
- Les préférences utilisateur n’accordent aucun droit supplémentaire : elles pilotent uniquement l’affichage.

---

## Modèle de données échangé

Exemple de `GET /api/dashboard` :

```json
{
  "widgets": [
    {
      "key": "emploi_du_temps",
      "label": "Aujourd'hui",
      "component": "EmploiDuTempsWidget",
      "enabled": true,
      "collapsed": false,
      "position": 0,
      "size": "large",
      "config": {
        "limit": 10
      },
      "dataUrl": "/api/dashboard/widgets/emploi_du_temps"
    }
  ]
}
```

---

## Extensibilité (ajout d’un nouveau widget)

1. Créer une classe backend implémentant `DashboardWidgetInterface`.
2. L’enregistrer dans `DashboardWidgetRegistry`.
3. Créer le composant Vue correspondant dans `assets/components/Personnel/dashboard/widgets`.
4. L’ajouter dans `dashboardWidgetRegistry`.
5. (Optionnel) Ajouter une logique de config utilisateur spécifique via `config`.

---

## Points d’attention opérationnels

- Générer/appliquer la migration Doctrine pour `DashboardPreference` avant déploiement.
- Garder les clés widget (`key`) stables pour préserver les préférences existantes.
- Valider côté backend les valeurs de layout (`size`, `position`, `config`) pour éviter les entrées invalides.
- Prévoir une stratégie de fallback si un composant front manque dans le registre.
