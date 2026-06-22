<?php

namespace App\ApiDto\Structure;

use Symfony\Component\Serializer\Annotation\Groups;

class DocumentsRecentsWidgetDto
{
    /**
     * @var array<array{icon: string, titre: string, date: string}>
     */
    #[Groups(['StructureDepartement_widget:read'])]
    public array $items = [];
}
