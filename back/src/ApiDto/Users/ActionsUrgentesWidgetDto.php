<?php

namespace App\ApiDto\Users;

use Symfony\Component\Serializer\Annotation\Groups;

class ActionsUrgentesWidgetDto
{
    /**
     * @var array<array{icon: string, titre: string, detail: string, cta: string, color: string}>
     */
    #[Groups(['Personnel_widget:read'])]
    public array $items = [];
}
