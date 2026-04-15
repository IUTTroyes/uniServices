<?php

namespace UnifolioBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UnifolioBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}