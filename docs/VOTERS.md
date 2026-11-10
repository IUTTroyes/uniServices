# Autorisation côté serveur

Les voters sont organisés par famille de règles métier. Le noyau couvre la structure, la scolarité, l’APC, le personnel, l’EDT et les règles transversales. Les modules Document, Helpdesk, Questionnaire, Stage et Intranet gardent leurs propres voters. Une ressource mérite un voter dédié quand ses règles de propriété ou de périmètre divergent de celles de son domaine.

| Élément | Responsabilité |
| --- | --- |
| `PermissionRegistry` et `PermissionResolver` | Définir les rôles et calculer leurs héritages selon les modules actifs |
| `UserEffectivePermissionService` | Rechercher les droits d’une personne sur l’affectation pertinente |
| `DepartmentScope` et `DiplomeScope` | Identifier le rattachement métier utilisé pour le contrôle |
| Voters métier | Décider si le compte peut effectuer l’action demandée |
| Extension de requête EDT | Limiter les résultats étudiants avant agrégation ou sérialisation |

## Où trouver les règles

| Domaine | Voters |
| --- | --- |
| Structure, actualités et affectations | [StructureVoter](../back/src/Security/StructureVoter.php), [PersonnelVoter](../back/src/Security/PersonnelVoter.php) |
| Étudiants, évaluations, notes et absences | [PostVoter](../back/src/Security/PostVoter.php) |
| Enseignements et APC | [ScolariteVoter](../back/src/Security/ScolariteVoter.php), [ApcVoter](../back/src/Security/ApcVoter.php) |
| Emplois du temps et salles | [EdtVoter](../back/src/Security/EdtVoter.php), [SalleVoter](../back/src/Security/SalleVoter.php) |
| Identité et permissions transversales | [UserTypeVoter](../back/src/Security/UserTypeVoter.php), [GlobalVoter](../back/src/Security/GlobalVoter.php), [DepartmentPermissionVoter](../back/src/Security/DepartmentPermissionVoter.php), [DiplomePermissionVoter](../back/src/Security/DiplomePermissionVoter.php) |
| Prévisionnels et plans de cours | [PrevisionnelVoter](../packages/intranet-bundle/src/Security/Voter/PrevisionnelVoter.php), [PlanCoursVoter](../packages/intranet-bundle/src/Security/Voter/PlanCoursVoter.php) |
| Modules métier | [FinanceVoter](../packages/finance-bundle/src/Security/FinanceVoter.php), [DocumentVoter](../packages/document-bundle/src/Security/DocumentVoter.php), [TicketVoter](../packages/helpdesk-bundle/src/Security/TicketVoter.php), [QuestionnaireVoter](../packages/questionnaire-bundle/src/Security/QuestionnaireVoter.php), [StageVoter](../packages/stage-bundle/src/Security/StageVoter.php) |

Les définitions de rôles du noyau se trouvent dans [CorePermissionProvider](../back/src/Security/CorePermissionProvider.php). Les modules déclarent leurs propres rôles via `PermissionProviderInterface`. Pour vérifier une habilitation, consulter le voter, la définition du rôle et le point d’appel.

## Périmètres

- `SUPER_ADMIN` est global dès qu’une affectation donne ce droit. Le calcul utilise les permissions résolues de l’affectation, avec les règles d’activation du module.
- Pour une écriture départementale, vérifier l’affectation du personnel dans le département propriétaire de la ressource. Le département actif sert aux contrôles généraux et à la navigation.
- Sur les créations locales dont le rattachement est absent, utiliser l’affectation active. Les référentiels institutionnels sans département sont réservés à `SUPER_ADMIN`.
- Pour un groupe rattaché à plusieurs semestres ou un enseignement rattaché à plusieurs UE, vérifier chaque rattachement. La première relation ne représente pas l’ensemble du périmètre.
- `ROLE_RESP_PARCOURS` exige un diplôme identifié et couvert par l’affectation. La liste de diplômes utilisée dans l’interface constitue une indication de navigation.
- La consultation d’un événement EDT requiert un compte connecté. Pour un étudiant, le groupe de l’événement doit appartenir à ses groupes. Les événements sans groupe ne lui sont pas visibles. Le même filtre s’applique aux collections EDT, dont le pointage et les statistiques construites par le fournisseur Doctrine.
- `EdtEvent::typeEvenement` distingue `cours`, `personnel` et `autre`. Le champ historique `type` garde le format CM, TD ou TP. Les cours exigent `ROLE_EDT` sur le périmètre concerné. Un événement personnel est visible par son propriétaire et `SUPER_ADMIN`. Le propriétaire peut créer ou modifier son événement sans rattachement pédagogique.
- Les documents et catégories départementaux exigent `ROLE_ADMIN` ou `ROLE_CHEF_DEPT` dans le département concerné.
- Les étudiants connectés peuvent consulter les questionnaires sans invitation. Les écritures sur les questions et sections suivent le département du questionnaire parent.

## Ajouter une opération

