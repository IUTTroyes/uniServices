<?php

namespace IntranetBundle\Commission\Exception;

class CommissionNotSupportedException extends \RuntimeException
{
    public static function forSemestre(?int $semestreId): self
    {
        return new self(sprintf('Aucune stratégie de commission disponible pour le semestre #%s.', $semestreId ?? '?'));
    }
}
