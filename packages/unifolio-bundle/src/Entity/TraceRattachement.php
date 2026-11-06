<?php

namespace UnifolioBundle\Entity;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\GranulariteEnum;

/**
 * Lien entre une trace et UN élément du référentiel APC (compétence, niveau ou AC),
 * avec l'argumentaire de l'étudiant. Exactement une des trois cibles est renseignée
 * (contrainte CHECK en base, garantie par les constructeurs nommés).
 */
#[ORM\Entity]
#[ORM\Table(name: 'unifolio_trace_rattachement')]
class TraceRattachement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'rattachements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Trace $trace;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ApcCompetence $competence = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ApcNiveau $niveau = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ApcApprentissageCritique $apprentissageCritique = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $argumentaire = null;

    #[ORM\Column]
    private int $ordre = 0;

    private function __construct(Trace $trace, ?string $argumentaire)
    {
        $this->trace = $trace;
        $this->setArgumentaire($argumentaire);
    }

    public static function versCompetence(Trace $trace, ApcCompetence $competence, ?string $argumentaire = null): self
    {
        $r = new self($trace, $argumentaire);
        $r->competence = $competence;

        return $r;
    }

    public static function versNiveau(Trace $trace, ApcNiveau $niveau, ?string $argumentaire = null): self
    {
        $r = new self($trace, $argumentaire);
        $r->niveau = $niveau;

        return $r;
    }

    public static function versApprentissageCritique(Trace $trace, ApcApprentissageCritique $ac, ?string $argumentaire = null): self
    {
        $r = new self($trace, $argumentaire);
        $r->apprentissageCritique = $ac;

        return $r;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrace(): Trace
    {
        return $this->trace;
    }

    public function getGranularite(): GranulariteEnum
    {
        return match (true) {
            $this->apprentissageCritique !== null => GranulariteEnum::AC,
            $this->niveau !== null => GranulariteEnum::NIVEAU,
            default => GranulariteEnum::COMPETENCE,
        };
    }

    public function getCible(): ApcCompetence|ApcNiveau|ApcApprentissageCritique
    {
        return $this->apprentissageCritique ?? $this->niveau ?? $this->competence
            ?? throw new \LogicException('Rattachement sans cible.');
    }

    /** Identifie la cible indépendamment de l'instance, ex. "ac:42". */
    public function getCleCible(): string
    {
        return $this->getGranularite()->value . ':' . (string) ($this->getCible()->getId() ?? spl_object_id($this->getCible()));
    }

    /** Compétence de rattachement, quelle que soit la granularité. */
    public function getCompetenceParente(): ?ApcCompetence
    {
        return $this->competence
            ?? $this->niveau?->getCompetence()
            ?? $this->apprentissageCritique?->getNiveau()?->getCompetence();
    }

    public function getCompetence(): ?ApcCompetence
    {
        return $this->competence;
    }

    public function getNiveau(): ?ApcNiveau
    {
        return $this->niveau;
    }

    public function getApprentissageCritique(): ?ApcApprentissageCritique
    {
        return $this->apprentissageCritique;
    }

    public function getArgumentaire(): ?string
    {
        return $this->argumentaire;
    }

    public function setArgumentaire(?string $argumentaire): static
    {
        $this->argumentaire = $argumentaire !== null && trim($argumentaire) !== '' ? $argumentaire : null;

        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }
}
