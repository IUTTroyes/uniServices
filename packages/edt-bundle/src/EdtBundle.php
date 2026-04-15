<?php

namespace EdtBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class EdtBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
