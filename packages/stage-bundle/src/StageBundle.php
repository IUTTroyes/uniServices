<?php

namespace StageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class StageBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}