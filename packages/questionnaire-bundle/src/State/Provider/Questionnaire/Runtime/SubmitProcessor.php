<?php

namespace QuestionnaireBundle\State\Provider\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use QuestionnaireBundle\ApiDto\Questionnaire\Runtime\SubmitOutput;
use QuestionnaireBundle\Domain\Questionnaire\Runtime\InvitationRuntime;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Envoi des réponses, par le jeton de l'invitation. Une fois envoyées, un nouvel envoi n'est accepté que si le
 * questionnaire autorise la modification des réponses (sinon il est sans effet).
 * Refusé si le questionnaire n'est pas ouvert (409) ou s'il manque une réponse obligatoire (422).
 *
 * @implements ProcessorInterface<mixed, SubmitOutput>
 */
final class SubmitProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly InvitationRuntime $runtime,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SubmitOutput
    {
        $invitation = $this->runtime->invitation($uriVariables['token'] ?? '');
        if ($invitation->isSubmitted() && !$this->runtime->estModifiable($invitation)) {
            return new SubmitOutput(true, $invitation->getSubmittedAt() ?? new \DateTimeImmutable());
        }
        $this->runtime->verifierModifiable($invitation);

        $manquantes = $this->runtime->obligatoiresManquantes($invitation);
        if ($manquantes !== []) {
            throw new UnprocessableEntityHttpException('Réponses obligatoires manquantes : '.implode(' ; ', \array_slice($manquantes, 0, 10)).(\count($manquantes) > 10 ? '…' : ''));
        }

        $invitation->markStarted();
        $invitation->markSubmitted();
        $this->em->flush();

        return new SubmitOutput(true, $invitation->getSubmittedAt() ?? new \DateTimeImmutable());
    }
}
