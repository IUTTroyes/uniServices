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
        /** Le répondant peut (encore) enregistrer ou modifier ses réponses. */
        public bool                $modifiable,
        public ?\DateTimeImmutable $submittedAt,
        public array               $sections,
        public ?string             $description = null,
        /** Durée estimée en secondes (calculée par l'éditeur), null si inconnue. */
        public ?int                $estimatedTime = null,
        public ?string             $startText = null,
        public ?string             $endText = null,
    )
    {
    }
}
