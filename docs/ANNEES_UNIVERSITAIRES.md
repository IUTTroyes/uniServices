# Gestion des années universitaires

## Principe

L’année universitaire sélectionnée est le contexte de travail par défaut. Elle est transmise automatiquement par le client, puis contrôlée et appliquée par le serveur sur les opérations annuelles.

Ce contexte ne constitue **jamais une autorisation**. Les voters, les permissions départementales, les contrôles de propriétaire et les extensions de sécurité continuent de s’appliquer.

Trois notions sont distinctes :

| Notion | Rôle |
| --- | --- |
| Année sélectionnée | Choix de travail dans l’interface, y compris une année passée |
| Année active | Année courante définie par `StructureAnneeUniversitaire.actif` ; sert notamment à initialiser la sélection |
| Année d’une ressource | Année déjà portée par un semestre, une inscription, une évaluation, une campagne, etc. |

Le backend ne remplace pas une année manquante par l’année active. Une opération annuelle sans contexte exploitable est refusée.

## 1. Transport automatique côté frontend

Toutes les requêtes utilisant l’instance Axios de `shared/helpers/axios.js` passent par `shared/helpers/contexteAnnuel.js`.

Par défaut :

```js
api.get('/api/structure_semestres');
```

L’intercepteur capture l’année sélectionnée et envoie :

```http
X-Annee-Universitaire: 12
X-Contexte-Annuel: selection
```

Il n’est plus nécessaire d’ajouter l’identifiant dans chaque composant ou service. Cette règle vaut pour GET, POST, PATCH, PUT et DELETE. Elle ne s’applique pas aux appels utilisant directement une autre instance Axios, `fetch` ou un client tiers : ces clients doivent fournir leur contexte lorsqu’ils appellent une opération annuelle.

Le contexte est propre à chaque requête, et conservé à l’identique lors d’une relance après rafraîchissement du jeton d’authentification.

### Options explicites

| Option Axios | Effet |
| --- | --- |
| Option absente ou `contexteAnnuel: 'selection'` | Utilise la sélection ; un paramètre annuel explicite existant devient prioritaire |
| `contexteAnnuel: 12` | Année cible explicite, indépendante de la sélection |
| `contexteAnnuel: 'toutes'` | Demande une consultation sur plusieurs années, uniquement si l’opération l’autorise |
| `contexteAnnuel: 'aucun'` | Ne transmet pas d’année ; utilisable sur une opération indépendante |
| `contexteAnnuel: 'ressource'` | Laisse le serveur déduire l’année d’une ressource ou d’un parent explicitement identifié |

Exemples :

```js
// Liste annuelle ordinaire.
api.get('/api/structure_semestres');

// Préparer ou consulter une autre année.
api.get('/api/structure_pns', { contexteAnnuel: 12 });

// Catalogue complet permettant la réouverture d’un diplôme.
api.get('/api/structure_diplomes', { contexteAnnuel: 'toutes' });

// Paramétrage indépendant de l’année.
api.get('/api/salles', { contexteAnnuel: 'aucun' });

// Le semestre identifie déjà sa maquette annuelle.
api.get('/api/structure_semestres/45', { contexteAnnuel: 'ressource' });
```

Un booléen n’est pas accepté à la place de cette option : les intentions doivent rester explicites.

### Compatibilité avec les services existants

Les paramètres `anneeUniversitaire`, `anneeUniversitaireId`, `anneeUnivId` et `annee_universitaire`, présents dans les paramètres de requête ou le corps, sont reconnus. Les IRI `/api/structure_annee_universitaires/{id}` sont également reconnues.

L’intercepteur détecte ces valeurs et marque le contexte comme explicite. Des valeurs contradictoires provoquent une erreur avant l’envoi. Le backend effectue lui aussi ses propres contrôles.

