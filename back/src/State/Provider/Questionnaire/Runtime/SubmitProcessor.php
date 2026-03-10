<?php

namespace App\State\Questionnaire\Runtime;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiDto\Questionnaire\Runtime\SubmitOutput;
use App\Entity\Questionnaires\QuestionnaireInvitation;
use Doctrine\ORM\EntityManagerInterface;

final class SubmitProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SubmitOutput
    {
        $token = (string) $uriVariables['token'];

        $inv = $this->em->getRepository(QuestionnaireInvitation::class)->findOneBy(['token' => $token]);
        if (!$inv) { throw new \RuntimeException('Invitation not found'); }
        if ($inv->isSubmitted()) { return new SubmitOutput(true, $inv->getSubmittedAt() ?? new \DateTimeImmutable()); }

        // TODO: validation finale required + types + cohérence conditions (si nécessaire)
        $inv->markSubmitted();
        $this->em->flush();

        return new SubmitOutput(true, $inv->getSubmittedAt() ?? new \DateTimeImmutable());
    }
}
