<?php

namespace App\Domain\Dashboard;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;

interface WidgetDataProviderInterface
{
    public function supports(string $code): bool;

    public function getData(string $code, Personnel|Etudiant $user): array;
}