Les identifiants d’année universitaire sont des **ID de ressource**, et non le numéro calendaire 2026 ou un libellé « 2026–2027 ». La valeur historique `courante` reste acceptée explicitement côté serveur pour compatibilité ; elle n’est plus générée comme repli automatique par le service de semestres.

Deux services exposent également l’exception comme dernier argument, pour préserver leurs signatures existantes :

```js
getDiplomesService({}, '/pn-light', false, 'toutes');
getEtudiantScolaritesService({ etudiant: 42 }, '', false, 'toutes');
```

## 2. Sélection et changement d’année

`useAnneeUnivStore.setSelectedAnneeUniv()` est le point d’entrée pour changer la sélection. Il :

1. Actualise le contexte mémoire de l’onglet et sa génération.
2. Vide les listes de diplômes, années de formation et semestres ainsi que les sélections dépendantes dans les stores concernés.
3. Persiste le choix dans `localStorage` et actualise le store.

`localStorage` sert à restaurer le choix au démarrage. Une sélection supprimée du catalogue n’est plus restaurée comme si elle était encore valide. Le démarrage peut sélectionner l’année active disponible.

La barre supérieure recharge actuellement la page après le changement d’année. En complément, les réponses et erreurs tardives des requêtes automatiques d’un ancien contexte sont rejetées avec `ERR_CANCELED`. Elles ne doivent pas écraser les données de la nouvelle sélection. `apiCall` n’affiche pas de toast pour ces annulations.

Les requêtes portant sur une année explicitement choisie restent stables. Les réponses signalées indépendantes ou transversales par le serveur ne sont pas invalidées par un simple changement de sélection.

Pour une nouvelle interface qui change l’année sans recharger la page, les états locaux des composants doivent aussi être réinitialisés et les données rechargées. Il ne faut pas modifier directement `localStorage` pour changer l’année de travail.

## 3. Politiques backend

### Ressources API Platform

Les règles sont centralisées dans [`back/src/AnneeUniversitaire/PerimetreAnnuelRegistry.php`](../back/src/AnneeUniversitaire/PerimetreAnnuelRegistry.php). Deux réglages distincts déterminent le comportement d’une ressource ORM :

| Emplacement dans le registre | Rôle | Cas du PN |
| --- | --- | --- |
| Constante `PATHS` | Déclare qu’une ressource est annuelle et indique le chemin ORM vers son année universitaire. | `'StructurePn' => 'anneeUniversitaire'` impose un contexte annuel sur les collections ordinaires. |
| Méthode `autoriseToutes()` | Autorise exceptionnellement une consultation sur toutes les années avec le mode frontend `'toutes'`. | `StructurePn` n’est actuellement pas dans cette liste : ce mode est refusé. |

Pour permettre une consultation de tous les PN, il faut ajouter `StructurePn` à la liste de `autoriseToutes()`, **sans retirer son entrée de `PATHS`**. Les appels ordinaires resteront ainsi limités à l’année sélectionnée ; seule une demande explicite `'toutes'` pourra lever cette restriction annuelle. Les permissions restent inchangées.

La liste `INDEPENDANTS` désigne les ressources sans rattachement annuel. Les politiques particulières des DTO sont déclarées dans `DTO_MODES`. Les contrôleurs Symfony déclarent leur politique avec l’attribut `ContexteAnnuel`, décrit plus bas.

`PATHS` déclare notamment les chemins suivants :

| Ressource | Chemin vers l’année universitaire |
| --- | --- |
| Liste des étudiants inscrits | `scolarites.anneeUniversitaire` |
| PN | `anneeUniversitaire` |
| Année de formation | `pn.anneeUniversitaire` |
| Semestre | `annee.pn.anneeUniversitaire` |
| UE | `semestre.annee.pn.anneeUniversitaire` |
| Groupe | `semestres.annee.pn.anneeUniversitaire` |
| Diplôme proposé | `pns.anneeUniversitaire` |
| Enseignement | `enseignementUes.ue.semestre.annee.pn.anneeUniversitaire` |
| Inscription semestrielle | `scolarite.anneeUniversitaire` |
| Note | `scolariteSemestre.scolarite.anneeUniversitaire` |
| Stage étudiant | `stagePeriode.anneeUniversitaire` |

