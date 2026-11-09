<?php

namespace UnifolioBundle\Service\Evaluation;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\GranulariteEnum;

/** Un point à évaluer dans un lot : un rattachement de trace, ou un élément du référentiel (bilan). */
final readonly class CibleLot
{
    public function __construct(
        public string $cle,
        public GranulariteEnum $granularite,
        public ApcCompetence|ApcNiveau|ApcApprentissageCritique $cible,
        public ?TraceRattachement $rattachement = null,
    ) {
    }

    public static function pourRattachement(TraceRattachement $r): self
    {
        return new self('r:' . $r->getId(), $r->getGranularite(), $r->getCible(), $r);
    }

    public static function pourElement(GranulariteEnum $granularite, ApcCompetence|ApcNiveau|ApcApprentissageCritique $cible): self
    {
        return new self($granularite->value . ':' . $cible->getId(), $granularite, $cible);
    }

    public function libelle(): string
    {
        return trim(($this->cible instanceof ApcApprentissageCritique ? $this->cible->getCode() . ' ' : '') . $this->cible->getLibelle());
    }

    public function competence(): ?ApcCompetence
    {
        return match (true) {
            $this->rattachement !== null => $this->rattachement->getCompetenceParente(),
            $this->cible instanceof ApcCompetence => $this->cible,
            $this->cible instanceof ApcNiveau => $this->cible->getCompetence(),
            default => $this->cible->getNiveau()?->getCompetence(),
        };
    }
}
