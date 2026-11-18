# Structure de la scolarité dans UniServices

Ce document décrit les entités et les traitements présents dans le dépôt. Il distingue les résultats stockés, les valeurs calculées pour l’affichage et les traitements qui ne sont pas encore raccordés. Les règles de décision décrites sont celles du code actuel, pas une présentation du règlement des études.

## 1. Vue d’ensemble

`Etudiant` porte l’identité de la personne. `EtudiantScolarite` représente son dossier pour une année universitaire et un département. `EtudiantScolariteSemestre` décrit chacun des semestres suivis dans ce dossier.

```mermaid
flowchart TD
    E["Étudiant"] -->|possède plusieurs| SC["Scolarité annuelle"]
    AU["Année universitaire"] --> SC
    DEP["Département"] --> SC
    SC -->|contient plusieurs| SS["Inscription semestrielle"]
    SEM["Semestre de formation"] --> SS
    SS -->|porte plusieurs| N["Notes individuelles"]
    EV["Évaluation"] --> N
    SS --> ME["Moyennes par enseignement"]
    SS --> MU["Moyennes par UE"]
```

Les groupes et absences sont également rattachés à l’inscription semestrielle. Le schéma représente les relations métier ; il ne représente pas le sens de stockage des clés étrangères.

## 2. Le dossier annuel : `EtudiantScolarite`

| Propriété | Rôle dans le modèle |
| --- | --- |
| `etudiant` | Identité de l’étudiant concerné |
| `anneeUniversitaire` | Période universitaire, par exemple 2026–2027 |
| `departement` | Département de rattachement du dossier |
| `scolariteSemestre` | Collection des inscriptions semestrielles |
| `ordre` | Information d’ordre stockée sur le dossier |
| `actif` | État actif du dossier, distinct de l’année universitaire active |
| `public` | Indicateur de publication stocké sur le dossier |
| `moyenne` | Résultat global annuel éventuellement enregistré |
| `nbAbsences` | Compteur d’absences stocké au niveau annuel |
| `decision` | Décision annuelle nullable, sous forme de booléen |
| `proposition` | Année de formation proposée pour la poursuite, via `StructureAnnee` |
| `commentaire` | Commentaire du dossier |
| `packages` | Applications accessibles dans ce contexte |

Le dossier annuel n’a pas de relation directe vers un diplôme ou une année de formation suivie. Ces informations se retrouvent en parcourant les inscriptions semestrielles.

`getAnnee()` renvoie une **liste calculée d’années de formation distinctes** depuis les semestres suivis. Ce n’est pas une colonne en base, et ce n’est pas la propriété `anneeUniversitaire`.

## 3. L’inscription semestrielle : `EtudiantScolariteSemestre`

Cette entité relie le dossier annuel à un `StructureSemestre` précis de la maquette.

| Propriété | Rôle dans le modèle |
| --- | --- |
| `scolarite` | Dossier annuel parent |
| `semestre` | Semestre effectivement suivi |
| `groupes` | Groupes de l’étudiant pour cette inscription, en ManyToMany |
| `note` | Collection des notes rattachées à cette inscription |
| `absence` | Collection des absences rattachées à cette inscription |
| `moyenne` | Moyenne générale semestrielle éventuellement enregistrée |
| `rang` | Champ de rang stocké dans l’entité |
| `decision` | Décision semestrielle nullable, sous forme de booléen |
| `nbAbsences` | Compteur d’absences stocké pour le semestre |
| `proposition` | Semestre proposé pour la poursuite, distinct du semestre suivi |
| `legacyContext` | Informations de contexte historique issues de V3 |

Le chemin vers la structure de formation est :

```php
$scolarite = $inscription->getScolarite();
$anneeUniversitaire = $scolarite?->getAnneeUniversitaire();
$etudiant = $scolarite?->getEtudiant();

$pn = $inscription->getSemestre()?->getAnnee()?->getPn();
$diplome = $pn?->getDiplome();
```

L’année universitaire du dossier et celle du PN doivent être cohérentes. L’existence de ces relations ne signifie pas, à elle seule, qu’une contrainte de cohérence croisée est appliquée automatiquement par Doctrine.

Pour les groupes, `SynchroSemestrePreviousProcessor` permet une reprise depuis le semestre précédent de la même année de formation. Il rapproche les étudiants et les groupes selon leur type et leur ordre ; ce traitement ne constitue pas une validation de passage au semestre suivant.