`AnnualQueryExtension` ajoute automatiquement le périmètre aux collections ORM d’API Platform. Les jointures et le `DISTINCT` sont construits depuis ces chemins déclarés. Il ne s’agit pas d’un filtre Doctrine global : les commandes, migrations, requêtes SQL et repositories utilisés hors API Platform ne sont pas modifiés implicitement.

La liste des étudiants inscrits est annuelle, mais une identité étudiante consultée par ID est indépendante. La recherche `sansScolarite=true` est une exception explicite : elle recherche les identités sans aucun dossier, quelle que soit l’année.

Une lecture d’élément identifié s’appuie sur l’année de cet élément. Une année explicitement contradictoire est refusée ; le simple choix de l’interface ne transforme pas une ressource historique en une autre ressource.

Les DTO de questionnaires ont leurs politiques propres : réponse à une invitation identifiée par un jeton, publication ou analyse d’un questionnaire identifié, liste transversale des invitations personnelles, duplication vers une année cible. Les invitations publiques n’exigent pas de sélection préalable dans l’interface.

### Contrôleurs Symfony

Chaque contrôleur métier doit déclarer sa politique, sur la classe ou la méthode. La déclaration de méthode est prioritaire :

```php
use App\AnneeUniversitaire\ContexteAnnuel;

#[ContexteAnnuel('annuel', 'Liste des formations de l’année de travail.')]
final class MonControleur
{
    // ...
}
```

| Politique | Signification |
| --- | --- |
| `annuel` | Année obligatoire ; le contrôleur exploite le contexte normalisé |
| `independant` | Identité, configuration ou donnée partagée sans année scolaire |
| `transversal` | Cursus, historique, catalogue ou copie interannuelle intentionnels |
| `ressource` | L’objet ou le parent explicitement identifié définit le périmètre |

Une nouvelle opération métier sans politique provoque une erreur de configuration. Les contrôleurs techniques tiers, notamment authentification et documentation, ne sont pas assimilés à des opérations métier du projet.

Pour une ressource identifiée :

```php
#[ContexteAnnuel(
    'ressource',
    'Le semestre porte déjà son année.',
    \App\Entity\Structure\StructureSemestre::class,
    'semestreId',
)]
```

`AnnualRequestSubscriber` résout le contexte avant l’exécution des opérations et renseigne l’attribut de requête `_annee_universitaire_id`. Sur les opérations annuelles, il normalise également `anneeUniversitaire` dans les paramètres de requête destinés aux traitements existants.

### Repositories et requêtes personnalisées

La déclaration `annuel` impose la présence du contexte, **elle n’ajoute pas automatiquement un WHERE à une requête personnalisée**. Le contrôleur ou le service doit utiliser le résolveur :

```php
$yearId = $contexte->requireId();

// Critère direct.
$items = $repository->findBy(['anneeUniversitaire' => $yearId]);

// Chemin annuel connu du registre.
$qb = $repository->createQueryBuilder('s');
$contexte->apply($qb, StructureSemestre::class, 's');
$items = $qb->getQuery()->getResult();
```

Les contrôleurs annuels existants de destinataires de messages, plans de cours, génération EDT, imports et création de scolarités, synchronisation de formation, administration des campagnes et dashboard métier ont été adaptés au contexte. Les vues globales, les ressources désignées par leur ID/UUID et les parcours transversaux conservent leurs politiques explicites.

Les services exécutés hors HTTP doivent recevoir une année explicitement. Appeler `requireId()` sans requête est une erreur ; une tâche planifiée ne doit jamais dépendre du dernier choix d’un utilisateur.

## 4. Résolution et contrôles

L’ordre de résolution est :

