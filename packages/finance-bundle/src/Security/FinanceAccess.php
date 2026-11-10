<?php

namespace FinanceBundle\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use App\Security\DepartmentPermissionChecker;
use App\Security\UserEffectivePermissionService;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Enum\StatutCommandeEnum;

/**
 * Adaptateur des fonctionnalités du package vers les règles locales de FinanceVoter.
 * Une relation métier (auteur, demandeur, tuteur ou responsable) ne remplace jamais les habilitations du voter.
 * Les contraintes de statut et de contenu restent appliquées par les processeurs et workflows.
 */
class FinanceAccess
{
    public const ROLE_GESTIONNAIRE = 'ROLE_FINANCE_MANAGER';
    public const ROLE_VALIDEUR = 'ROLE_FINANCE_VALIDEUR';
    public const ROLE_DEMANDEUR = 'ROLE_FINANCE_DEMANDEUR';
    public const ROLE_ACCES = 'ROLE_FINANCE_VIEW';

    /** Statuts dans lesquels le demandeur peut encore supprimer sa demande. */
    private const SUPPRIMABLE_PAR_DEMANDEUR = [StatutCommandeEnum::BROUILLON];

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly DepartmentPermissionChecker $departements,
        private readonly FinanceVoter $voter,
    ) {
    }

    public function estSuperAdmin(mixed $user): bool
    {
        return $user instanceof Personnel && $this->permissions->isSuperAdmin($user);
    }

    /** Le rôle donné, dans ce département (le super administrateur a tous les rôles partout). */
    public function aRole(mixed $user, ?StructureDepartement $departement, string $role): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }
        if ($this->permissions->isSuperAdmin($user)) {
            return true;
        }

        return $departement !== null && $this->permissions->hasAnyPermissionInDepartment($user, [$role], $departement);
    }

    /** Accès au package dans le département actif, comme les permissions générales locales. */
    public function aAcces(mixed $user): bool
    {
        return $this->voter->allows(FinanceVoter::ACCES, null, $user);
    }

    /** Gestion du référentiel dans le département actif. */
    public function estGestionnaireQuelquePart(mixed $user): bool
    {
        return $this->voter->allows(FinanceVoter::REFERENTIEL_GERER, null, $user);
    }

    public function estGestionnaire(mixed $user, FinanceBonCommande $commande): bool
    {
        return $this->voter->allows(FinanceVoter::MANAGE, $commande, $user);
    }

    public function estValideur(mixed $user, FinanceBonCommande $commande): bool
    {
        return $this->voter->allows(FinanceVoter::MANAGE, $commande, $user);
    }

    public function estDemandeur(mixed $user, FinanceBonCommande $commande): bool
    {
        return self::memePersonne($commande->getDemandeur(), $user)
            && $this->voter->allows(FinanceVoter::EDIT, $commande, $user);
    }

    public function peutCreer(mixed $user, ?StructureDepartement $departement): bool
    {
        $commande = (new FinanceBonCommande())->setDepartement($departement);
        return $this->voter->allows(FinanceVoter::BC_CREER, $commande, $user);
    }

    public function peutVoir(mixed $user, FinanceBonCommande $commande): bool
    {
        return $this->voter->allows(FinanceVoter::VIEW, $commande, $user);
    }

    /**
     * Écriture sur la demande (PATCH). Le détail des champs permis selon le statut est dans
     * FinanceWorkflow::champsModifiables ; le valideur, lui, n'agit que par les transitions (avis).
     */
    public function peutModifier(mixed $user, FinanceBonCommande $commande): bool
    {
        return $this->voter->allows(FinanceVoter::EDIT, $commande, $user);
    }

    /** Dépôt du document signé : le demandeur tant que l'avis n'est pas rendu, le service financier jusqu'à la clôture. */
    public function peutDeposerDocument(mixed $user, FinanceBonCommande $commande): bool
    {
        if ($this->estGestionnaire($user, $commande)) {
            return !\in_array($commande->getStatut(), [StatutCommandeEnum::CLOTURE, StatutCommandeEnum::REJETE], true);
        }

        return $this->estDemandeur($user, $commande)
            && \in_array($commande->getStatut(), [StatutCommandeEnum::BROUILLON, StatutCommandeEnum::SOUMIS], true);
    }

    public function peutSupprimer(mixed $user, FinanceBonCommande $commande): bool
    {
        return $this->voter->allows(FinanceVoter::MANAGE, $commande, $user);
    }

    /**
     * Départements où la personne a ce rôle (pour filtrer les listes).
     *
     * @return list<int>
     */
    public function departementsAvecRole(Personnel $user, string $role): array
    {
        $ids = [];
        foreach ($user->getDepartementsPersonnel() as $affectation) {
            $departement = $affectation->getDepartement();
            if ($departement?->getId() !== null && $this->permissions->hasAnyPermissionInDepartment($user, [$role], $departement)) {
                $ids[] = $departement->getId();
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Départements dont la personne voit toutes les demandes (habilité à consulter les finances).
     *
     * @return list<int>
     */
    public function departementsVisibles(Personnel $user): array
    {
        return $this->departementsAvecRole($user, self::ROLE_ACCES);
    }

    /** Même personnel (instanceof plutôt que ::class : une entité chargée paresseusement est un proxy Doctrine). */
    public static function memePersonne(mixed $a, mixed $b): bool
    {
        if (!$a instanceof Personnel || !$b instanceof Personnel) {
            return false;
        }

        return $a === $b || ($a->getId() !== null && $a->getId() === $b->getId());
    }
}
