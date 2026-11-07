# UniFolio v2 — Plan de refonte dans uniServices

> Statut : **plan validé** (décisions D1 à D6 tranchées) — rien n'est implémenté au-delà du squelette existant.
> Source analysée : [CyndelHerolt/UniFolio](https://github.com/CyndelHerolt/UniFolio) (Symfony + Twig + Live Components).

---

## 1. Point de départ

### 1.1 Ce que fait UniFolio v1

| Domaine | Entités v1 | Remarques |
|---|---|---|
| Traces | `Trace`, `Bibliotheque`, `OrdreTrace` | Une trace = un seul type (image, pdf, vidéo, lien) stocké dans `contenu` (array). Champs IA (`conception_ia`, `realisation_ia`). Bibliothèque = étudiant × année universitaire. |
| Rattachement compétences | `Validation` (trace ↔ `ApcNiveau`, `etat` int 0‑3) | La « validation » sert à la fois de rattachement **et** d'évaluation. Pas de rattachement aux AC. |
| Portfolio | `Portfolio`, `Page`, `OrdrePage`, `Templates` | Pages = listes ordonnées de traces. Pas de mise en page libre. Visibilité booléenne. |
| Évaluation | `Validation.etat`, `Commentaire` | Une seule échelle codée en dur, pas de critères, pas d'auto‑évaluation. |
| CV | `Cv`, `Experience`, `Formation` | Langues / soft / hard skills en JSON. |
| Pilotage | `BilanPedagogique`, dashboards | Statistiques de validation par compétence. |
| Référentiel / structure | `Annee`, `Semestre`, `ApcNiveau`… + synchro Intranet | **Duplication** des données de l'intranet → à supprimer. |

### 1.2 Ce que fournit déjà uniServices

- **Cœur `back/src/Entity`** : `Etudiant`, `Personnel`, `EtudiantScolarite` (+ `…Semestre`), `StructureAnneeUniversitaire`, `StructureAnnee`, `StructureSemestre`, `StructureTypeDiplome` (flag `apc`), `StructureDiplome`, `ApcReferentiel` → `ApcCompetence` → `ApcNiveau` → `ApcApprentissageCritique`.
- **Squelette `packages/unifolio-bundle`** : `UnifolioBundle`, `UnifolioPermissionProvider` (référent, correcteur, publisher), 3 widgets dashboard **avec données factices**, `manifest.ts` vide (couleur `emerald`).
- **Conventions de bundle** (cf. `message-bundle`, `stage-bundle`) : entités dans le bundle, contrôleurs `/api/...`, `Resources/config/{services,routes}.yaml`, tests PHPUnit + Vitest, commande de migration depuis l'ancienne appli (`MigrateIntranetV3StageCommand`), `GotenbergService` pour le PDF.
- **Front** : Vue 3, PrimeVue 4, Pinia, Tailwind 4, `vue-draggable-next` déjà présent.

**Principe directeur : zéro duplication du référentiel et de la structure.** Le bundle ne stocke que ce qui est propre au portfolio et référence les entités cœur.

---

## 2. Périmètre fonctionnel v2

| # | Module | Nouveau / repris |
|---|---|---|
| M1 | Bibliothèque de traces multi‑médias | Repris + enrichi |
| M2 | Rattachement des traces aux compétences / niveaux / **apprentissages critiques**, avec argumentaire | Enrichi (AC nouveaux) |
| M3 | **Grilles d'évaluation personnalisables par type de diplôme** (critères, échelles, pondération, versions) | Nouveau |
| M4 | **Auto‑évaluation étudiante** (par trace et bilan par compétence/période) | Nouveau |
| M5 | **Évaluation enseignante** (campagnes, affectation, workflow, commentaires, comparaison avec l'auto‑éval) | Refondu |
| M6 | **Portfolio public à blocs (type Gutenberg)** + publication versionnée | Refondu |
| M7 | CV structuré réutilisable dans le portfolio | Repris |
| M8 | **Suivi pluriannuel** (progression par année universitaire, archives, tableaux de bord) | Enrichi |
| M9 | Notifications, widgets, exports PDF | Repris + branché sur l'existant |
| M10 | Migration des données UniFolio v1 | Nouveau |

---

## 3. Modèle de données (préfixe de tables `unifolio_`)

```
Etudiant ──< Trace ──< TraceMedia
              │
              ├──< TraceRattachement >── ApcCompetence | ApcNiveau | ApcApprentissageCritique
              │        │
              │        ├──< AutoEvaluation ──< ReponseCritere
              │        └──< Evaluation ─────< ReponseCritere
              │                   │
              └──< Commentaire    └── GrilleVersion ──< GrilleCritere ──< GrilleEchelon
                                         │
StructureTypeDiplome ──< Grille ──< GrilleVersion

Etudiant ──< Portfolio ──< PortfolioPage (blocs JSON)
                 └──< PortfolioPublication (snapshot JSON figé)

StructureAnneeUniversitaire ──< Campagne ──< CampagneAffectation (évaluateur ↔ étudiants)
```

### 3.1 Traces (M1, M2)

- **`Trace`** : `etudiant`, `scolarite` (`EtudiantScolarite` → donne année universitaire + année de formation), `titre`, `slug`, `contexte` (texte riche), `dateRealisation`, `typeActivite` (SAÉ, stage, projet perso, alternance, associatif… — enum extensible), `enseignement` optionnel (`ScolEnseignement`, ex. une SAÉ), `conceptionIa` / `realisationIa` (enums v1 conservés, déclaration **obligatoire** au dépôt — D6), `usageIaDetail` (texte libre : outils utilisés, pour quoi faire), `statut` (`brouillon`, `deposee`, `en_evaluation`, `evaluee`, `a_reviser`), `tags` (JSON), timestamps + UUID (traits cœur).
- **`TraceMedia`** (n par trace, ordonnés) : `type` (`image`, `pdf`, `video`, `lien`, `fichier`, `texte`, `code`, `embed`), `fichier`/`url`, `legende`, `ordre`, `metadata` JSON. → Une trace peut combiner plusieurs preuves (corrige la limite v1 « un type par trace »).
- **`TraceRattachement`** : `trace`, **une seule** des FK `competence` / `niveau` / `apprentissageCritique` (contrainte CHECK + validation), `argumentaire` (pourquoi cette trace prouve cet élément), `ordre`. Le niveau de granularité exigé est **paramétrable par type de diplôme** (cf. 3.2).

### 3.2 Grilles d'évaluation (M3)

- **`ParametrageFormation`** : `diplome` (`StructureDiplome`), `departement`, `granularite` (`competence` | `niveau` | `ac`), `grille` par défaut, `applicableDepuis` (`StructureAnneeUniversitaire`). Une formation peut changer de granularité d'une année à l'autre ; les évaluations passées gardent leur `grilleVersion` et leur cible.
- **Périmètre (D1)** : uniquement les types de diplôme APC (BUT). Le `GrilleResolver` et les voters refusent tout étudiant inscrit dans un diplôme non APC ; le menu UniFolio n'apparaît pas pour lui.
- **`Grille`** : `libelle`, `typeDiplome` (obligatoire, `apc = true`), surcharge optionnelle `diplome` / `departement`, `cible` (`rattachement`, `trace`, `portfolio`, `competence_bilan`), `granularite` (`competence`, `niveau`, `ac`), `actif`.
- **`GrilleVersion`** : `numero`, `publieeLe`, `verrouillee`. **Toute évaluation pointe sur une version figée** : modifier une grille crée une nouvelle version, l'historique reste lisible.
- **`GrilleCritere`** : `libelle`, `description`, `aide` (indicateurs observables), `type` (`echelle`, `booleen`, `note`, `texte`), `poids`, `obligatoire`, `ordre`, `visibleEtudiant`, `utiliseAutoEval`.
- **`GrilleEchelon`** — *réalisé (lot 2) : échelle **commune à la version** (et non par critère), utilisée par tous les critères de type `echelle`* : `libelle` (ex. Non acquis / En cours / Acquis / Maîtrisé), `valeur`, `couleur`, `descripteur`.
- Grilles **par défaut** fournies via fixtures : une grille BUT par granularité (compétence, niveau, AC — échelle 4 niveaux) ; les départements peuvent les dériver.
- La `granularite` de la grille doit correspondre à celle du `ParametrageFormation` (contrôle à l'enregistrement d'une campagne).
- **Critère « Usage de l'IA » optionnel (D6)** : `GrilleCritere.source = declaration_ia` — critère que le référent peut ajouter à une grille (case « Évaluer l'usage de l'IA »). Le formulaire d'évaluation affiche alors la déclaration de l'étudiant (`conceptionIa`, `realisationIa`, `usageIaDetail`) à côté du critère ; l'enseignant évalue la **pertinence et la transparence** de l'usage avec l'échelle de la grille. Pondération libre (0 possible = critère informatif). Absent des grilles par défaut.
- Résolution de la grille applicable (service `GrilleResolver`) : grille choisie dans le paramétrage si publiée, sinon la plus spécifique des grilles actives et publiées — diplôme > département > type de diplôme.
- *Lot 2* : grilles nationales BUT installées par `bin/console unifolio:grilles:init` (idempotent). Les référents adaptent une grille nationale en la dupliquant pour leur département ; seules les grilles de département/diplôme leur sont modifiables.
- *Lot 2* : les rôles UniFolio ne sont effectifs que si le paquet `unifolio` est activé sur l'affectation départementale du personnel (règle du cœur `PermissionResolver`).

### 3.3 Auto‑évaluation (M4)

- **`AutoEvaluation`** : `etudiant`, `cible` (rattachement **ou** bilan compétence/niveau sur une campagne), `grilleVersion`, `reflexion` (texte : points forts, axes de progrès), `statut` (`brouillon`, `soumise`), `soumiseLe`.
- **`ReponseCritere`** partagée auto‑éval / éval : `critere`, `echelon` | `valeurNumerique` | `valeurBool` | `texte`.
- Vue « écart » : comparaison auto‑éval vs évaluation enseignante, critère par critère (levier pédagogique fort).
- *Réalisé (lot 3)* : bilan rattaché à l'**année de BUT** (`EtudiantScolarite`) en attendant les campagnes du lot 4 ; une seule cible par auto-évaluation (rattachement **ou** élément du référentiel, contrainte CHECK) ; version de grille figée à l'ouverture ; score pondéré normalisé 0–1 (échelle ramenée entre son min et son max, oui/non = 1/0, note / note max) ; seuls les critères « utilisé en auto-évaluation » sont proposés. Sans paramétrage de la formation, la granularité du bilan est celle de la grille retenue.

### 3.4 Évaluation enseignante (M5)

- **`Campagne`** : `anneeUniversitaire`, `libelle`, `semestres` / `groupes` ciblés, `grille`, dates (`ouverture`, `depotLimite`, `evaluationLimite`, `cloture`), `autoEvaluationRequise`, `typeCible` (traces, portfolio, bilan).
- **`CampagneAffectation`** : `campagne`, `evaluateur` (`Personnel`), `etudiants` (ou groupe) — alimente le widget « À corriger ».
- **`Evaluation`** : `evaluateur`, `cible`, `campagne`, `grilleVersion`, `appreciation`, `score` calculé (pondération), `statut` (`en_cours`, `rendue`, `revision_demandee`), `rendueLe`, `visibleEtudiant`.
- **`Commentaire`** : fil de discussion sur trace / rattachement / bloc de portfolio (`parent`, `auteur` étudiant|personnel, `visibilite`, ancrage `blockId` optionnel).
- Multi‑évaluateurs possibles (double correction, jury) : plusieurs `Evaluation` sur une même cible + vue de synthèse.
- *Réalisé (lot 4)* :
  - **Campagne** = une formation × une année universitaire × (optionnellement) une année de BUT ; unique par triplet. Phases calculées : à venir → dépôt → évaluation → clôturée. Évaluer est possible dès l'ouverture (au fil de l'eau) ; le dépôt se ferme à la limite de dépôt, sauf pour les traces dont une révision est demandée ; la clôture fige tout (évaluations, dépôts).
  - **Affectation** = un enseignant ↔ un étudiant pour une campagne (plusieurs enseignants possibles par étudiant) ; par étudiants cochés, par groupe ou pour toute la campagne ; l'enseignant doit appartenir au département de la formation. Un évaluateur ne voit que les traces **déposées** de ses étudiants, jamais les brouillons.
  - **Évaluation** = sur un rattachement de trace *ou* un bilan d'élément (comme l'auto-évaluation), avec la version de grille figée, un score pondéré 0–1 et une appréciation. Statuts : en cours → rendue (communiquée ou non à l'étudiant) ou révision demandée (trace uniquement, motif obligatoire). Une évaluation rendue se rouvre tant que la campagne n'est pas clôturée.
  - **Statut des traces synchronisé** : première évaluation ouverte → « en évaluation » ; tous les rattachements avec une évaluation rendue → « évaluée » ; révision demandée depuis le dernier dépôt → « à réviser » ; redépôt → « déposée » (l'évaluateur reprend en rouvrant son évaluation).
  - **Auto-évaluation** : verrouillée dès qu'une évaluation rendue existe sur la même cible ; si la campagne l'exige, l'enseignant ne peut ouvrir une évaluation que sur une auto-évaluation soumise.
  - **Synthèse d'un élément** : moyenne des évaluateurs par rattachement, puis règle de la formation (meilleure / moyenne / plus récente) — `SyntheseElement`.
  - **Comparaison** auto-évaluation / évaluation critère par critère (rapprochement par libellé, car les deux peuvent reposer sur deux versions de grille), écart qualifié « concordant » sous 10 points.
  - **Commentaires** : fil à un seul niveau sur une trace, ancrable sur un rattachement, visible de l'étudiant, de ses évaluateurs affectés et du référent.
  - **Notifications** (système d'e-mails du cœur, jamais bloquantes) : évaluation rendue, révision demandée, relance d'évaluateur (`unifolio:campagnes:relancer [--jours=7] [--dry-run]`, **à planifier dans le Scheduler**).
  - **Widgets** : « à corriger », « progression » (éléments couverts par une trace déposée) et « alertes » alimentés par les vraies données.
  - **Évaluation en lot (retour d'usage)** : tous les points d'une trace (ses rattachements) ou de l'année d'un étudiant (ses bilans) s'évaluent sur **une seule page**, sans fenêtre : trace à gauche, points à droite regroupés par compétence (couleur), barre d'actions fixe (enregistrer tout, rendre tout, tout rouvrir, demander une révision, « remplir d'un coup »). Afficher le formulaire ne crée rien : une évaluation n'est créée que pour un point renseigné. « Rendre » est **tout ou rien** (toutes les anomalies sont listées) et n'envoie **qu'un seul e-mail** par lot. La demande de révision s'applique à toute la trace (un motif, un e-mail). API : `/api/unifolio/evaluations/lot/{trace|bilans}/…` (`EvaluationLot`).
  - **Commentaires facultatifs** : un critère de type texte (appréciation libre) n'est jamais obligatoire, ni pour l'enseignant ni pour l'étudiant ; sans critère texte dans la grille, un commentaire facultatif est proposé par point.
  - *Reporté* : évaluation de portfolios (lots 5-6), tableaux de bord de promo (lot 7), notification des commentaires.

- **Granularité choisie par la formation (D5)** — une seule granularité active, au choix de chaque formation :
  - `competence` : évaluation par compétence (`ApcCompetence`) ;
  - `niveau` : évaluation par niveau de compétence (`ApcNiveau`) ;
  - `ac` : évaluation par apprentissage critique (`ApcApprentissageCritique`).
  - **Périmètre = l'année concernée** : seuls les éléments du référentiel liés à la `StructureAnnee` de la scolarité de l'étudiant (via `ApcNiveau.annee`, filtré par parcours) sont proposés au rattachement, à l'auto‑évaluation et à l'évaluation. En granularité `competence`, la compétence est évaluée au niveau attendu cette année‑là.
    - *Constat lot 1* : `apc_niveau.annee_id` n'est pas renseigné dans les données. Le service `ReferentielAnnee` utilise ce lien s'il existe, sinon les niveaux du référentiel du diplôme dont `ordreAnnee` (à défaut `ordre`) vaut le rang de l'année de BUT (`StructureAnnee.ordre`) — une compétence peut démarrer au niveau 1 en BUT2.
  - Le choix s'applique **de bout en bout** : rattachement des traces, auto‑évaluation, évaluation enseignante, tableaux de bord et blocs de progression utilisent la même cible.
  - Un AC / niveau / compétence peut être couvert par plusieurs traces : agrégation configurable (`max`, `moyenne`, `derniere`) pour le bilan.

### 3.5 Portfolio à blocs (M6, M7)

- **`Portfolio`** : `etudiant`, `titre`, `slug`, `type` (`academique` — rattaché à une année/campagne et évalué — ou `vitrine` — pro, public), `scolarite` (si académique), `theme` (palette, typo, mise en page), `banniere`, `visibilite` (`prive`, `lien_secret`, `etablissement`, `public`), `tokenPartage`, `indexable` (SEO opt‑in, remplace `opt_search`).
- **Deux types (D3)**, même moteur de pages/blocs :

  | | Académique | Vitrine |
  |---|---|---|
  | Cardinalité | 1 par `EtudiantScolarite` (une par année de BUT) — unicité `(etudiant, scolarite, type)` | 1 par étudiant, persistant sur tout le cursus et après diplomation |
  | Création | Automatique à l'ouverture de la campagne, à partir du **modèle imposé** par le référent | À l'initiative de l'étudiant, modèles optionnels |
  | Structure | Pages/blocs obligatoires verrouillables (ex. une page par compétence, bloc réflexion requis) | Totalement libre |
  | Contenu | Traces de l'année + traces antérieures réutilisables | N'importe quelle trace, CV, blocs libres |
  | Évaluation | Oui : cible des campagnes, évalué sur la **publication soumise** | Non |
  | Visibilité | `prive` / `etablissement` (étudiant + évaluateurs + référents) | `prive` / `lien_secret` / `etablissement` / `public`, indexation opt‑in |
  | Cycle de vie | Brouillon → soumis (snapshot) → évalué → **lecture seule à la clôture** | Éditable en continu, republication à volonté |

  - Passerelle : « Copier dans ma vitrine » (pages ou blocs d'un portfolio académique → vitrine), sans lien vivant, pour que l'archive académique reste intacte.
  - Les traces référencées dans une vitrine publique ne deviennent publiques que via le snapshot de publication (pas d'accès direct aux médias privés).
- **`PortfolioPage`** : `portfolio`, `verrouillee` (imposée par le modèle), `titre`, `slug`, `ordre`, `parent` (sous‑pages), **`blocs` JSON** (document type Gutenberg) :
  ```json
  [{ "id": "uuid", "type": "heading", "attrs": { "level": 2, "text": "…" } },
   { "id": "uuid", "type": "columns", "attrs": { "ratio": "1-2" }, "children": [ … ] },
   { "id": "uuid", "type": "trace", "attrs": { "traceUuid": "…", "display": "card" } }]
  ```
  Stockage JSON plutôt que relationnel : ordre/imbrication triviaux, versionnage par snapshot, même modèle que Gutenberg/Editor.js.
- **`PortfolioPublication`** : snapshot immuable (pages + blocs + données des traces résolues) créé à chaque « Publier ». Le public et les évaluateurs voient la version publiée ; l'étudiant continue d'éditer son brouillon. Champs de rendu : `statutRendu` (`en_attente`, `genere`, `erreur`), `cheminRendu`, `genereLe`, `active` (version servie).
- **Rendu statique (D2)** — chaque publication est **pré‑générée en HTML** :
  1. « Publier » crée le snapshot puis envoie un message `GeneratePortfolioStaticRender` (Symfony Messenger, asynchrone).
  2. Le handler appelle le **service de rendu Node** (`portfolio-renderer`) qui exécute `renderToString` sur les **mêmes composants `Render.vue`** que l'éditeur ; il produit une page HTML par page de portfolio, le CSS du thème (Tailwind purgé), les balises OpenGraph/SEO, un `sitemap.xml` si `indexable`.
  3. Les médias référencés sont copiés dans le dossier de la publication avec dérivés redimensionnés (`srcset`) ; aucun lien vers les médias privés de la bibliothèque.
  4. Blocs interactifs (galerie, onglets, accordéon, radar) : **îlots** — petit script JS ciblé, sans charger l'application Vue.
  5. Bascule atomique : la nouvelle version devient `active` une fois entièrement générée ; les anciennes restent consultables (historique, preuve d'évaluation).
  - Régénération : republication, changement de thème, mise à jour d'un composant `Render` (commande `unifolio:render:rebuild [--all|--portfolio=]`).
  - **Service selon la visibilité** :
    - `public` : fichiers servis directement par le serveur web sous `/p/{slug}/` (cache HTTP long).
    - `lien_secret`, `etablissement` : fichiers stockés **hors webroot**, servis par un contrôleur Symfony qui vérifie token / authentification puis renvoie le fichier (`BinaryFileResponse`, `X-Sendfile` si disponible).
    - Dépublication ou modération : désactivation immédiate (`active = false`) et purge des fichiers publics.
  - **Exports dérivés** du même HTML : PDF via `GotenbergService` (rendu identique) et **archive ZIP** autonome téléchargeable par l'étudiant (conservation après diplomation).
- **Registre de blocs** (front et back partagent un `blockType` + JSON Schema par bloc, validé côté back) :
  - Texte : titre, paragraphe (Tiptap), citation, liste, encadré/callout, séparateur, bouton.
  - Médias : image, galerie, vidéo, fichier, embed (YouTube, Figma, CodePen… liste blanche).
  - Mise en page : colonnes, groupe/section avec fond, onglets/accordéon.
  - **Blocs métier** : trace (carte / détaillée), liste de traces filtrée par compétence, radar/progression des compétences, matrice compétences × années, CV (expériences, formations, langues, skills), timeline du parcours, auto‑évaluation/réflexion.
- **CV** : `Cv`, `CvExperience`, `CvFormation` (reprise v1) — données sources des blocs CV, export PDF via `GotenbergService`.
- **Modèles** de portfolio (remplacent `Templates` v1) définis par les référents : structure de pages + blocs pré‑remplis, éventuellement imposés pour le portfolio académique.
- *Réalisé (lot 5)* :
  - **Schéma de blocs unique** `blocs.json` (22 types, 4 groupes, limites : profondeur 4, 300 blocs, 256 Ko) lu par le back (`BlocRegistry`, `BlocValidator`) **et** le front (`blocs/schema.js`) : un seul endroit à modifier pour ajouter un bloc.
  - **Frontière de sécurité = `BlocValidator`** : aucun HTML accepté, URL en http(s) uniquement, intégrations limitées à une liste blanche (`EmbedPolicy`), traces et médias vérifiés comme appartenant à l'étudiant (rien de personnel dans un modèle), attributs inconnus supprimés, valeurs par défaut complétées. Un bloc incomplet reste enregistrable mais est signalé (`incomplets`) : il bloquera la publication (lot 6).
  - **Texte riche = Markdown simplifié** (`blocs/markdown.js`, tout HTML échappé avant rendu) au lieu de Tiptap, non installé : aucune dépendance ajoutée. Remplaçable plus tard sans toucher au schéma.
  - **Pages plates** (pas de sous-pages) ; concurrence optimiste par `version` de page (conflit → « Recharger »). Pages et blocs **imposés** par le modèle : ni suppression, ni renommage, ni changement de type (le contenu reste libre).
  - **Portfolio académique** créé automatiquement à la première consultation de la liste par l'étudiant (idempotent) pour chaque campagne ; copie du modèle de la campagne (identifiants régénérés), à défaut structure par défaut (présentation, une page par compétence, CV) ; **lecture seule à la clôture**.
  - **Modèles** par département ou formation (national en lecture seule, duplicable), liés à une campagne ; suppression refusée s'ils sont utilisés. **CV** : document complet remplacé à chaque enregistrement, toutes les erreurs listées.
  - *Reporté au lot 6* : `slug`, bannière, jeton de partage, indexation, création de la vitrine, « Copier dans ma vitrine », publication / snapshot et rendu statique. Filtre par compétence dans un modèle (les traces y sont personnelles) non disponible.

### 3.6 Suivi pluriannuel (M8)

- Les traces sont rattachées à une `EtudiantScolarite` → elles suivent l'étudiant d'année en année ; la bibliothèque est filtrable par année mais **globale** (une trace de BUT1 peut être réutilisée en BUT3).
- Clôture de campagne → portfolio académique et évaluations passent en **lecture seule** (archive).
- Tableaux de bord :
  - Étudiant : progression par compétence et niveau sur l'ensemble du cursus, AC couverts / non couverts, échéances.
  - Enseignant / référent : matrice étudiants × compétences d'une promo, taux de dépôt, évaluations en retard, écarts auto‑éval/éval.
  - Les 3 widgets existants branchés sur des données réelles.

---

## 4. Architecture technique

### Back (`packages/unifolio-bundle/src`)

```
Entity/            Trace, TraceMedia, TraceRattachement, Grille*, AutoEvaluation, Evaluation,
                   ReponseCritere, Campagne*, Commentaire, Portfolio*, Cv*
Enum/              StatutTrace, TypeMedia, TypeActivite, ConceptionIa, RealisationIa,
                   VisibilitePortfolio, StatutEvaluation, TypeCritere…
Repository/
Controller/        /api/unifolio/{traces,portfolios,grilles,campagnes,evaluations,…}
                   + /p/{slug}/… (rendus statiques protégés : lien secret / établissement)
Security/          UnifolioPermissionProvider (existant) + Voters : Trace, Portfolio, Evaluation, Grille
Service/           GrilleResolver, ScoreCalculator, ReferentielScopeResolver (éléments de l'année), BlockValidator (JSON Schema), PortfolioPublisher,
                   ProgressionCalculator, MediaStorage, StaticRenderStorage, RendererClient
Message/           GeneratePortfolioStaticRender (+ handler, transport async)
Services/Dashboard WidgetProviders (existants → données réelles)
Email/             notifications (évaluation rendue, révision demandée, échéance) via EmailService cœur
Command/           MigrateUnifolioV1Command, RelanceEcheancesCommand (Scheduler), RebuildStaticRenderCommand
DataFixtures/      grilles par défaut BUT
tests/
```

- **API** : contrôleurs JSON (style `message-bundle`) pour les workflows ; API Platform possible pour les CRUD simples si c'est la convention retenue sur le cœur (le cœur l'utilise sur `Apc*`).
- **Fichiers** : réutiliser le mécanisme de stockage existant (à confirmer : celui de `document-bundle`), vignettes images, limites de taille/type, accès aux médias soumis aux voters (sauf portfolio publié public).

### Front (`packages/unifolio-bundle/assets`)

```
manifest.ts        routes + menu (étudiant / enseignant / référent)
views/             Bibliotheque, TraceEdit, TraceDetail, PortfolioList, PortfolioEditor, PortfolioPreview,
                   AutoEvaluation, CampagnesList, CampagneEdit, EvaluationQueue, EvaluationForm,
                   GrillesList, GrilleEditor, SuiviPromo, SuiviEtudiant, Cv
editor/            BlockEditor.vue, BlockToolbar, BlockInserter (/ commande), BlockInspector (panneau latéral),
                   blocks/<type>/{Edit.vue, Render.vue, schema.json}, registry.ts
renderer/          entrée SSR (renderPublication.ts) + îlots JS des blocs interactifs + thèmes
stores/ requests/  Pinia + services API
```

### Service de rendu (`docker/portfolio-renderer`)

- Petit service Node (HTTP interne, non exposé) qui importe le registre de blocs et les `Render.vue` du bundle via `@vue/server-renderer`, construit avec Vite en mode SSR.
- Entrée : snapshot JSON + thème ; sortie : fichiers HTML/CSS/JS écrits dans le stockage de rendu.
- Conteneur ajouté au `docker-compose` ; build dans la CI avec le front.

- Éditeur de blocs **maison** : liste de blocs + `vue-draggable-next` (déjà en dépendance) + **Tiptap** pour le texte riche + inspecteur de propriétés PrimeVue. Chaque bloc fournit `Edit` et `Render` : le même `Render` sert à l'aperçu, au rendu statique public et au PDF. Contrainte : les `Render.vue` doivent être **compatibles SSR** (pas d'accès `window`/DOM hors `onMounted`, pas d'appel API — tout vient du snapshot).
- Rendu public : **statique pré‑généré à la publication** (cf. § 3.5 et décision D2).

---

## 5. Rôles et droits

| Rôle | Droits clés |
|---|---|
| Étudiant | CRUD de ses traces, rattachements, auto‑évaluations, portfolios, CV ; publication ; réponse aux commentaires. |
| Enseignant correcteur (`ROLE_PORTFOLIO_CORRECTOR`) | Voir les traces/portfolios des étudiants affectés, évaluer, commenter, demander une révision. |
| Référent portfolio (`ROLE_REFERENT_PORTFOLIO`) | Gérer grilles, campagnes, affectations, modèles de portfolio ; suivi de promo. |
| Responsable publication (`ROLE_PORTFOLIO_PUBLISHER`) | Modération des portfolios publics / indexables, dépublication. |
| Public | Lecture des publications `public` / `lien_secret`. |

---

## 6. Découpage en lots

| Lot | Contenu | Livrable testable |
|---|---|---|
| **0 — Fondations** | Config Doctrine du bundle, enums, traits, voters, routes manifest, menu par rôle, stockage médias | Navigation UniFolio vide mais sécurisée |
| **1 — Traces** | Trace, TraceMedia, TraceRattachement (compétence/niveau/AC), bibliothèque filtrable, éditeur de trace | L'étudiant dépose et rattache ses traces |
| **2 — Grilles** | `ParametrageFormation` (granularité), Grille / Version / Critère / Échelon, éditeur de grille, critère IA optionnel, résolution BUT > diplôme > département, fixtures BUT | Le référent configure une grille BUT |
| **3 — Auto‑évaluation** | Auto‑éval par rattachement + bilan par compétence, réflexion | L'étudiant s'auto‑évalue sur la grille |
| **4 — Évaluation** | Campagnes, affectations, file « à corriger », évaluation selon la granularité de la formation, bilan agrégé, commentaires, écarts, notifications, widgets réels | Cycle complet dépôt → évaluation → retour |
| **5 — Portfolio blocs** | Éditeur de blocs (texte, médias, mise en page), blocs métier, modèles imposés, portfolio académique auto‑créé par campagne, CV | L'étudiant compose son portfolio académique |
| **6 — Vitrine & publication** | Portfolio vitrine, « Copier dans ma vitrine », snapshots, service `portfolio-renderer`, génération statique asynchrone, service selon visibilité, îlots JS, OG/SEO/sitemap, modération, export PDF + ZIP | Portfolio vitrine partageable publiquement |
| **7 — Suivi pluriannuel** | Progression multi‑années, matrice promo, archivage à la clôture, exports | Pilotage référent |
| **8 — Migration v1** | Reprise complète, cf. § 7 | Données historiques reprises, rapport d'écarts vide ou validé |

Chaque lot : migrations Doctrine, tests unitaires entités/services, tests de voters, tests fonctionnels API, tests Vitest des stores et du registre de blocs.

---

## 7. Migration des données UniFolio v1 (D4 : reprise complète)

### 7.1 Principe

- Commande `unifolio:migrate:v1` (même approche que `MigrateIntranetV3StageCommand`) : connexion DBAL **en lecture seule** à la base v1, accès au dossier de fichiers v1.
- **Idempotente et rejouable** : chaque entité migrée porte `oldId` (`OldIdTrait` du cœur) ; une relance met à jour au lieu de dupliquer.
- Étapes séparables (`--step=users|traces|evaluations|portfolios|cv|all`), `--dry-run`, `--annee-universitaire=…`, `--departement=…`.
- **Rapport d'écarts** (CSV + résumé console) : utilisateurs non trouvés, niveaux non résolus, fichiers manquants, contenus invalides. Rien n'est ignoré silencieusement.
- Pré‑requis : les lots 1 à 6 sont livrés (le modèle cible doit exister), les référentiels APC et les scolarités sont déjà présents dans uniServices.

### 7.2 Correspondances

| v1 | v2 | Clé de correspondance / règle |
|---|---|---|
| `Etudiant` | `Etudiant` (cœur) | `username`, sinon `mail_univ` ; non trouvé → rapport |
| `Enseignant` | `Personnel` (cœur) | `username`, sinon `mail_univ` |
| `AnneeUniversitaire`, `Annee`, `Semestre` | `StructureAnneeUniversitaire`, `EtudiantScolarite` | année (`annee`) + étudiant → scolarité de l'étudiant cette année‑là |
| `Competence` / `ApcNiveau` / `ApcApprentissageCritique` | `ApcCompetence` / `ApcNiveau` / `ApcApprentissageCritique` (cœur) | compétence par `code` dans le référentiel du département ; niveau par compétence + `ordre` ; AC par `code` |
| `Bibliotheque` | — (supprimée) | sert uniquement à déterminer étudiant + année de la trace |
| `Trace` (`type_trace` + `contenu`) | `Trace` + n `TraceMedia` | 1 élément de `contenu` → 1 média (`image`, `pdf`, `video`, `lien`) ; fichiers copiés vers le stockage v2 avec contrôle d'existence et de type ; `titre`, `description`→`contexte`, `contexte`, `date_realisation`, `legende`, enums IA repris tels quels |
| `Validation` (trace ↔ `ApcNiveau`) | `TraceRattachement` (cible `niveau`) | un rattachement par validation, `argumentaire` vide |
| `Validation.etat` 1‑3 + `enseignant` | `Evaluation` + `ReponseCritere` | sur une grille **« Héritage UniFolio v1 »** (granularité `niveau`, 1 critère, échelons 1‑3 avec les libellés v1), version verrouillée ; `etat = 0` → pas d'évaluation |
| `Commentaire` (+ parent) | `Commentaire` | ancrage trace ou portfolio, fil reconstitué via `commentaire_parent` |
| `Portfolio` + `Page` + `OrdrePage` + `OrdreTrace` | `Portfolio` **académique** (scolarité de `annee`) + `PortfolioPage` | chaque page v1 → page v2 avec un bloc `heading` (intitulé), un bloc `paragraph` (description) puis un bloc `trace` par trace, dans l'ordre `OrdreTrace` |
| `Portfolio.visibilite` = true | `PortfolioPublication` | snapshot créé à la date de migration ; `opt_search` → `indexable` |
| `Portfolio.banniere` | `Portfolio.banniere` | fichier copié ; bannière par défaut v1 ignorée |
| `Cv`, `Experience`, `Formation` | `Cv`, `CvExperience`, `CvFormation` | reprise directe |
| `Notification` | — | non reprise (éphémère) |
| `Templates` | — | non reprise ; remplacée par les modèles v2 |

### 7.3 Points d'attention

- **Granularité** : les données v1 sont au niveau `ApcNiveau`. Si une formation a choisi `ac` ou `competence` en v2, les évaluations héritées restent affichées « au niveau » avec un badge *Héritage v1* ; elles ne sont pas converties.
- **Années closes** : les portfolios académiques et évaluations des années antérieures à l'année en cours sont migrés **directement en lecture seule**.
- **Pas de vitrine automatique** : l'étudiant peut créer sa vitrine et y copier des pages de ses portfolios académiques migrés.
- **RGPD** : ne migrer que les étudiants encore présents dans uniServices ; les comptes v1 orphelins figurent au rapport, sans import.
- **Recette** : migration à blanc sur une copie de prod, contrôle par échantillonnage avec 2‑3 enseignants, puis bascule et passage de la v1 en lecture seule.

---

## 8. Décisions à prendre

- ~~**D1 — Diplômes non APC**~~ → **Tranché : BUT seulement.** UniFolio ne s'applique qu'aux types de diplôme `apc = true` ; aucun référentiel libre. Le modèle reste ouvert (grille liée à `StructureTypeDiplome`) si d'autres diplômes APC apparaissent.
- ~~**D2 — Rendu public**~~ → **Tranché : rendu statique pré‑généré à la publication**, via un service Node SSR qui réutilise les composants `Render.vue` (un seul code de rendu par bloc). Voir § 3.5 et § 4.
- ~~**D3 — Types de portfolio**~~ → **Tranché : académique + vitrine.** Un portfolio académique par `EtudiantScolarite` (évalué, figé à la clôture) et un portfolio vitrine unique par étudiant (libre, public possible). Voir § 3.5.
- ~~**D4 — Migration v1**~~ → **Tranché : reprise complète** (traces, médias, rattachements, évaluations, commentaires, portfolios, CV). Voir § 7.
- ~~**D5 — Granularité d'évaluation BUT**~~ → **Tranché : au choix de chaque formation** — compétence, niveau ou AC, limités à l'année concernée, via `ParametrageFormation`. Voir § 3.2 et 3.4.
- ~~**D6 — Usage IA**~~ → **Tranché : déclarations conservées + critère de grille optionnel.** Voir § 3.1 et § 3.2.

Toutes les décisions sont tranchées : le plan est prêt pour le lot 0.