## 4. Évaluations et notes

### Une évaluation, plusieurs résultats individuels

`ScolEvaluation` décrit une épreuve : enseignement, semestre, année universitaire, coefficient `coeff`, date, type d’évaluation, type de groupe, personnels autorisés, visibilité, état et statistiques.

`EtudiantNote` représente le résultat individuel à cette évaluation. Elle référence :

- `evaluation` : l’épreuve concernée ;
- `scolariteSemestre` : l’inscription semestrielle concernée ;
- `scolarite` : un rattachement direct supplémentaire au dossier annuel.

Le lien `scolariteSemestre` est nullable dans le mapping actuel. Il ne faut donc pas supposer que toutes les notes historiques permettent de remonter à un semestre. Le lien direct `scolarite` existe en parallèle : la cohérence des deux rattachements doit être préservée par les traitements d’écriture.

La note contient aussi un commentaire, un statut de présence, un historique, un indicateur `publiee`, des dates techniques et un UUID. Le dossier annuel `public`, l’évaluation `visible` et la note `publiee` sont des indicateurs distincts ; ils ne doivent pas être confondus.

### Statuts de présence

Le résultat de `EtudiantNote.getNote()` dépend du statut :

| `presenceStatut` | Valeur renvoyée par `getNote()` |
| --- | --- |
| `present` | Valeur numérique stockée, éventuellement `null` |
| `absent_justifie` | Sentinelle `-0.01` |
| `dispense` | Sentinelle `-0.01` |
| `absent_injustifie` | `0.0` |

Ces valeurs décrivent le comportement du getter. Elles ne définissent pas à elles seules une politique complète de calcul des moyennes. Le setter accepte actuellement l’intervalle `[-0.01, 20]`, bien que son message d’erreur mentionne 0 à 20.

### Ce qui se passe lors d’une saisie

Les opérations de création et de modification des notes utilisent `EtudiantNotePersistProcessor` :

1. Persistance de la note.
2. Réévaluation de l’état de l’évaluation, sauf maintien d’un état manuel.
3. Mise à jour des statistiques de cette évaluation.

Les statistiques comprennent moyenne, médiane, minimum, maximum et nombres d’absences justifiées, injustifiées et de dispenses. Les trois statuts d’absence/dispense sont exclus des valeurs numériques de ces statistiques, y compris l’absence injustifiée pour laquelle le getter renvoie 0. Lorsqu’aucune valeur n’est retenue, les statistiques numériques sont enregistrées à 0.

**Ce processor ne recalcule pas les moyennes par enseignement, par UE, semestrielles ou annuelles.** Les statistiques d’une évaluation et les moyennes de scolarité sont deux niveaux différents.

### Rattrapages

`ScolEvaluationRattrapage` relie une évaluation à un étudiant, avec personnel, salle, date, horaires et état. Cette entité organise le rattrapage ; elle ne porte pas une note de remplacement dédiée et ne définit pas à elle seule la règle de substitution du résultat.

## 5. Où sont stockées les moyennes ?

| Niveau | Stockage | Rattachement |
| --- | --- | --- |
| Évaluation | `ScolEvaluation.stats` | Statistiques collectives des notes de l’épreuve |
| Enseignement | `MoyenneEnseignement.value` | Inscription semestrielle + enseignement + personnel |
| UE | `MoyenneUe.value` | Inscription semestrielle + UE + personnel |
| Semestre | `EtudiantScolariteSemestre.moyenne` | Inscription semestrielle |
| Année universitaire | `EtudiantScolarite.moyenne` | Dossier annuel |

Les deux entités `MoyenneEnseignement` et `MoyenneUe` appartiennent au bundle Intranet. Leur propriété `etudiantScolariteSemestre` référence bien une inscription semestrielle, malgré le nom de leur accesseur `getEtudiantScolarite()`.

La moyenne d’enseignement est stockée indépendamment de la moyenne d’UE. Le lien pédagogique `ScolEnseignementUe` porte le coefficient et les ECTS de contribution de l’enseignement à une UE ; une même moyenne d’enseignement peut être affichée dans plusieurs UE auxquelles il contribue.

