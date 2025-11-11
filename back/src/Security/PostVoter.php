<?php

namespace App\Security;

use App\Entity\Etudiant\EtudiantNote;
use App\Entity\Scolarite\ScolEvaluation;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{

    const CAN_EDIT_ETUDIANT = 'CAN_EDIT_ETUDIANT';
    const CAN_EDIT_SCOL = 'CAN_EDIT_SCOL';
    const CAN_EDIT_EVAL = 'CAN_EDIT_EVAL';
    const CAN_EDIT_NOTES = 'CAN_EDIT_NOTES';

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::CAN_EDIT_ETUDIANT, self::CAN_EDIT_SCOL, self::CAN_EDIT_EVAL, self::CAN_EDIT_NOTES])) {
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
            self::CAN_EDIT_SCOL => $this->canEditScolarite($user),
            self::CAN_EDIT_EVAL => $this->canEditEvaluation($user),
            self::CAN_EDIT_NOTES => $this->canEditNotes($post, $user),
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

    private function canEditScolarite(Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel && (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SCOLARITE', $user->getRoles()) || in_array('ROLE_CHEF_DEPT', $user->getRoles()) || in_array('ROLE_ASSISTANT', $user->getRoles()) || in_array('ROLE_DIRECTEUR_ETUDES', $user->getRoles()) || in_array('ROLE_RESP_PARCOURS', $user->getRoles()) ) ) {
            return true;
        }

        return false;
    }

    private function canEditEvaluation(Personnel|Etudiant $user)
    {
        if ($user instanceof Personnel && (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_CHEF_DEPT', $user->getRoles()) || in_array('ROLE_RESP_PARCOURS', $user->getRoles()) || in_array('ROLE_RESP_NOTES', $user->getRoles()) ) ) {
            return true;
        }

        return false;
    }

    private function canEditNotes(mixed $subject, Personnel|Etudiant $user): bool
    {
        if ($user instanceof Personnel && (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_CHEF_DEPT', $user->getRoles()) || in_array('ROLE_RESP_PARCOURS', $user->getRoles()) || in_array('ROLE_RESP_NOTES', $user->getRoles()) ) ) {
            return true;
        }

        // Si l'objet est une note, vérifier que le personnel est autorisé sur l'évaluation liée
        if ($subject instanceof EtudiantNote) {
            $evaluation = $subject->getEvaluation();
            if ($evaluation instanceof ScolEvaluation && $user instanceof Personnel) {
                return $evaluation->getPersonnelAutorise()->contains($user);
            }
        }

        return false;
    }
}
