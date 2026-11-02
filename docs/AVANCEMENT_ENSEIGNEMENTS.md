# Avancement partagé des enseignements

Depuis **Intranet → Mes enseignements → Avancement**, cliquer sur le repère d’un groupe pour le mettre à jour :

- **Séquence du plan de cours** : choix facultatif parmi les séquences de cet enseignement et de cette année, avec l’état **En cours** ou **Terminée**.
- **Où j’en suis** : texte libre obligatoire sans séquence (chapitre, exercice, projet, compétence…). Avec une séquence, ce champ devient une **Précision** facultative. Une séquence et son état suffisent à enregistrer.
- **Pour la prochaine séance** : texte libre facultatif.
- Entrée enregistre, Maj + Entrée insère un retour à la ligne. Le bouton Enregistrer est également disponible.
- Le dernier auteur et la date apparaissent sous le repère. Historique affiche les révisions, par lots de 20.

Les groupes sont l’union des groupes des semestres des UE rattachées à l’enseignement, limitée au département actif et à l’année sélectionnée. Un filtre CM / TD / TP / autre apparaît lorsqu’il existe plusieurs types de groupes.

## Données et accès

Le suivi est commun aux enseignants pour un même **enseignement + groupe + année universitaire**. Chaque enregistrement crée une révision immuable dans `intranet_avancement_enseignement`. Les autres années ne sont ni mélangées ni recopiées.

Une révision peut aussi contenir une copie de la séquence choisie (`id`, `ordre`, `programme`) et son état (`en_cours` / `terminee`). Cette copie est produite par le serveur après vérification de l’enseignement et de l’année du plan. Elle est indépendante des modifications et suppressions futures du plan. Les anciens repères libres restent inchangés. Si une ancienne séquence n’existe plus, son intitulé est proposé en repère libre lors de l’édition ; les révisions passées ne sont jamais réécrites.

Le choix d’une séquence ne termine pas les précédentes et n’impose aucun ordre de progression aux groupes. Une nouvelle séquence est proposée « En cours » par défaut. Les commentaires de la séquence dans le plan ne sont pas utilisés comme commentaires d’avancement du groupe.

Le serveur exige un compte Personnel, le rôle réel `ROLE_TEACHER`, Intranet activé dans le département actif, ainsi qu’un prévisionnel de l’enseignant pour cet enseignement et cette année. Le groupe doit appartenir à un des semestres autorisés des UE de l’enseignement. Le menu de simulation des rôles ne change pas ces droits réels. L’auteur provient toujours de l’utilisateur authentifié.

Le client transmet l’identifiant de la dernière révision affichée (`versionId`, 0 à la première saisie). Un verrou transactionnel sur l’enseignement protège aussi les premières saisies simultanées. Si un collègue a enregistré entre-temps, l’API retourne 409 : le brouillon reste affiché et une comparaison avec le repère actuel est proposée avant un nouvel enregistrement. Une saisie identique ne crée pas de nouvelle révision.

Aucune modification du prévisionnel, des progressions EDT existantes ou du calendrier n’est effectuée par ce suivi.

## Installation

La migration `DoctrineMigrations\Version20261102120000` crée uniquement la table du suivi et ses index/clés étrangères. Elle doit être incluse dans Git (exception dédiée dans `back/.gitignore`) et appliquée sur les autres environnements, puis complétée par `DoctrineMigrations\Version20261102140000` pour les séquences facultatives :

```sh
cd back
php bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20261102120000' --up --no-interaction
php bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20261102140000' --up --no-interaction
```

## Architecture API Platform

Les trois opérations sont déclarées sur l’`ApiResource` de `AvancementEnseignement`. Le contrôleur dédié a été supprimé.

- `AvancementEnseignementProvider` charge les groupes, derniers repères et séquences via le service métier.
- `AvancementEnseignementHistoriqueProvider` charge les révisions paginées.
- `AvancementEnseignementProcessor` reçoit un `AvancementEnseignementSaisieDto` validé par API Platform et transmet la saisie au service métier transactionnel.
- Les DTO de sortie explicitent les réponses liste, historique et révision. Les entités Doctrine ne sont pas modifiables directement depuis les requêtes.
- `AvancementEnseignementContexte` centralise l’utilisateur authentifié et les paramètres d’enseignement, d’année, de groupe et de curseur communs aux Providers et au Processor.

Le service JavaScript propose une seule lecture `getAvancementsEnseignementService(params, scope = '', showToast = false)`, vers `/api${scope}/avancement_enseignements`. Le scope vide sélectionne le `GetCollection` de suivi, `/historique` sélectionne celui de l’historique. Le filtre `AvancementEnseignementFilter`, déclaré par `QueryParameter`, applique `enseignement`, `anneeUniversitaire`, `groupe` et `avant` aux requêtes de révisions. Les Providers personnalisés le réutilisent explicitement via le service métier, puisqu’ils ne passent pas par les extensions ORM du `CollectionProvider`.

Le service JavaScript utilise `apiCall`, avec les mêmes messages, gestion d’erreur et option `showToast` que les services voisins. Les erreurs Axios sont retransmises pour traiter notamment les conflits 409. Les erreurs de validation API Platform (422) sont affichées sans perdre le brouillon.

## API

- `GET /api/avancement_enseignements?enseignement={id}&anneeUniversitaire={id}` : groupes, derniers repères et séquences disponibles.
- `POST /api/avancement_enseignements?enseignement={id}&groupe={id}&anneeUniversitaire={id}` : corps JSON `{ "repere": "…", "prochaineEtape": "…", "versionId": 0 }`.
- `GET /api/historique/avancement_enseignements?enseignement={id}&groupe={id}&anneeUniversitaire={id}&avant={revisionId}` : historique paginé ; `avant` est facultatif et la réponse fournit le prochain curseur.

Pour une saisie liée à une séquence, ajouter `"sequenceId": 123, "etatSequence": "en_cours"` (ou `"terminee"`) au corps JSON. `repere` peut alors être vide ou omis. Pour un repère libre, `sequenceId` et `etatSequence` sont nuls ou omis. Les instantanés de séquence et l’auteur ne peuvent pas être imposés par le client.

Les champs libres sont limités à 2 000 caractères chacun. Sans année explicite, l’année active est utilisée. Les réponses ne sont pas mises en cache. L’accès public général à l’API pour le développement n’est pas modifié.