Dans les traitements examinés, aucun service métier ne raccorde automatiquement toute la chaîne **notes → moyennes d’enseignement → moyennes d’UE → moyenne semestrielle → moyenne annuelle**. Les entités de moyennes disposent d’opérations API d’écriture, mais leur présence ne prouve pas qu’un calcul depuis les notes est implémenté. Les formules manquantes ne doivent pas être déduites de celles de la commission.

## 6. La sous-commission : une vue calculée par semestre

La page `SousCommissionView.vue` charge les semestres de l’année de formation sélectionnée, puis appelle :

```text
GET /api/intranet/commissions/semestre/{semestreId}
```

L’accès exige un utilisateur authentifié et soit `CAN_EDIT_SEMESTRE` sur le semestre, soit `ROLE_SCOLARITE` dans son département.

### Chaîne de traitement

| Composant | Responsabilité |
| --- | --- |
| `CommissionController` | Résout le semestre, vérifie les droits et renvoie le JSON |
| `CommissionService` | Charge le jeu de données et sélectionne la stratégie |
| `CommissionDataLoader` | Charge les inscriptions et les moyennes déjà persistées |
| `CommissionStrategyResolver` | Choisit le mode selon le caractère APC du type de diplôme |
| `ClassiqueCommissionStrategy` / `ApcCommissionStrategy` | Construisent les colonnes et lignes de résultat |
| `ClassiqueDecisionRule` / `ApcDecisionRule` | Produisent une décision indicative et son libellé |
| `CommissionView`, `CommissionRow`, `CommissionColumn` | DTO de sortie ; ce ne sont pas des entités persistées |

Les repositories indexent les moyennes sous la forme `[inscriptionSemestrielleId][ueId]` ou `[inscriptionSemestrielleId][enseignementId]`. Le chargement des inscriptions est effectué sur le semestre demandé, sans filtre supplémentaire sur `EtudiantScolarite.actif`.

### Moyenne générale affichée

La commission utilise en priorité `EtudiantScolariteSemestre.moyenne` si elle est renseignée. Sinon, elle calcule une moyenne pondérée à partir des moyennes d’UE disponibles :

```text
moyenne générale = somme(moyenne UE × poids UE) / somme(poids UE retenus)
```

| Mode | Poids utilisé |
| --- | --- |
| Classique | `StructureUe.coefficient` |
| APC | `StructureUe.nbEcts` |

Le résultat de secours est arrondi à trois décimales. Les UE inactives sont exclues ; les moyennes d’UE absentes sont ignorées pour ce calcul et son dénominateur. Si le poids total retenu n’est pas positif, la moyenne est `null`.

Cette moyenne affichée n’est pas automatiquement écrite dans `EtudiantScolariteSemestre.moyenne`. Une moyenne semestrielle déjà enregistrée peut donc différer du résultat recalculé depuis les UE.

### Décisions actuellement codées

| Mode | Conditions et libellés |
| --- | --- |
| Classique | Moyenne générale absente : « Incomplet ». Sinon validation à partir de 10/20. Les UE à au moins 10 sont comptées à titre informatif. |
| APC | Une moyenne d’UE manque ou aucune UE n’est disponible : « Incomplet ». Toutes les UE à au moins 10 : « Semestre validé ». Sinon moyenne générale à au moins 10 et aucune UE sous 8 : « Validé par compensation ». Sinon « Non validé ». |

La règle APC est explicitement présentée dans le code comme **indicative et à adapter au règlement local**. Le mode classique peut produire une décision malgré des moyennes d’UE manquantes, puisqu’il se fonde sur la moyenne générale disponible.

### Rangs, statistiques et restitution

Le rang affiché est recalculé sur les lignes ayant une moyenne, par ordre décroissant. Les ex æquo partagent le rang : par exemple 1, 1, 3. Il est indépendant du champ `rang` stocké sur l’inscription.

La vue fournit effectif, moyenne de promotion, minimum, maximum, nombre de validations et taux de réussite. Le taux utilise toutes les lignes comme dénominateur, y compris celles dont la décision est indéterminée. Les lignes sont ensuite triées par nom et prénom.

L’interface propose des tableaux classiques ou APC, une présentation par UE ou par enseignement en APC, des filtres et un export CSV.

**La sous-commission actuelle est une consultation avec calculs de présentation.** Le traitement ne persiste ni les moyennes calculées, ni les rangs, ni les décisions ; il n’enregistre pas de délibération de jury et ne modifie pas les propositions de poursuite ou la publication.