1. Année explicitement fournie dans les paramètres ou le corps.
2. Contexte numérique Axios explicitement demandé.
3. Année déduite d’un parent précis : semestre, année de formation, PN, inscription, évaluation ou UE.
4. Année capturée automatiquement dans l’en-tête.

Des années incompatibles entre les références sont refusées. Une ressource associée à plusieurs années, par exemple un diplôme, ne permet pas toujours de déduire un contexte unique ; il faut alors une sélection ou une année explicite.

`AnnualWriteListener` vérifie les écritures ORM HTTP dans un périmètre annuel, y compris les écritures de collections. Une relation directe à l’année manquante peut être renseignée depuis le contexte ; une relation déjà renseignée avec une autre année n’est pas écrasée silencieusement. Les changements de rattachement entre années sont refusés dans ce périmètre.

Les opérations indépendantes et transversales relèvent de leurs contrôles métier propres ; elles ne deviennent pas des opérations annuelles du seul fait qu’un en-tête a été envoyé. Les modèles de questionnaire restent des ressources partagées.

| Situation | Réponse |
| --- | --- |
| Année requise absente, mal formée ou inconnue | HTTP 400 |
| Ressource et année explicite contradictoires | HTTP 409 |
| Écriture hors contexte | HTTP 409 |
| Ressource annuelle sans rattachement exploitable | HTTP 422 |
| `toutes` sur une opération non autorisée | HTTP 400 |
| Politique oubliée sur une nouvelle opération métier | Erreur de configuration |

## 5. Exceptions explicites existantes

- Liste/configuration des années universitaires et création d’une nouvelle année.
- Catalogue complet de diplômes dans les formulaires de préparation d’année.
- Historique des scolarités d’un étudiant et suivi de cursus Unifolio.
- Informations de sécurité et scolarité active nécessaires à l’authentification d’un étudiant, indépendamment d’une sélection de consultation.
- Identités, personnels, salles, départements, références APC, types, configuration et données administratives sans année universitaire.
- Messagerie personnelle, notifications, CV, publications et historiques explicitement consultés : leur périmètre peut traverser plusieurs années.
- Copie d’un plan de cours entre deux années et comparaison de campagnes de questionnaire.
- Ressources annuelles dont l’ID, l’UUID ou le jeton détermine déjà l’année.

Le mode frontend `toutes` n’est autorisé en collection que pour les ressources déclarées dans `autoriseToutes()` : identités étudiantes, diplômes, scolarités, inscriptions semestrielles, portfolios, traces et campagnes. Il est réservé aux lectures et ne contourne jamais les contrôles d’accès existants.

## 6. Caches, CORS et audit

Les réponses déclarent `Vary: X-Annee-Universitaire, X-Contexte-Annuel`. Quand une année précise a été résolue, elle est renvoyée dans `X-Annee-Universitaire`. `X-Contexte-Annuel` indique la politique effectivement appliquée. Ces en-têtes sont autorisés et exposés par la configuration CORS.

Après ajout d’une ressource ou d’un contrôleur :

```sh
cd back
php bin/console app:annees-universitaires:audit
```

Cette commande vérifie les politiques HTTP et la validité des chemins ORM sans lire les données métier. Elle ne prouve pas qu’un WHERE a été ajouté à chaque requête personnalisée : les requêtes annuelles écrites hors API Platform doivent être vérifiées comme telles.

Aucune modification du schéma de base de données n’est nécessaire pour le mécanisme de contexte.

## 7. Exemples complets : de la vue au filtre backend

Les exemples utilisent un diplôme d’ID **7**, une année sélectionnée d’ID **12**, une autre année d’ID **11** et un PN d’ID **45** rattaché à l’année 11. Ces nombres sont des identifiants fictifs.

**État des services actuels :** `getPns(params, showToast)` permet déjà les cas « sélection » et « année explicite dans les paramètres ». Il n’expose pas encore d’argument `contexteAnnuel`. Il ne faut donc pas inventer un troisième argument sur cette fonction, ni placer l’option Axios dans `params`.

