<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use QuestionnaireBundle\State\Provider\Questionnaire\Runtime\InvitationIndexProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/invitations/{token}',
            provider: InvitationIndexProvider::class
        ),
    ],
)]
final class InvitationIndexDto
{
    /** @param list<SectionIndexDto> $sections */
    public function __construct(
        public string              $questionnaireTitle,
        public string              $invitationStatus,
        public ?\DateTimeImmutable $startedAt,
        public ?\DateTimeImmutable $submittedAt,
        public array               $sections
    )
    {
    }
}
