<?php
/*
 * Copyright (c) 2022. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Security/Voter/AbsenceNoteVoter.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 05/09/2022 10:55
 */

namespace App\Security\Voter;

use App\Entity\Personnel;
use App\Repository\PrevisionnelRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AbsenceNoteVoter extends Voter
{
    public function __construct(protected AbstractVoter $abstractVoter, protected PrevisionnelRepository $previsionnelRepository)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['CAN_ADD_ABSENCE', 'CAN_ADD_NOTE', 'CAN_EDIT_ABSENCE'])
            && is_array($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // vérifier que c'est un prof, qu'il est dans le bon département et qu'il a un prévisionnel associé.
        $user = $token->getUser();

        if (!$user instanceof Personnel) {
            return false;
        }
        // todo: tester éventuellement des droits supplémentaire, et donc pas besoin de prévisionnel?


        if (array_key_exists('semestre', $subject)) {
            if (!$this->abstractVoter->userInGoodDepartement($subject['semestre']->getDiplome()?->getDepartement())) {
                throw new AccessDeniedException('Vous n\'êtes pas dans le département associé à cette matière/ressource/SAÉ');
            }

            if ($this->abstractVoter->isResponsableDepartement($subject['semestre']->getDiplome()?->getDepartement())) {
                return true;
            }
        }

        if (array_key_exists('diplome', $subject)) {
            if (!$this->abstractVoter->userInGoodDepartement($subject['diplome']->getDepartement())) {
                throw new AccessDeniedException('Vous n\'êtes pas dans le département associé à cette matière/ressource/SAÉ');
            }

            if ($this->abstractVoter->isResponsableDepartement($subject['diplome']->getDepartement())) {
                return true;
            }
        }

        switch ($attribute) {
            case 'CAN_ADD_ABSENCE':
                // check if previsionnel exist...
                $previ = $this->previsionnelRepository->findBy([
                    'typeMatiere' => $subject['matiere']->typeMatiere,
                    'idMatiere' => $subject['matiere']->id,
                ]);
                if (0 === count($previ)) {
                    throw new AccessDeniedException('Vous ne pouvez pas saisir d\'absence dans cette matière/ressource/SAÉ');
                }
                break;
            case 'CAN_ADD_NOTE':
                // check if previsionnel exist... Vérifier selon l'étape s'il peut agir sur l'évaluation
                $previ = $this->previsionnelRepository->findBy([
                    'typeMatiere' => $subject['matiere']->typeMatiere,
                    'idMatiere' => $subject['matiere']->id,
                ]);
                if (0 === count($previ)) {
                    throw new AccessDeniedException('Vous ne pouvez pas saisir d\'évaluation dans cette matière/ressource/SAÉ');
                }
                break;
            case 'CAN_EDIT_ABSENCE':
                break;
        }

        return true;
    }
}