Les extraits intitulés « service d’exemple à ajouter » ci-dessous sont des modèles de code : ils ne désignent pas des fonctions déjà présentes dans le dépôt. Ils montrent comment exposer proprement une option Axios dans un nouveau service.

### 7.1 Année sélectionnée automatiquement : liste des PN d’un diplôme

#### Vue Vue : aucun identifiant d’année à passer

```vue
<script setup>
import { onMounted, ref } from 'vue';
import { getPns } from '@requests';

const props = defineProps({ diplomeId: { type: Number, required: true } });
const pns = ref([]);

onMounted(async () => {
  pns.value = await getPns({ diplome: props.diplomeId }, false);
});
</script>
```

#### Service existant : `shared/requests/structure_services/pnService.js`

Son appel Axios est, sous forme abrégée :

```js
const getPns = async (params, showToast = false) => {
  const response = await apiCall(
    api.get,
    ['/api/structure_pns', { params }],
    'PNs récupérés avec succès',
    'Erreur lors de la récupération des PNs',
    showToast,
  );
  return response.member;
};
```

L’intercepteur ajoute le contexte. La requête réseau est équivalente à :

```http
GET /api/structure_pns?diplome=7
X-Annee-Universitaire: 12
X-Contexte-Annuel: selection
```

#### Ressource API Platform : `StructurePn`

Le projet utilise `QueryParameter(filter: ...)`, et non un attribut `#[ApiFilter(...)]` pour ces paramètres :

```php
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['pn:detail']]),
        new GetCollection(normalizationContext: ['groups' => ['pn:detail']]),
        new Delete(security: "is_granted('CAN_DELETE_PN', object)"),
    ],
    parameters: [
        'diplome' => new QueryParameter(filter: PnFilter::class),
        'anneeUniversitaire' => new QueryParameter(filter: PnFilter::class),
    ],
)]
```

#### Résolution et filtre

1. `AnnualRequestSubscriber` reconnaît une collection annuelle de PN.
2. `AnneeUniversitaireContext` valide l’année 12 et le subscriber normalise le paramètre `anneeUniversitaire` pour les filtres existants.
3. `AnnualQueryExtension` applique le chemin déclaré `StructurePn → anneeUniversitaire`, indépendamment de l’oubli éventuel d’un filtre dans la vue.
4. `PnFilter` applique le diplôme 7 et le paramètre annuel normalisé.

La partie utile de `PnFilter` est :

```php
if ('diplome' === $property) {
    $queryBuilder
        ->join(sprintf('%s.diplome', $alias), 'diplome')
        ->andWhere('diplome.id = :diplome')
        ->setParameter('diplome', $value);
}

if ('anneeUniversitaire' === $property) {
    $queryBuilder
        ->join("$alias.anneeUniversitaire", 'anneeUniversitaire')
        ->andWhere('anneeUniversitaire.id = :anneeUniversitaire')
        ->setParameter('anneeUniversitaire', $value);
}
```

Le résultat est limité au diplôme 7 **et** à l’année 12. L’extension et le filtre peuvent appliquer une restriction annuelle équivalente : le filtre reste compatible avec les paramètres existants, tandis que l’extension fournit la garantie centrale sur les collections annuelles.

### 7.2 Autre année explicite : consulter les PN de l’année 11

#### Vue avec le service existant

```js
pns.value = await getPns({
  diplome: props.diplomeId,
  anneeUniversitaire: 11,
}, false);
```

Le même service transmet les paramètres ; Axios détecte l’année explicite :

```http
GET /api/structure_pns?diplome=7&anneeUniversitaire=11
X-Annee-Universitaire: 11
X-Contexte-Annuel: explicite
```

La sélection globale reste 12. Le backend normalise l’année 11, puis `AnnualQueryExtension` et `PnFilter` appliquent l’année **11**, sans ajouter de condition imposant l’année active ou sélectionnée 12.

