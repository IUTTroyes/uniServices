<?php

namespace HelpdeskBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class HelpdeskBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}