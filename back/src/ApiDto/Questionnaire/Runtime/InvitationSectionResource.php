<?php

namespace App\ApiDto\Questionnaire\Runtime;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Questionnaire\Runtime\InvitationSectionProvider;

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
