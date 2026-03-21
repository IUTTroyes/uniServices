<?php

namespace IUTTroyes\IntranetBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class IntranetBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
