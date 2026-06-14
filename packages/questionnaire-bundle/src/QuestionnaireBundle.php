<?php

namespace QuestionnaireBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class QuestionnaireBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}