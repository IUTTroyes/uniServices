<?php

namespace UnifolioBundle\Security;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Security\UserEffectivePermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use UnifolioBundle\Entity\Evaluation;
use UnifolioBundle\Enum\StatutEvaluationEnum;
use UnifolioBundle\Service\Campagne\EvaluateurAcces;

/**
 * Droits sur une évaluation.
 * - l'évaluateur voit et modifie les siennes (tant qu'il reste affecté à l'étudiant) ;
 * - il voit aussi les évaluations rendues des autres évaluateurs de l'étudiant ;
 * - le référent du département les consulte ;
 * - l'étudiant voit ce qui lui a été communiqué.
 *
 * @extends Voter<string, Evaluation>
 */
class EvaluationVoter extends Voter
{
    public const VOIR = 'UNIFOLIO_EVALUATION_VOIR';
    public const MODIFIER = 'UNIFOLIO_EVALUATION_MODIFIER';

    public function __construct(
        private readonly UserEffectivePermissionService $permissions,
        private readonly ReferentContexte $contexte,
        private readonly EvaluateurAcces $acces,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Evaluation && \in_array($attribute, [self::VOIR, self::MODIFIER], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        \assert($subject instanceof Evaluation);
        $user = $token->getUser();

        if ($user instanceof Etudiant) {
            return $attribute === self::VOIR
                && $subject->getEtudiant()->getId() === $user->getId()
                && $subject->isVisibleEtudiant()
                && $subject->getStatut() !== StatutEvaluationEnum::EN_COURS;
        }
        if (!$user instanceof Personnel) {
            return false;
        }

        if ($subject->estDe($user)) {
            return true;
        }
        if ($attribute === self::MODIFIER) {
            $vote?->addReason('Une évaluation ne peut être modifiée que par son auteur.');

            return false;
        }

        if ($this->contexte->isSuperAdmin($user)
            || ($this->permissions->hasAnyPermission($user, [UnifolioRoles::REFERENT]) && $this->contexte->administre($user, $subject->getCampagne()->getDiplome()->getDepartement()))) {
            return true;
        }

        return $subject->getStatut() === StatutEvaluationEnum::RENDUE && $this->acces->estAffecte($user, $subject->getEtudiant());
    }
}
