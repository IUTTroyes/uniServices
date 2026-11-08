# Tests du package Stages

## Tests unitaires

`packages/stage-bundle/tests/{Enum,Entity,Notification,Security,Service,State}` : sans base ni noyau.

```bash
vendor/bin/phpunit --testsuite StageBundle
```

## Tests d'API (`tests/Api`)

Requêtes HTTP réelles traitées par le noyau Symfony (environnement `test`), authentifiées par un vrai jeton JWT, sur
la base `<base>_test`. Chaque test tourne dans une transaction annulée à la fin : la base de test reste vide. Les
rapports déposés vont dans un dossier temporaire (`STAGE_RAPPORT_DIR`), jamais dans `var/stage/rapports`.
Sans base de test joignable, ces tests sont **ignorés** (la CI sans base reste verte).

Mise en place, une fois par poste :

1. `back/.env.test.local` (non versionné) : en environnement de test, Symfony ne lit pas `.env.local`. Y reprendre
   `DATABASE_URL` et les variables `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE` de `.env.local`.
2. Créer la base une fois (ex. ``CREATE DATABASE `uniService_test` `` si la base de dev est `uniService`), puis son
   schéma : `make test-db` (à relancer après chaque changement de schéma ; vide la base de test).
3. Lancer : `make test-php` (tous les tests PHP) ou `vendor/bin/phpunit packages/stage-bundle/tests/Api`.

Les jeux de données sont construits dans chaque test (`StageApiTestCase` : `departement()`, `periode()`,
`personnel()`, `etudiant()`, `dossier()`). Les messages `[error] Uncaught PHP Exception …` affichés pendant
l'exécution sont les refus attendus (403, 422…) consignés par le journal, pas des échecs.

Couverture : visibilité des dossiers par profil et par département, transitions de statut, dates posées par le serveur,
champs interdits (étudiant, tuteur), règles légales, rapport (dépôt, droits, téléchargement, retrait), avenants,
évaluation par l'entreprise (lien public à usage unique), périodes gérées, soutenances, modèle de convention.
