<?php

namespace App\Security\Voter;

use App\Entity\FicheHeure;
use App\Entity\Users\Personnel;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Enum\FicheHeureStatutEnum;
use App\Enum\StatutEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class FicheHeureVoter extends Voter
{
    public const CREATE = 'FICHE_HEURE_CREATE';
    public const READ_OWN = 'FICHE_HEURE_READ_OWN';
    public const EDIT_OWN = 'FICHE_HEURE_EDIT_OWN';
    public const SUBMIT_OWN = 'FICHE_HEURE_SUBMIT_OWN';
    public const VALIDATE = 'FICHE_HEURE_VALIDATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::CREATE, self::READ_OWN, self::EDIT_OWN, self::SUBMIT_OWN, self::VALIDATE])) {
            return false;
        }

        if ($attribute === self::CREATE) {
            return $subject === null || $subject === FicheHeure::class;
        }

        return $subject instanceof FicheHeure;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::CREATE:
                if ($user instanceof Personnel) {
                    return $user->getStatut() === StatutEnum::BIATSS;
                }
                return false;

            case self::READ_OWN:
                /** @var FicheHeure $subject */
                return $user === $subject->getPersonnel();

            case self::EDIT_OWN:
                /** @var FicheHeure $subject */
                return $user === $subject->getPersonnel() && $subject->getStatut() === FicheHeureStatutEnum::BROUILLON;

            case self::SUBMIT_OWN:
                /** @var FicheHeure $subject */
                return $user === $subject->getPersonnel() && $subject->getStatut() === FicheHeureStatutEnum::BROUILLON;

            case self::VALIDATE:
                /** @var FicheHeure $subject */
                if (!$user instanceof Personnel) {
                    return false;
                }

                if ($subject->getStatut() !== FicheHeureStatutEnum::SOUMISE) {
                    return false;
                }

                $owner = $subject->getPersonnel();
                if (!$owner instanceof Personnel) {
                    return false;
                }

                $validatorUser = $user; // Alias for clarity as per subtask description
                $validatorDepartementPersonnels = $validatorUser->getDepartementPersonnels();
                $ownerDepartementPersonnels = $owner->getDepartementPersonnels();

                foreach ($validatorDepartementPersonnels as $valDeptPersonnel) {
                    if (!$valDeptPersonnel instanceof StructureDepartementPersonnel) continue;
                    $valDept = $valDeptPersonnel->getDepartement();
                    if (!$valDept) continue;

                    foreach ($ownerDepartementPersonnels as $ownerDeptPersonnel) {
                        if (!$ownerDeptPersonnel instanceof StructureDepartementPersonnel) continue;
                        $ownerDept = $ownerDeptPersonnel->getDepartement();
                        if (!$ownerDept) continue;

                        if ($valDept === $ownerDept) {
                            $valRoles = $valDeptPersonnel->getRoles();
                            if (isset($valRoles['uni_fiche_heures']) &&
                                in_array('ROLE_FICHE_HEURE_VALIDATEUR', $valRoles['uni_fiche_heures'])) {
                                return true;
                            }
                        }
                    }
                }
                return false;
        }

        return false;
    }
}
