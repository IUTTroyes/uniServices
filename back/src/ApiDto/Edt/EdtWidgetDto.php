<?php

namespace App\ApiDto\Edt;

use Symfony\Component\Serializer\Annotation\Groups;

class EdtWidgetDto
{
    #[Groups(['edt_widget:read'])]
    public string $todayLabel = '';

    /**
     * @var array<array{heure: string, type: string, cours: string, salle: string, color: string}>
     */
    #[Groups(['edt_widget:read'])]
    public array $items = [];
}
