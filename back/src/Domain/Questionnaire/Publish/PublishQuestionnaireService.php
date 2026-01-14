<?php

namespace App\Domain\Questionnaire\Publish;

use App\Domain\Questionnaire\Structure\QuestionnaireStructureService;
use App\Entity\Questionnaires\QuestionnaireInvitation;
use App\Entity\Questionnaires\QuestionnaireSectionInstance;
use App\Entity\Questionnaires\Questionnaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class PublishQuestionnaireService
{
    public function __construct(
        private readonly EntityManagerInterface        $em,
        private readonly QuestionnaireStructureService $structureService
    ) {}

    /**
     * @param list<string> $recipientEmails
     * @return array{sectionsCount:int,invitationsCount:int,publishedAt:\DateTimeImmutable}
     */
    public function publish(Questionnaire $q, array $recipientEmails): array
    {
        if ($q->isPublished()) {
            throw new \RuntimeException('Questionnaire already published');
        }

        // Geler questionnaire
        $q->publishNow();

        // Générer QuestionnaireSectionInstance (structure figée)
        $plan = $this->structureService->buildPlan($q);
        foreach ($plan as $row) {
            $psi = new QuestionnaireSectionInstance(
                q: $q,
                st: $row['sectionTemplate'],
                order: $row['order'],
                titleSnapshot: $row['title'],
                repeatItemType: $row['repeatItemType'],
                repeatItemId: $row['repeatItemId']
            );
            $this->em->persist($psi);
        }

        // Générer invitations + tokens
        //todo: traiter selon le type de destinataire...
        $countInv = 0;
        foreach ($recipientEmails as $email) {
            $email = trim(mb_strtolower($email));
            if ($email === '') { continue; }
            $token = Uuid::v7()->toRfc4122();
            $inv = new QuestionnaireInvitation($q, $token, $email);
            $this->em->persist($inv);
            $countInv++;
        }

        $this->em->flush();

        return [
            'sectionsCount' => count($plan),
            'invitationsCount' => $countInv,
            'publishedAt' => $q->getPublishedAt() ?? new \DateTimeImmutable(),
        ];
    }
}
