<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use QuestionnaireBundle\State\Questionnaire\Runtime\InvitationSectionProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/invitations/{token}/sections/{id}',
            read: false,
            provider: InvitationSectionProvider::class
        ),
    ],
)]
final class InvitationSectionResource {}