#### Variante : exposer l’option Axios dans le service

Si l’on souhaite écrire `contexteAnnuel: 11` sans utiliser un paramètre annuel métier, voici une **évolution possible du service, non appliquée dans cet exemple documentaire** :

```js
const getPns = async (
  params = {},
  showToast = false,
  contexteAnnuel = 'selection',
) => {
  const response = await apiCall(
    api.get,
    ['/api/structure_pns', { params, contexteAnnuel }],
    'PNs récupérés avec succès',
    'Erreur lors de la récupération des PNs',
    showToast,
  );
  return response.member;
};
```

Après cette évolution seulement, une vue pourrait appeler :

```js
pns.value = await getPns({ diplome: props.diplomeId }, false, 11);
```

La résolution et les restrictions backend seraient les mêmes. L’option `contexteAnnuel` appartient à la configuration Axios ; elle ne devient pas un paramètre HTTP nommé `contexteAnnuel`.

### 7.3 Toutes les années : exception soumise à autorisation

**La collection de PN n’autorise pas actuellement le mode `toutes`.** Une requête vers `/api/structure_pns` avec ce mode reçoit HTTP 400 avant l’application normale des filtres. Omettre `anneeUniversitaire` ne permet pas non plus d’obtenir toutes les années : le contexte sélectionné sera appliqué, ou son absence provoquera une erreur.

Un cas concret déjà autorisé est le catalogue des diplômes lors de la préparation d’une nouvelle année.

#### Vue et service existants

```js
import { getDiplomesService } from '@requests';

const diplomes = await getDiplomesService(
  { pagination: false },
  '/pn-light',
  false,
  'toutes',
);
```

`getDiplomesService` expose déjà son quatrième argument :

```js
const getDiplomesService = async (
  params = {},
  scope = '',
  showToast = false,
  contexteAnnuel = 'selection',
) => {
  // Dans l’appel à apiCall :
  // api.get(`/api${scope}/structure_diplomes`, { params, contexteAnnuel })
};
```

Le réseau reçoit :

```http
GET /api/pn-light/structure_diplomes?pagination=false
X-Contexte-Annuel: toutes
```

#### Backend et filtre

1. `toutesAutorisees(StructureDiplome::class)` vérifie que la ressource est autorisée et que la requête est une lecture.
2. `AnnualQueryExtension` n’ajoute pas de restriction annuelle pour cette consultation.
3. `DiplomeFilter` continue d’appliquer les filtres métier explicitement fournis. Sans paramètre `anneeUniversitaire`, sa branche annuelle n’est pas appliquée.
4. Les contrôles d’accès habituels restent actifs.

Il faut donc **ne pas conserver un filtre `anneeUniversitaire`** si l’on veut effectivement une consultation sans restriction annuelle. Le mode `toutes` ne supprime pas les autres filtres demandés par la vue.

Pour autoriser à l’avenir le même cas sur les PN :

- ajouter explicitement `StructurePn` à `PerimetreAnnuelRegistry.autoriseToutes()` ;
- exposer l’option dans le service, comme dans la variante précédente ;
- réserver l’appel avec `'toutes'` à l’écran qui nécessite cet historique et conserver ses contrôles d’accès ;
- exécuter l’audit et vérifier les résultats sur plusieurs années.

Il s’agit d’une évolution à décider, pas du comportement actuel du PN.

### 7.4 Aucun contexte annuel : une ressource indépendante, par exemple une salle

Un PN reste annuel : **`contexteAnnuel: 'aucun'` sur une collection de PN est refusé avec HTTP 400**. Cette option ne signifie pas « tous les PN ».

Une salle, en revanche, existe indépendamment des maquettes annuelles.

#### Service d’exemple à ajouter pour exposer explicitement l’option

