<?php

namespace FinanceBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FinanceBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
