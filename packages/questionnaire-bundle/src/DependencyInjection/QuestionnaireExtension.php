<?php

namespace QuestionnaireBundle\DependencyInjection;

use App\Extension\AbstractPackageExtension;

class QuestionnaireExtension extends AbstractPackageExtension
{
    protected function espaceTwig(): ?string
    {
        return 'Questionnaire';
    }
}
