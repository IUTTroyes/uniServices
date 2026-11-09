<?php

namespace FinanceBundle\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use App\Security\DepartmentPermissionChecker;
use App\Security\UserEffectivePermissionService;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Enum\StatutCommandeEnum;

/**
 * Règles d'accès aux demandes d'achat, partagées par le voter, le processeur, le contrôleur et l'extension de collection.
 * Les rôles du package sont toujours vérifiés dans le département de la demande (jamais globalement) :
 *  - super administrateur : tout ;
 *  - gestionnaire financier (ROLE_FINANCE_MANAGER) : toutes les demandes de ses départements ;
 *  - valideur (ROLE_FINANCE_VALIDEUR) : lecture des demandes de ses départements et avis de l'étape 1 (transitions) ;
 *  - demandeur (ROLE_FINANCE_DEMANDEUR) : crée des demandes dans ses départements, voit et suit les siennes ;
 *  - responsable désigné sur une demande : la voit.
 * Les étudiants n'ont jamais accès au package.
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

        return $departement !== null && $this->departements->checkPermission($user, $departement, $role);
    }

    /** Accès au package dans au moins un département (menus, référentiel des fournisseurs). */
    public function aAcces(mixed $user): bool
    {
        return $this->estSuperAdmin($user) || ($user instanceof Personnel && $this->departementsAvecRole($user, self::ROLE_ACCES) !== []);
    }

    /** Gestionnaire financier dans au moins un département (référentiels : fournisseurs, taux de TVA). */
    public function estGestionnaireQuelquePart(mixed $user): bool
    {
        return $this->estSuperAdmin($user) || ($user instanceof Personnel && $this->departementsAvecRole($user, self::ROLE_GESTIONNAIRE) !== []);
    }

    public function estGestionnaire(mixed $user, FinanceBonCommande $commande): bool
    {
        return $this->aRole($user, $commande->getDepartement(), self::ROLE_GESTIONNAIRE);
    }

    public function estValideur(mixed $user, FinanceBonCommande $commande): bool
    {
        return $this->aRole($user, $commande->getDepartement(), self::ROLE_VALIDEUR);
    }

    public function estDemandeur(mixed $user, FinanceBonCommande $commande): bool
    {
        return self::memePersonne($commande->getDemandeur(), $user);
    }

    public function peutCreer(mixed $user, ?StructureDepartement $departement): bool
    {
        return $this->aRole($user, $departement, self::ROLE_DEMANDEUR);
    }

    public function peutVoir(mixed $user, FinanceBonCommande $commande): bool
    {
        return $this->estGestionnaire($user, $commande)
            || $this->estValideur($user, $commande)
            || $this->estDemandeur($user, $commande)
            || self::memePersonne($commande->getResponsable(), $user);
    }

    /**
     * Écriture sur la demande (PATCH). Le détail des champs permis selon le statut est dans
     * FinanceWorkflow::champsModifiables ; le valideur, lui, n'agit que par les transitions (avis).
     */
    public function peutModifier(mixed $user, FinanceBonCommande $commande): bool
    {
        return $this->estGestionnaire($user, $commande) || $this->estDemandeur($user, $commande);
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
        return $this->estGestionnaire($user, $commande)
            || ($this->estDemandeur($user, $commande) && \in_array($commande->getStatut(), self::SUPPRIMABLE_PAR_DEMANDEUR, true));
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
            if ($departement?->getId() !== null && $this->departements->checkPermission($user, $departement, $role)) {
                $ids[] = $departement->getId();
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Départements dont la personne voit toutes les demandes (gestionnaire ou valideur).
     *
     * @return list<int>
     */
    public function departementsVisibles(Personnel $user): array
    {
        return array_values(array_unique(array_merge(
            $this->departementsAvecRole($user, self::ROLE_GESTIONNAIRE),
            $this->departementsAvecRole($user, self::ROLE_VALIDEUR),
        )));
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