1. Déclarer un attribut d’action dans le voter du domaine et vérifier qu’un seul voter traite ce couple attribut/sujet.
2. Déclarer les attributs fixes dans `supportsAttribute()` pour que Symfony puisse mémoriser les voters à interroger. Contrôler le type du sujet dans `supports()` ou avant d’appliquer une règle dépendante de la ressource.
3. Sur une écriture locale, passer l’objet métier au voter et résoudre son périmètre à partir des relations propriétaires. Sur une modification, contrôler l’objet avant et après dénormalisation si le rattachement ou le propriétaire peut changer.
4. Pour une collection, filtrer la requête serveur. Un vote sur un élément individuel ne filtre pas une liste et un filtre demandé par le client ne définit pas les droits.
5. Vérifier un compte sans droits, un compte autorisé sur le département de la ressource, le même rôle dans un autre département et un super administrateur dont l’affectation active diffère de celle qui donne ce droit. Pour une ressource partagée, vérifier un compte autorisé sur une partie seulement des rattachements.

### Exemple avec API Platform

```php
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    operations: [
        new Post(securityPostDenormalize: "is_granted('CAN_EDIT_EXEMPLE', object)"),
        new Patch(
            security: "is_granted('CAN_EDIT_EXEMPLE', object)",
            securityPostDenormalize: "is_granted('CAN_EDIT_EXEMPLE', object)",
        ),
        new Delete(security: "is_granted('CAN_DELETE_EXEMPLE', object)"),
    ]
)]
```

### Exemple dans un contrôleur

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('CAN_EDIT_EVAL', subject: 'evaluation')]
public function editEvaluation(ScolEvaluation $evaluation): Response
{
    // ...
}
```

### Exemple dans un service

```php
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

public function __construct(
    private AuthorizationCheckerInterface $authChecker
) {}

public function someMethod(ScolEvaluation $evaluation): void
{
    if ($this->authChecker->isGranted('CAN_EDIT_EVAL', $evaluation)) {
        // L'utilisateur peut modifier
    }
}
```

## Déploiement

La migration `Version20261109120000` ajoute le type d’événement. Elle classe les événements historiques liés à un enseignement comme cours et les autres comme événements de type autre. L’interface de gestion des événements personnels sera ajoutée séparément.

## Protection des routes API

L’accès anonyme aux opérations API Platform est bloqué dans le pare-feu avant la lecture des ressources. Les contrôleurs Symfony portent leurs propres exigences d’authentification ou de rôle. La règle générique `PUBLIC_ACCESS` reste en place pour la transition prévue vers la production.

Les exceptions explicites couvrent la connexion, le renouvellement de session, la déconnexion, la réinitialisation du mot de passe, les flux RSS et les routes d’invitation qui vérifient leur jeton. Conserver ces exceptions avant la règle générale lors du passage à une API authentifiée.

- Les bons de commande sont modifiables par leur demandeur ou un gestionnaire finance du département. Le demandeur peut déposer le PDF signé depuis la route dédiée. Les écritures sur les fournisseurs et les réceptions exigent le rôle de gestionnaire. Les nouveaux PDF signés sont écrits sous `var/uploads/finance`. Les configurations web fournies bloquent l’accès statique aux anciens PDF, qui restent lisibles par le contrôleur autorisé.
- Les actualités sont gérées par les administrateurs, chefs de département et relais communication du département.
- Un étudiant peut créer et modifier son propre dossier de stage et ses propres avenants. La validation et la gestion des soutenances restent réservées aux gestionnaires autorisés sur le département.
- Les responsables de plan de cours, les enseignants affectés dans le prévisionnel et les gestionnaires de l’enseignement peuvent écrire sur les plans et leurs éléments associés.
- Les collections de finance, de stages, de scolarité et d’invitations de questionnaires sont filtrées côté serveur. Pour les moyennes et les rattrapages, le périmètre comprend l’étudiant concerné, les enseignants liés au résultat et les personnels de scolarité ou de gestion des notes habilités dans le département.
- Les opérations globales d’import CSV d’étudiants sont réservées à `SUPER_ADMIN`.

Pour une route de contrôleur qui reçoit un identifiant, autoriser l’accès sur l’objet chargé avant l’export ou la mutation. Une route de lot doit vérifier chaque cible avant d’effectuer la première écriture.

## Intégration des packages (fusion novembre 2026)

Les règles locales des voters restent la référence. Les attributs `FINANCE_BC_*`, `QUESTIONNAIRE_*` et
`STAGE_ETUDIANT_*` sont traduits vers les décisions `CAN_*` correspondantes ; ils ne définissent pas une seconde
politique. Les services `FinanceAccess`, `QuestionnaireAccess` et `StageAccess` appellent directement la même
méthode `allows()` que Symfony. Être auteur, responsable désigné ou tuteur ne donne pas de dérogation aux rôles locaux.

Les expressions de sécurité des opérations existantes, notamment les contrôles avant et après dénormalisation,
sont conservées. Deux adaptations concernent la création : le demandeur d'une commande est imposé par le serveur
(et cette identité est utilisée pour le vote), et le parent d'une question/section est chargé depuis l'URL avant
le contrôle local dans `QuestionnaireStructureProcessor`, avant toute persistance.

Le circuit financier, les notifications, les exports et les nouveaux processeurs restent actifs. Les validations,
réceptions et décisions portées par un document exigent la gestion financière locale. Les contraintes métier
(de statut, contenu, anonymat, dates) s'ajoutent aux permissions sans les remplacer. Les filtres entrants fondés
sur la propriété ont été retirés au profit des règles de collection locales, en particulier
`DepartmentCollectionAccessExtension` pour les finances, stages et invitations.

Validation de non-régression : `tests/Security/MergePermissionsTest.php`, tests d'accès Questionnaire/Stage et tests
du processeur Stage. Les tests API nécessitent la base de test configurée ; leur absence ne valide pas les parcours HTTP.
