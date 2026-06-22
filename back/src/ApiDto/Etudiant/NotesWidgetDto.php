<?php

namespace App\ApiDto\Etudiant;

use Symfony\Component\Serializer\Annotation\Groups;

class NotesWidgetDto
{
    /**
     * @var array<array{semestre: string, titre: string, completion: string, color: string, date: string}>
     */
    #[Groups(['EtudiantNote_widget:read'])]
    public array $items = [];
}