```js
// Exemple documentaire : le getSallesService actuel n’a que params et showToast.
import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';

export const getSallesSansContexteService = async (params = {}) => {
  const response = await apiCall(
    api.get,
    ['/api/salles', { params, contexteAnnuel: 'aucun' }],
    'Salles récupérées',
    'Impossible de charger les salles',
    false,
  );
  return response.member;
};
```

#### Vue

```js
// Après ajout du service d’exemple et de son export dans @requests.
import { getSallesSansContexteService } from '@requests';

const salles = await getSallesSansContexteService();
```

Requête :

```http
GET /api/salles
X-Contexte-Annuel: aucun
```

`Salle` est déclarée indépendante dans le registre. Le subscriber n’exige donc pas d’année et l’extension n’ajoute aucune jointure vers une année universitaire. Les éventuels filtres de salle et les permissions continuent de s’appliquer normalement ; `PnFilter` n’intervient pas.

L’appel existant `getSallesService()` fonctionne également sans modification : il peut transmettre le contexte automatique, mais le backend ignore ce contexte pour une ressource indépendante. L’option `'aucun'` sert ici à rendre l’intention explicite.

### 7.5 Année déterminée par la ressource : lire le PN 45

#### Service d’exemple à ajouter

```js
import api from '@helpers/axios';

export const getPnParRessourceService = async (pnId) => {
  const response = await api.get(`/api/structure_pns/${pnId}`, {
    contexteAnnuel: 'ressource',
  });
  return response.data;
};
```

#### Vue

```vue
<script setup>
import { onMounted, ref } from 'vue';
// Après ajout du service d’exemple et de son export dans @requests.
import { getPnParRessourceService } from '@requests';

const props = defineProps({ pnId: { type: Number, required: true } });
const pn = ref(null);

onMounted(async () => {
  pn.value = await getPnParRessourceService(props.pnId);
});
</script>
```

Requête :

```http
GET /api/structure_pns/45
X-Contexte-Annuel: ressource
```

#### Backend : lecture d’élément, pas filtrage de collection

1. Le subscriber reconnaît l’opération `Get` avec un identifiant dans l’URL.
2. `AnnualQueryExtension.applyToItem()` consulte le rattachement du PN 45 et identifie l’année 11.
3. L’opération lit le PN 45 selon son identifiant et applique ses contrôles d’accès.
4. Le contexte de réponse peut indiquer `X-Annee-Universitaire: 11`.

**`PnFilter` ne s’exécute pas comme filtre de collection sur cette lecture par ID.** L’équivalent de la restriction est d’abord `pn.id = 45`, puis le contrôle de cohérence du contexte explicite s’il existe.

Même si la sélection de l’interface est 12, la ressource reste le PN 45 de l’année 11. En revanche :

```js
api.get('/api/structure_pns/45', { contexteAnnuel: 12 });
```

est refusé avec HTTP 409, car l’année explicitement demandée contredit celle du PN.

### 7.6 Tableau récapitulatif pour le PN

| Intention | Appel côté vue ou service | Traitement backend |
| --- | --- | --- |
| PN du diplôme dans l’année sélectionnée | `getPns({ diplome: 7 })` | Contexte automatique + extension annuelle + `PnFilter` |
| PN du diplôme dans l’année 11 | `getPns({ diplome: 7, anneeUniversitaire: 11 })` | Année explicite 11 + extension annuelle + `PnFilter` |
| Tous les PN, toutes années | Option `'toutes'` dans un service adapté | Actuellement refusé : autorisation explicite du registre à ajouter si nécessaire |
| PN sans contexte et sans année identifiable | Option `'aucun'` | Refus : le PN est une ressource annuelle |
| PN déjà identifié | `GET /structure_pns/45` avec option `'ressource'` | Année déduite de l’élément ; pas de `PnFilter` de collection |

Pour une opération annuelle ordinaire, le seul code à écrire dans la vue reste donc le filtre métier, par exemple `{ diplome: 7 }`. Les variantes de contexte sont des choix explicites de l’écran ou du service qui en a besoin.
