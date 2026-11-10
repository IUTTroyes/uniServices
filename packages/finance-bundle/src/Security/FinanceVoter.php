<?php

namespace FinanceBundle\Security;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceReception;
use FinanceBundle\Enum\StatutCommandeEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits du package Finances : une seule décision (`allows`) pour les opérations API et les services métier.
 * Les rôles sont attribués par département ; le service financier couvre tout l'établissement :
 *  - super administrateur : tout ;
 *  - gestionnaire (ROLE_FINANCE_MANAGER dans au moins un département) = service financier : voit et gère TOUTES les
 *    demandes, quel que soit leur département ou service, et le référentiel (fournisseurs, taux de TVA) ;
 *  - valideur (ROLE_FINANCE_VALIDEUR) : lit les demandes de ses départements ; son avis passe par les transitions ;
 *  - demandeur (ROLE_FINANCE_DEMANDEUR) : crée dans ses départements (ou pour un service, dans n'importe lequel) et
 *    suit les siennes ; sans le rôle, être l'auteur d'une demande ne donne aucun droit d'écriture ;
 *  - responsable désigné sur une demande : la voit.
 * Une demande est imputée à un département OU à un service (jamais les deux). Une demande de service n'a pas de
 * département : le service financier l'instruit (cf. FinanceAccess::imputeeAUnService).
 *
 * Suppression : le demandeur supprime son brouillon, au-delà le service financier seul. Dépôt du document signé : le
 * demandeur tant que l'avis n'est pas rendu (brouillon, soumise), le service financier jusqu'à la clôture.
 *
 * Attributs d'opération : CAN_VIEW_FINANCE, CAN_CREATE_FINANCE, CAN_EDIT_FINANCE, CAN_MANAGE_FINANCE (sujet : demande ou
 * réception) ; FINANCE_BC_* en sont les alias historiques ; FINANCE_ACCES / FINANCE_REFERENTIEL_GERER sont sans sujet.
 */
final class FinanceVoter extends Voter
{
    public const ACCES = 'FINANCE_ACCES';
    public const REFERENTIEL_GERER = 'FINANCE_REFERENTIEL_GERER';
    public const BC_VOIR = 'FINANCE_BC_VOIR';
    public const BC_MODIFIER = 'FINANCE_BC_MODIFIER';
    public const BC_SUPPRIMER = 'FINANCE_BC_SUPPRIMER';
    public const BC_CREER = 'FINANCE_BC_CREER';
    public const BC_GERER = 'FINANCE_BC_GERER';
    public const BC_DEPOSER_DOCUMENT = 'FINANCE_BC_DEPOSER_DOCUMENT';

    private const SUR_COMMANDE = [self::BC_VOIR, self::BC_MODIFIER, self::BC_SUPPRIMER, self::BC_CREER, self::BC_GERER, self::BC_DEPOSER_DOCUMENT];

    public const VIEW = 'CAN_VIEW_FINANCE';
    public const CREATE = 'CAN_CREATE_FINANCE';
    public const EDIT = 'CAN_EDIT_FINANCE';
    public const MANAGE = 'CAN_MANAGE_FINANCE';

    public const ROLE_GESTIONNAIRE = 'ROLE_FINANCE_MANAGER';
    public const ROLE_VALIDEUR = 'ROLE_FINANCE_VALIDEUR';
    public const ROLE_DEMANDEUR = 'ROLE_FINANCE_DEMANDEUR';
    public const ROLE_ACCES = 'ROLE_FINANCE_VIEW';

