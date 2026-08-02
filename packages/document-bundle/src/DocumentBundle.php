<?php

namespace DocumentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class DocumentBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
