# Distributions et isolation des packages

## Choisir une distribution

| Distribution | Packages | Liste PHP | Registre front |
| --- | --- | --- | --- |
| `intranet` (défaut) | tous | `back/config/bundles.php` | `packages/shell/assets/bundles-registry.js` |
| `unifolio` | auth, formation, unifolio | `back/config/distributions/unifolio.php` | `packages/shell/assets/distributions/unifolio.js` |

```bash
APP_DISTRIBUTION=unifolio php back/bin/console cache:clear
```

```bash
APP_DISTRIBUTION=unifolio pnpm --filter @uni-service/shell build
```

- Réglages propres à une distribution : `back/config/distributions/<nom>.yaml` (paquets implicites, applications des étudiants).
- Front : la liste des packages est aussi déclarée dans `packages/shell/distributions.js`.
- Le cache PHP d'une autre distribution est dans `back/var/cache/<nom>/`.

## Règle de dépendance

Le cœur (`back/src`) ne cite aucun package ; un package ne cite que le cœur et lui-même.
`tests/Architecture/IsolationDesPackagesTest.php` le vérifie (la reprise V3, `back/src/Migration`, est exclue).

## Points d'extension (`back/src/Extension`)

| Besoin d'un package | À implémenter |
| --- | --- |
| Ajouter un champ à l'API d'un objet du cœur | `ChampsSerialisesInterface` (absent de la documentation OpenAPI) |
| Déclarer les règles d'année universitaire de ses classes | `PerimetreAnnuelProviderInterface` (obligatoire pour toute nouvelle ressource) |
| Entité sans année (modèle) | `HorsAnneeInterface` |
| Fournir les affectations personnel / enseignement | `AffectationsEnseignementInterface` |
| Empêcher la suppression d'un objet du cœur | `SuppressionBloqueeInterface` |
| Déduire le département d'un objet | `RattachementPerimetreInterface` |

Supprimer en cascade : écouteur Doctrine `preRemove` dans le package. Restreindre une collection : extension de requête
dans le package (base : `App\Doctrine\AbstractVisibiliteDepartementExtension`).

Les relations vers un objet du cœur sont **unidirectionnelles** : pas de `inversedBy`.

## Configuration d'un package

Son extension hérite de `App\Extension\AbstractPackageExtension`, qui déclare services, mapping Doctrine, migrations,
gabarits et, au besoin, ressources API. Les contrôleurs de `src/Controller` sont importés par le noyau.
`scripts/create-bundle.php` génère cette extension, `services.yaml` et le fournisseur de périmètre annuel.
Un nouveau package n'entre que dans la distribution `intranet`. `remove-bundle.php` et `deactivate-bundle.php`
refusent un package cité par une autre distribution.

## Base de données

- Migrations initiales : `back/migrations/` (cœur) et `packages/*/migrations/` (MySQL).
- Base vide : `php bin/console doctrine:migrations:migrate`.
- Base existante : la même commande enregistre les migrations initiales sans exécuter de requête.
  Elle doit avoir reçu auparavant les migrations de `back/migrations-archive/`.
- Nouvelle migration : la placer dans le dossier du package dont les tables changent, avec la bonne `--namespace`.