    public function __construct(private readonly UserEffectivePermissionService $permissions) {}

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::MANAGE, self::ACCES, self::REFERENTIEL_GERER, ...self::SUR_COMMANDE], true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::ACCES || $attribute === self::REFERENTIEL_GERER) return true;
        return $this->supportsAttribute($attribute)
            && ($subject instanceof FinanceBonCommande || $subject instanceof FinanceReception);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return $this->allows($attribute, $subject, $token->getUser());
    }

    /** Même décision pour les opérations API et les services métier. */
    public function allows(string $attribute, mixed $subject, mixed $user): bool
    {
        if (!$user instanceof Personnel) {
            return false;
        }
        $superAdmin = $this->permissions->isSuperAdmin($user);

        if ($attribute === self::ACCES) {
            return $superAdmin || $this->roleQuelquePart($user, self::ROLE_ACCES);
        }
        if ($attribute === self::REFERENTIEL_GERER) {
            return $superAdmin || $this->roleQuelquePart($user, self::ROLE_GESTIONNAIRE);
        }
        $precis = $attribute; // BC_SUPPRIMER et BC_DEPOSER_DOCUMENT dépendent du statut de la demande
        $attribute = match ($attribute) {
            self::BC_VOIR => self::VIEW,
            self::BC_CREER => self::CREATE,
            self::BC_MODIFIER, self::BC_DEPOSER_DOCUMENT => self::EDIT,
            self::BC_SUPPRIMER, self::BC_GERER => self::MANAGE,
            default => $attribute,
        };

        $commande = $subject instanceof FinanceReception ? $subject->getBonCommande() : $subject;
        if (!$commande instanceof FinanceBonCommande) {
            return $superAdmin;
        }
        $departement = $commande->getDepartement();
        $statut = $commande->getStatut();
        if ($precis === self::BC_SUPPRIMER && !$superAdmin && !$this->roleQuelquePart($user, self::ROLE_GESTIONNAIRE)) {
            return $statut === StatutCommandeEnum::BROUILLON && $this->auteurAvecRole($user, $commande);
        }
        if ($precis === self::BC_DEPOSER_DOCUMENT) {
            if ($superAdmin || $this->roleQuelquePart($user, self::ROLE_GESTIONNAIRE)) {
                return !\in_array($statut, [StatutCommandeEnum::CLOTURE, StatutCommandeEnum::REJETE], true);
            }

            return \in_array($statut, [StatutCommandeEnum::BROUILLON, StatutCommandeEnum::SOUMIS], true) && $this->auteurAvecRole($user, $commande);
        }
        if ($superAdmin) {
            return true;
        }

        // Création : le demandeur est imposé par le serveur (la ressource n'a pas encore de demandeur).
        if ($attribute === self::CREATE) {
            return match (true) {
                $departement !== null => $this->roleDans($user, self::ROLE_DEMANDEUR, $departement),
                $commande->getService() !== null => $this->roleQuelquePart($user, self::ROLE_DEMANDEUR),
                default => $this->roleQuelquePart($user, self::ROLE_ACCES), // sans imputation : la validation répond (422)
            };
        }

        // Service financier : gestion et lecture de toutes les demandes de l'établissement.
        if ($this->roleQuelquePart($user, self::ROLE_GESTIONNAIRE)) {
            return true;
        }
        if ($attribute === self::MANAGE) {
            return false;
        }

        if ($attribute === self::EDIT) {
            return $this->auteurAvecRole($user, $commande);
        }

        // Lecture : auteur, responsable désigné, valideur du département de la demande.
        return FinanceAccess::memePersonne($commande->getDemandeur(), $user)
            || FinanceAccess::memePersonne($commande->getResponsable(), $user)
            || ($departement !== null && $this->roleDans($user, self::ROLE_VALIDEUR, $departement));
    }

    /** L'auteur de la demande qui a (encore) le rôle de demandeur : sans lui, aucun droit d'écriture. */
    private function auteurAvecRole(Personnel $user, FinanceBonCommande $commande): bool
    {
        if (!FinanceAccess::memePersonne($commande->getDemandeur(), $user)) {
            return false;
        }

        return $commande->getDepartement() !== null
            ? $this->roleDans($user, self::ROLE_DEMANDEUR, $commande->getDepartement())
            : $this->roleQuelquePart($user, self::ROLE_DEMANDEUR);
    }

    /** Le rôle donné dans ce département. */
    public function roleDans(Personnel $user, string $role, ?StructureDepartement $departement): bool
    {
        return $departement !== null && $this->permissions->hasAnyPermissionInDepartment($user, [$role], $departement);
    }

    /** Le rôle donné dans au moins un département. */
    public function roleQuelquePart(Personnel $user, string $role): bool
    {
        foreach ($user->getDepartementsPersonnel() as $affectation) {
            if ($this->roleDans($user, $role, $affectation->getDepartement())) {
                return true;
            }
        }

        return false;
    }
}
