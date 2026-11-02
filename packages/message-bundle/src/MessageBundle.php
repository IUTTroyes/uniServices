<?php

namespace MessageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MessageBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}

