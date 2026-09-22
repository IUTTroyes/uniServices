# Commits et processus de release

UniService utilise **Conventional Commits**, **Commitlint** et **Release Please** pour automatiser le changelog, le versionnement et les releases GitHub.

## Commits et titres de Pull Requests

Le format attendu est :

```text
<type>(<scope>): <description>
```

Le scope est facultatif.

Exemples :

```text
feat(stage): add internship agreement validation
fix(core): correct department permissions
refactor(api): normalize document response
perf(db): optimize personnel query
docs: update installation instructions
ci: update GitHub Actions
chore(deps): update Symfony dependencies
```

### Types autorisés

| Type | Usage |
| --- | --- |
| `feat` | Nouvelle fonctionnalité |
| `fix` | Correction d'un bug |
| `perf` | Amélioration des performances |
| `refactor` | Refactoring sans nouvelle fonctionnalité ni correction |
| `docs` | Documentation |
| `test` | Tests |
| `build` | Build et outillage |
| `ci` | Intégration continue / GitHub Actions |
| `chore` | Maintenance |
| `style` | Changements de style sans impact fonctionnel |
| `revert` | Annulation d'un changement |

### Scopes

Les scopes actuellement reconnus sont :

`core`, `auth`, `document`, `helpdesk`, `intranet`, `questionnaire`, `stage`, `unifolio`, `shell`, `front`, `api`, `db`, `docker`, `deps`, `ci`.

Le scope est facultatif pour les changements globaux :

```text
docs: update installation instructions
ci: update GitHub Actions
```

Commitlint contrôle le **titre de chaque Pull Request vers `main`**. Avec le squash merge, ce titre devient le message du commit présent sur `main` : il est donc la source principale utilisée par Release Please.

## Versionnement

Le projet utilise une version globale et suit SemVer.

Pendant la phase `0.x` :

- `fix` provoque un incrément de patch : `0.1.10 -> 0.1.11` ;
- `feat` provoque un incrément de minor : `0.1.10 -> 0.2.0` ;
- un breaking change provoque également un incrément de minor avant `1.0.0`.

Les commits de documentation, CI, tests, maintenance ou refactoring ne déclenchent pas seuls une nouvelle version.

Un breaking change peut être signalé avec `!` :

```text
feat(api)!: change authentication response
```

ou avec un footer `BREAKING CHANGE:`.

## Processus de release

1. Une Pull Request est ouverte vers `main`.
2. La CI Commitlint valide son titre.
3. Les autres CI valident le backend, le frontend et les tests concernés.
4. La PR est fusionnée dans `main`, de préférence avec **Squash and merge**.
5. Release Please analyse les commits Conventional Commits présents sur `main`.
6. Release Please crée ou met à jour sa Pull Request de release avec :
   - la prochaine version ;
   - le fichier `CHANGELOG.md` ;
   - le manifest Release Please.
7. La Pull Request Release Please est relue puis fusionnée.
8. Release Please crée le tag `vX.Y.Z` et la GitHub Release correspondante.

Le workflow peut aussi être lancé manuellement depuis GitHub Actions grâce à `workflow_dispatch`.

## Changelog

Le changelog public contient principalement :

- ✨ Fonctionnalités (`feat`)
- 🐛 Corrections (`fix`)
- ⚡ Performances (`perf`)
- ♻️ Refactoring (`refactor`)
- 📚 Documentation (`docs`)

Les entrées purement techniques (`ci`, `test`, `chore`, `build`, `style`) sont masquées du changelog.

## GITHUB_TOKEN

Le workflow Release Please utilise :

```yaml
permissions:
  contents: write
  pull-requests: write
```

et :

```yaml
token: ${{ secrets.GITHUB_TOKEN }}
```

`GITHUB_TOKEN` est créé automatiquement par GitHub Actions pour chaque exécution : **aucun secret `GITHUB_TOKEN` n'est à créer manuellement**.

Les permissions déclarées dans le workflow doivent cependant être autorisées par la configuration du dépôt ou de l'organisation. Si Release Please reçoit une erreur de type `403 Resource not accessible by integration`, vérifier **Settings > Actions > General > Workflow permissions** et les éventuelles règles imposées au niveau de l'organisation.

### Limitation du GITHUB_TOKEN

Pour éviter les boucles de workflows, certaines actions réalisées avec `GITHUB_TOKEN` ne déclenchent pas de nouveaux workflows GitHub Actions.

Si la Pull Request créée par Release Please doit elle-même déclencher toutes les CI, il peut être nécessaire d'utiliser un token GitHub App ou un PAT dédié à la place de `GITHUB_TOKEN`. Ne faire ce changement que si ce comportement est réellement nécessaire.
