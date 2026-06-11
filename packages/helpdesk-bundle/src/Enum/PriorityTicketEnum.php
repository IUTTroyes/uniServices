<?php

namespace HelpdeskBundle\Enum;

enum PriorityTicketEnum: string
{
    case BASSE= 'Basse';
    case MOYENNE = 'Moyenne';
    case HAUTE = 'Haute';
    case CRITIQUE='Critique';

    public function getPriorities(): array
    {
        return [
            self::BASSE,
            self::MOYENNE,
            self::HAUTE,
            self::CRITIQUE,

        ];
    }
}
