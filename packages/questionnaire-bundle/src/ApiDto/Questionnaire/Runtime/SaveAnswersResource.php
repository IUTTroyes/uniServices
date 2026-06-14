<?php

namespace QuestionnaireBundle\ApiDto\Questionnaire\Runtime;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use QuestionnaireBundle\State\Questionnaire\Runtime\SaveAnswersProcessor;

#[ApiResource(
    operations: [
        new Patch(
            uriTemplate: '/invitations/{token}/answers',
            input: SaveAnswersInput::class,
            output: SaveAnswersOutput::class,
            read: false,
            processor: SaveAnswersProcessor::class
        ),
    ],
)]
final class SaveAnswersResource {}
