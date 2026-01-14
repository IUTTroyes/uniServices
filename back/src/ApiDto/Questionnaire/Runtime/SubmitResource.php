<?php

namespace App\ApiDto\Questionnaire\Runtime;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\Questionnaire\Runtime\SubmitProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/invitations/{token}/submit',
            input: false,
            output: SubmitOutput::class,
            read: false,
            deserialize: false,
            processor: SubmitProcessor::class
        ),
    ],
)]
final class SubmitResource {}
