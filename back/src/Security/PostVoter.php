<?php

namespace App\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{

    const CAN_EDIT_ETUDIANT = 'CAN_EDIT_ETUDIANT';

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::CAN_EDIT_ETUDIANT])) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // si connecté
        if (!($user instanceof Etudiant || $user instanceof Personnel)) {
            // the user must be logged in; if not, deny access
            $vote?->addReason('The user is not logged in.');
            return false;
        }

        // Vérification globale pour ROLE_SUPER_ADMIN
        if ($user instanceof Personnel && in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }

        $post = $subject;

        return match($attribute) {
            self::CAN_EDIT_ETUDIANT => $this->canEditEtudiant($post, $user),
            default => false,
        };
    }

    private function canEditEtudiant(Etudiant $etudiant, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel && (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SCOLARITE', $user->getRoles()) || in_array('ROLE_CHEF_DEPT', $user->getRoles())) ) {
            return true;
        }

        return $user instanceof Etudiant && $user === $etudiant;
    }

    // todo: à compléter
    private function canEdit()
    {

    }
}
