<?php

namespace QuestionnaireBundle\Security;

use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Repository\Questionnaires\QuestionnaireRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * Charge un questionnaire à partir de son UUID (variable d'URL) et vérifie le droit demandé :
 * 404 si l'UUID est invalide ou inconnu, 403 si le droit est refusé.
 * Utilisé par les providers, processeurs et contrôleurs qui ne passent pas par le chargement d'API Platform.
 */
final class QuestionnaireResolver
{
    public function __construct(
        private readonly QuestionnaireRepository $repository,
        private readonly Security $security,
    ) {
    }

    public function charger(mixed $uuid, string $attribut = QuestionnaireVoter::VIEW): Questionnaire
    {
        $uuid = (string) $uuid;
        $questionnaire = Uuid::isValid($uuid) ? $this->repository->findOneBy(['uuid' => Uuid::fromString($uuid)]) : null;
        if (!$questionnaire instanceof Questionnaire) {
            throw new NotFoundHttpException('Questionnaire introuvable.');
        }
        if (!$this->security->isGranted($attribut, $questionnaire)) {
            throw new AccessDeniedHttpException('Accès refusé à ce questionnaire.');
        }

        return $questionnaire;
    }
}