## 7. Décision enregistrée et proposition de poursuite

| Niveau | Décision stockée | Proposition stockée |
| --- | --- | --- |
| Dossier annuel | `EtudiantScolarite.decision` | `StructureAnnee` |
| Inscription semestrielle | `EtudiantScolariteSemestre.decision` | `StructureSemestre` |
| Vue de commission | Décision calculée dans `CommissionRow` | Pas de proposition générée dans ce traitement |

Une décision stockée est un booléen nullable ; elle ne porte pas tout le détail d’une délibération. Les décisions affichées en commission sont recalculées par les règles et ne lisent pas simplement le champ `decision` déjà enregistré.

Une proposition désigne une destination envisagée. La renseigner ne crée pas automatiquement un nouveau dossier annuel ou une inscription au semestre proposé.

## 8. Absences et historique

`EtudiantAbsence` se rattache à l’inscription semestrielle et peut référencer un événement EDT. Les compteurs `nbAbsences` annuels et semestriels sont des valeurs stockées : leur existence ne garantit pas une actualisation automatique à chaque absence.

L’historique se lit en partant des dossiers annuels de l’étudiant, puis de leurs inscriptions et des semestres de leurs PN. `legacyContext` et les rattachements directs conservés sur les notes permettent de représenter des situations issues de V3 qui ne correspondent pas toujours à une maquette native complète.

## 9. Points de vigilance repérés dans le code

Ces constats décrivent l’état actuel ; ce document ne modifie pas les traitements.

- `ScolariteDetailsMigrator` appelle `setRang()`, `setMoyennesMatiere()` et `setMoyennesUe()` sur l’inscription semestrielle. Ces méthodes ne sont pas présentes dans l’entité examinée. Le champ `rang` existe, mais n’a pas d’accesseur correspondant. La reprise des détails V3 doit être alignée avec le stockage actuel des moyennes.
- `MoyenneUe` et `MoyenneEnseignement` n’ont pas de contrainte unique explicite pour le couple inscription/UE ou inscription/enseignement. Le repository de commission construit un index à une seule valeur par couple : des doublons seraient écrasés dans ce tableau, sans sélection explicite selon le personnel.
- Le nom `getEtudiantScolarite()` des entités de moyennes désigne une inscription semestrielle, pas le dossier annuel.
- Les indicateurs de publication, décisions stockées et résultats calculés appartiennent à des niveaux différents. Ils ne sont pas synchronisés par le simple affichage d’une commission.

## 10. Sources dans le dépôt

- [Dossier annuel](../back/src/Entity/Etudiant/EtudiantScolarite.php) et [inscription semestrielle](../back/src/Entity/Etudiant/EtudiantScolariteSemestre.php)
- [Notes](../back/src/Entity/Etudiant/EtudiantNote.php), [évaluations](../back/src/Entity/Scolarite/ScolEvaluation.php) et [rattrapages](../back/src/Entity/Scolarite/ScolEvaluationRattrapage.php)
- [Processor de saisie des notes](../back/src/State/Processor/Evaluation/EtudiantNotePersistProcessor.php)
- [Moyennes d’UE](../packages/intranet-bundle/src/Entity/Etudiant/MoyenneUe.php) et [moyennes d’enseignement](../packages/intranet-bundle/src/Entity/Etudiant/MoyenneEnseignement.php)
- [Services, stratégies, règles et DTO de commission](../packages/intranet-bundle/src/Commission/)
- [Contrôleur de commission](../packages/intranet-bundle/src/Controller/CommissionController.php) et [page de sous-commission](../packages/intranet-bundle/assets/views/Administration/SousCommission/SousCommissionView.vue)
- [Migration des détails V3](../back/src/Migration/IntranetV3/Scolarite/ScolariteDetailsMigrator.php)
- [Structure de formation](STRUCTURE_FORMATION.md)

## Disponibilité des semestres

`StructureSemestre.actif` est supprimé. Une inscription peut exister sur chacun des deux semestres dès le début de l’année ; les cours peuvent se chevaucher. Les listes utilisent le PN de l’année universitaire sélectionnée, sans distinguer un semestre « actif ». La sélection du semestre de travail et la validation des résultats restent des notions distinctes.

La résolution automatique du contexte et les exceptions sont décrites dans [Gestion des années universitaires](ANNEES_UNIVERSITAIRES.md).
