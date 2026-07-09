<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use QuestionnaireBundle\State\Provider\Questionnaire\Runtime\StudentInvitationsProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/student/invitations',
            provider: StudentInvitationsProvider::class
        ),
    ],
)]
final class StudentInvitationDto
{
    public function __construct(
        public string $uuid,
        public string $title,
        public ?string $description,
        public ?int $estimatedTime,
        public ?string $deadline,
        public string $token,
        public string $status,
        public bool $anonymous
    ) {}
}
