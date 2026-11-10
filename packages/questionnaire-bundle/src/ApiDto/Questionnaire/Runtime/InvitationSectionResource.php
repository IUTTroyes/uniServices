<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use QuestionnaireBundle\State\Provider\Questionnaire\Runtime\InvitationSectionProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/invitations/{token}/sections/{id}',
            security: "is_granted(\'PUBLIC_ACCESS\')",
            provider: InvitationSectionProvider::class
        ),
    ],
)]
final class InvitationSectionResource {}
