<?php

namespace FinanceBundle\Security;

use FinanceBundle\Entity\FinanceBonCommande;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Droits du package Finances (règles détaillées dans FinanceAccess).
 *  - FINANCE_ACCES / FINANCE_REFERENTIEL_GERER (fournisseurs, taux de TVA) : sans sujet ;
 *  - FINANCE_BC_* : sujet FinanceBonCommande.
 *
 * @extends Voter<string, mixed>
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

    public function __construct(private readonly FinanceAccess $access)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (\in_array($attribute, self::SUR_COMMANDE, true)) {
            return $subject instanceof FinanceBonCommande;
        }

        return $attribute === self::ACCES || $attribute === self::REFERENTIEL_GERER;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        return match ($attribute) {
            self::ACCES => $this->access->aAcces($user),
            self::REFERENTIEL_GERER => $this->access->estGestionnaireQuelquePart($user),
            self::BC_VOIR => $this->access->peutVoir($user, $subject),
            self::BC_MODIFIER => $this->access->peutModifier($user, $subject),
            self::BC_SUPPRIMER => $this->access->peutSupprimer($user, $subject),
            // Sans département, on laisse la validation répondre (422 « département obligatoire »).
            self::BC_CREER => $subject->getDepartement() === null
                ? $this->access->aAcces($user)
                : $this->access->peutCreer($user, $subject->getDepartement()),
            self::BC_GERER => $this->access->estGestionnaire($user, $subject),
            self::BC_DEPOSER_DOCUMENT => $this->access->peutDeposerDocument($user, $subject),
            default => false,
        };
    }
}
