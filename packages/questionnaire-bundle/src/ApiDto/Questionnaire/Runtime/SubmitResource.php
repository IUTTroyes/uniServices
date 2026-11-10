<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use QuestionnaireBundle\State\Provider\Questionnaire\Runtime\SubmitProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/invitations/{token}/submit',
            security: "is_granted(\'PUBLIC_ACCESS\')",
            input: false,
            output: SubmitOutput::class,
            read: false,
            deserialize: false,
            processor: SubmitProcessor::class
        ),
    ],
)]
final class SubmitResource {}
