<?php

namespace UnifolioBundle\Enum;

enum TypePortfolioEnum: string
{
    /** Un par année de BUT, évalué, figé à la clôture de la campagne. */
    case ACADEMIQUE = 'academique';
    /** Un par étudiant, libre, publiable. */
    case VITRINE = 'vitrine';

    public function getLibelle(): string
    {
        return match ($this) {
            self::ACADEMIQUE => 'Portfolio académique',
            self::VITRINE => 'Portfolio vitrine',
        };
    }

    public function isEvaluable(): bool
    {
        return $this === self::ACADEMIQUE;
    }

    /** @return VisibilitePortfolioEnum[] */
    public function visibilitesAutorisees(): array
    {
        return match ($this) {
            self::ACADEMIQUE => [VisibilitePortfolioEnum::PRIVE, VisibilitePortfolioEnum::ETABLISSEMENT],
            self::VITRINE => VisibilitePortfolioEnum::cases(),
        };
    }

    public function autoriseVisibilite(VisibilitePortfolioEnum $visibilite): bool
    {
        return \in_array($visibilite, $this->visibilitesAutorisees(), true);
    }
}
