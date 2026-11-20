<?php

namespace ParcautoBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ParcautoBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}