<?php

namespace FinanceBundle\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use App\Security\DepartmentPermissionChecker;
use App\Security\UserEffectivePermissionService;
use FinanceBundle\Entity\FinanceBonCommande;

/**
 * Règles d'accès aux demandes d'achat, partagées par le voter, le processeur, le contrôleur et l'extension de collection.
 * Les décisions de fond (voir, créer, modifier, gérer) sont prises par FinanceVoter::allows ; cette classe les adapte
 * aux services métier et ajoute les règles propres au circuit. Les rôles sont attribués par département ;
 *  - super administrateur : tout ;
 *  - gestionnaire financier (ROLE_FINANCE_MANAGER, dans au moins un département) : le service financier, toutes les
 *    demandes de l'établissement, quel qu'en soit le département ou le service ;
 *  - valideur (ROLE_FINANCE_VALIDEUR) : lecture des demandes de ses départements et avis de l'étape 1 (transitions) ;
 *  - demandeur (ROLE_FINANCE_DEMANDEUR) : crée des demandes dans ses départements, voit et suit les siennes ;
 *  - responsable désigné sur une demande : la voit.
 * Demande imputée à un service (pas de département) : déposée par toute personne ayant le rôle demandeur dans au moins
 * un département ; avis et gestion par le service financier.
 * Les étudiants n'ont jamais accès au package.
 */
class FinanceAccess
{
    public const ROLE_GESTIONNAIRE = FinanceVoter::ROLE_GESTIONNAIRE;
    public const ROLE_VALIDEUR = FinanceVoter::ROLE_VALIDEUR;
    public const ROLE_DEMANDEUR = FinanceVoter::ROLE_DEMANDEUR;
    public const ROLE_ACCES = FinanceVoter::ROLE_ACCES;

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        // Conservé pour compatibilité de construction : les rôles se lisent désormais par UserEffectivePermissionService.
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

        return $this->voter->roleDans($user, $role, $departement);
    }

    /** Accès au package dans au moins un département (menus, référentiel des fournisseurs). */
    public function aAcces(mixed $user): bool
    {
        return $this->voter->allows(FinanceVoter::ACCES, null, $user);
    }

    /** Gestionnaire financier dans au moins un département : le service financier (référentiels, toutes les demandes). */
    public function estGestionnaireQuelquePart(mixed $user): bool
    {
        return $this->voter->allows(FinanceVoter::REFERENTIEL_GERER, null, $user);
    }

    /** Service financier : gestionnaire dans au moins un département, compétent pour toutes les demandes. */
    public function estGestionnaire(mixed $user, FinanceBonCommande $commande): bool
    {
        return $this->estGestionnaireQuelquePart($user);
    }

    /** Avis de l'étape 1 : valideur du département ; pour un service, le service financier. */
    public function estValideur(mixed $user, FinanceBonCommande $commande): bool
    {
        if (self::imputeeAUnService($commande)) {
            return $this->estGestionnaireQuelquePart($user);
        }

        return $this->aRole($user, $commande->getDepartement(), self::ROLE_VALIDEUR);
    }

    /** Demande sans département (imputée à un service, ou pas encore imputée) : relève du service financier. */
    public static function imputeeAUnService(FinanceBonCommande $commande): bool
    {
        return $commande->getDepartement() === null;
    }

    /** L'auteur de la demande, qui a encore le rôle de demandeur (sinon aucun droit d'écriture). */
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

    /** Demande imputée à un service : toute personne ayant le rôle demandeur dans au moins un département. */
    public function peutCreerPourService(mixed $user): bool
    {
        if ($this->estSuperAdmin($user)) {
            return true;
        }

        return $user instanceof Personnel && $this->voter->roleQuelquePart($user, self::ROLE_DEMANDEUR);
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
        return $this->voter->allows(FinanceVoter::BC_DEPOSER_DOCUMENT, $commande, $user);
    }

    /** Le demandeur supprime son brouillon ; au-delà, seul le service financier. */
    public function peutSupprimer(mixed $user, FinanceBonCommande $commande): bool
    {
        return $this->voter->allows(FinanceVoter::BC_SUPPRIMER, $commande, $user);
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
            if ($departement?->getId() !== null && $this->voter->roleDans($user, $role, $departement)) {
                $ids[] = $departement->getId();
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Départements dont la personne voit toutes les demandes en tant que valideur (le service financier, lui, voit tout).
     *
     * @return list<int>
     */
    public function departementsVisibles(Personnel $user): array
    {
        return $this->departementsAvecRole($user, self::ROLE_VALIDEUR);
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
