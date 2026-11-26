# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

uniServices est la refonte modulaire (V4) de l'intranet de l'IUT de Troyes. Documentation et commentaires sont en français.

## Architecture

Monorepo hybride PHP/JS :

- `back/` : application Symfony 8.1 / PHP 8.4 (noyau). Contient les entités Doctrine, repositories, `ApiResource`, `State` (providers/processors API Platform), `Security` (voters), JWT (Lexik), LDAP, Messenger. Les entités et le métier partagés vivent ici, pas dans les bundles.
- `packages/<nom>-bundle/` : bundles Symfony (auth, intranet, document, finance, helpdesk, message, questionnaire, stage, unifolio) qui embarquent **à la fois** leur code PHP (`src/`, namespace `XxxBundle\`) et leur frontend Vue (`assets/`). Les bundles sont enregistrés dans `back/config/bundles.php` et les autoloads sont déclarés dans `composer.json` (racine) **et** `back/composer.json`.
- `packages/shell/` : application Vue hôte qui compose les bundles (dépend de `@uni-service/auth` et `@uni-service/intranet`, proxy Vite par préfixe `/auth`, `/intranet`, `/stage`... vers un serveur de dev par bundle, `/api` vers le backend). Chaque bundle expose un `assets/manifest.ts` (menus, routes, widgets).
- `shared/` : ressources frontend communes importées par alias (`@components`, `@stores`, `@requests`, `@helpers`, `@utils`, `@composables`, `@dialogs`, `@config` = `shared/global-data`, `@types`, `@images`, `@styles`). `@/` désigne le dossier `assets` du bundle courant (voir `vitest.config.js` pour la résolution).
- `scripts/` : `create-bundle.php`, `activate-bundle.php`, `deactivate-bundle.php`, `remove-bundle.php` modifient automatiquement `package.json`, les `composer.json`, `bundles.php` et `shared/global-data/tools.generated.json`. Pour créer/retirer un bundle, passer par ces scripts plutôt qu'à la main.
- Autorisation côté serveur : rôles via `PermissionRegistry`/`PermissionResolver`, droits par affectation (`UserEffectivePermissionService`), voters par domaine. Voir `docs/VOTERS.md` et `docs/ROLES_ET_ACCES.md` avant de toucher aux accès.
- Autres références utiles dans `docs/` : `NOTIFICATIONS_SYSTEM.md`, `EMAIL_SYSTEM.md`, `WIDGETS_SYSTEM.md`, `AUTH_SECURE_TOKENS.md`. Les documents `back/MIGRATION_*.md` décrivent la migration depuis l'intranet V3.

## Commandes

Backend (depuis la racine sauf mention) :

```bash
make start-back                      # symfony server:start (dans back/)
vendor/bin/phpunit                   # tests PHP (suites Core + bundles), config phpunit.xml.dist à la racine
vendor/bin/phpunit --testsuite StageBundle
vendor/bin/phpunit packages/message-bundle/tests --filter NomDuTest
make check-back                      # comme la CI : composer validate, lint:container, doctrine:schema:validate, phpstan, tests
make phpstan                         # PHPStan niveau 6 (back/phpstan.neon : back/src + auth-bundle/src)
make test-db                         # recrée la base « <base>_test » (tests d'API ; requiert back/.env.test.local)
```

Frontend (pnpm, Node 22) :

```bash
pnpm install --frozen-lockfile
make start-front                     # pnpm run dev (shell sur le port 3000 ; les bundles ont leurs propres ports via proxy)
npx vitest run                       # tous les tests (globals + jsdom)
npx vitest run packages/auth-bundle/assets/tests/aideAcces.test.js   # un seul fichier
npx vitest run -t "nom du test"
pnpm --filter @uni-service/shell build
make check-front                     # install + tests + build, comme la CI
npx cypress run                      # E2E (dossier cypress/)
```

Commits : convention Conventional Commits imposée par commitlint (CI), versions gérées par release-please.

## Conventions frontend

@ruleset.md

Styleguide UI (`.agents/rules/styleguide.md`, détail dans `docs/STYLEGUIDE_AI_AGENTS.md`) : préférer les composants de `@components` (`HeaderComponent`, `Card`, `Kpi`, `EmptyState`, `ButtonDelete`...) ; couleurs via les clés de thème (`blue`, `emerald`, `red`...), statuts via `<Tag>` PrimeVue ; `GlobalLoader` réservé aux opérations bloquantes.

## Tests PHP

Les tests d'API des bundles utilisent une base `<base>_test` (voir `packages/stage-bundle/docs/tests.md`).
