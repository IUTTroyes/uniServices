<?php

namespace QuestionnaireBundle\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\DepartmentPermissionChecker;
use App\Security\UserEffectivePermissionService;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Services\QuestionnaireReglages;

/**
 * Règles d'accès aux questionnaires, partagées par le voter, les processeurs et l'extension de collection.
 *
 *  - super administrateur : tout ;
 *  - gestionnaire (ROLE_QUESTIONNAIRE_MANAGER ou ROLE_QUALITE) : tous les questionnaires des départements où il a
 *    l'un de ces rôles ; un questionnaire sans département (établissement) n'est gérable que par le super administrateur ;
 *  - référent qualité du département (réglages du package) : consultation des questionnaires et résultats du département ;
 *  - destinataire des résultats (diffusion validée) : après publication, lecture des seules sections qui le concernent ;
 *  - auteur : garde la main sur ses propres questionnaires (consultation, paramètres, publication, relances, résultats),
 *    mais ne peut supprimer que ses brouillons et ne peut les déplacer que vers un département qu'il gère.
 *
 * Les répondants n'ont aucun droit ici : ils accèdent à leur questionnaire par le jeton de leur invitation.
 */
class QuestionnaireAccess
{
    public const ROLES_GESTIONNAIRE = ['ROLE_QUESTIONNAIRE_MANAGER', 'ROLE_QUALITE'];

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly DepartmentPermissionChecker $departements,
        private readonly QuestionnaireReglages $reglages,
    ) {
    }

    public function estSuperAdmin(mixed $user): bool
    {
        return $user instanceof Personnel && $this->permissions->isSuperAdmin($user);
    }

    /**
     * Gestionnaire des questionnaires du département. Sans département (questionnaire d'établissement) : super
     * administrateur uniquement.
     */
    public function estGestionnaire(mixed $user, ?StructureDepartement $departement): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }
        if ($this->estSuperAdmin($user)) {
            return true;
        }
        if ($departement === null) {
            return false;
        }
        foreach (self::ROLES_GESTIONNAIRE as $role) {
            if ($this->departements->checkPermission($user, $departement, $role)) {
                return true;
            }
        }

        return false;
    }

    /** Gestionnaire dans au moins un département (menus, création). */
    public function estGestionnaireQuelquePart(mixed $user): bool
    {
        return $this->estSuperAdmin($user) || ($user instanceof Personnel && $this->departementsGeres($user) !== []);
    }

    /**
     * Départements dont la personne gère les questionnaires (pour filtrer les listes).
     *
     * @return list<int>
     */
    public function departementsGeres(Personnel $user): array
    {
        $ids = [];
        foreach ($user->getDepartementsPersonnel() as $affectation) {
            $departement = $affectation->getDepartement();
            if ($departement?->getId() === null) {
                continue;
            }
            foreach (self::ROLES_GESTIONNAIRE as $role) {
                if ($this->departements->checkPermission($user, $departement, $role)) {
                    $ids[] = $departement->getId();
                    break;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public function estAuteur(mixed $user, Questionnaire $questionnaire): bool
    {
        return $user instanceof Personnel && self::memePersonne($questionnaire->getAuthor(), $user);
    }

    /** Consultation du questionnaire, de sa structure, de ses invitations et de ses résultats. */
    public function peutVoir(mixed $user, Questionnaire $questionnaire): bool
    {
        // Modèle d'établissement : bibliothèque commune, consultable (et réutilisable) par tout gestionnaire.
        if ($questionnaire->isEstModele() && $questionnaire->getDepartement() === null && $this->estGestionnaireQuelquePart($user)) {
            return true;
        }

        return $this->peutGerer($user, $questionnaire)
            || ($user instanceof Personnel && $this->reglages->estResponsableQualite($user, $questionnaire->getDepartement()));
    }

    /** Résultats agrégés (statistiques, exports) : qui peut voir le questionnaire, ou destinataire de résultats publiés. */
    public function peutVoirSynthese(mixed $user, Questionnaire $questionnaire): bool
    {
        return $this->peutVoir($user, $questionnaire) || $this->sectionsDestinataire($user, $questionnaire) !== [];
    }

    /**
     * Sections dont les résultats sont visibles : null = toutes (équipe), sinon celles dont la personne est
     * destinataire une fois les résultats publiés.
     *
     * @return list<int>|null
     */
    public function sectionsVisibles(mixed $user, Questionnaire $questionnaire): ?array
    {
        return $this->peutVoir($user, $questionnaire) ? null : $this->sectionsDestinataire($user, $questionnaire);
    }

    /** @return list<int> */
    public function sectionsDestinataire(mixed $user, Questionnaire $questionnaire): array
    {
        if (!$user instanceof Personnel || !$questionnaire->resultatsPublies()) {
            return [];
        }
        $ids = [];
        foreach ($questionnaire->getSectionInstances() as $section) {
            if ($section->estDestinataire($user)) {
                $ids[] = (int) $section->getId();
            }
        }

        return $ids;
    }

    /** Paramètres, structure (tant que le questionnaire est un brouillon), publication, invitations, relances. */
    public function peutGerer(mixed $user, Questionnaire $questionnaire): bool
    {
        return $this->estGestionnaire($user, $questionnaire->getDepartement()) || $this->estAuteur($user, $questionnaire);
    }

    /**
     * Départements dont la personne voit les questionnaires : ceux qu'elle gère et ceux dont elle est le référent qualité.
     *
     * @return list<int>
     */
    public function departementsVisibles(Personnel $user): array
    {
        $ids = $this->departementsGeres($user);
        foreach ($user->getDepartementsPersonnel() as $affectation) {
            $departement = $affectation->getDepartement();
            if ($departement?->getId() !== null && $this->reglages->estResponsableQualite($user, $departement)) {
                $ids[] = $departement->getId();
            }
        }

        return array_values(array_unique($ids));
    }

    /** Suppression : gestionnaire du département, ou auteur d'un brouillon. */
    public function peutSupprimer(mixed $user, Questionnaire $questionnaire): bool
    {
        return $this->estGestionnaire($user, $questionnaire->getDepartement())
            || ($this->estAuteur($user, $questionnaire) && $questionnaire->estBrouillon());
    }

    /** Créer un questionnaire dans ce département (ou l'y déplacer) : gestionnaire du département. */
    public function peutCreer(mixed $user, ?StructureDepartement $departement): bool
    {
        return $this->estGestionnaire($user, $departement);
    }

    /**
     * Même personne ? (même type de compte et même identifiant ; instanceof plutôt que ::class, une entité chargée
     * paresseusement étant un proxy Doctrine).
     */
    public static function memePersonne(Etudiant|Personnel|null $a, Etudiant|Personnel|null $b): bool
    {
        if ($a === null || $b === null || ($a instanceof Etudiant) !== ($b instanceof Etudiant)) {
            return false;
        }

        return $a === $b || ($a->getId() !== null && $a->getId() === $b->getId());
    }
}
