<?php

namespace FormationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FormationBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}