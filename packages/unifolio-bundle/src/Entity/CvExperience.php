<?php

namespace UnifolioBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'unifolio_cv_experience')]
class CvExperience
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'experiences')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Cv $cv;

    #[ORM\Column(length: 255)]
    private string $poste;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $entreprise = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $ordre = 0;

    public function __construct(Cv $cv, string $poste, ?string $entreprise = null, ?\DateTimeInterface $dateDebut = null, ?\DateTimeInterface $dateFin = null, ?string $description = null)
    {
        $poste = trim($poste);
        if ($poste === '') {
            throw new \InvalidArgumentException('Chaque expérience doit avoir un intitulé de poste.');
        }
        if ($dateDebut !== null && $dateFin !== null && $dateFin < $dateDebut) {
            throw new \InvalidArgumentException(sprintf('Expérience « %s » : la date de fin précède la date de début.', $poste));
        }
        $this->cv = $cv;
        $this->poste = mb_substr($poste, 0, 255);
        $this->entreprise = $entreprise !== null && trim($entreprise) !== '' ? mb_substr(trim($entreprise), 0, 255) : null;
        $this->dateDebut = $dateDebut !== null ? \DateTime::createFromInterface($dateDebut) : null;
        $this->dateFin = $dateFin !== null ? \DateTime::createFromInterface($dateFin) : null;
        $this->description = $description !== null && trim($description) !== '' ? mb_substr(trim($description), 0, 2000) : null;
    }

    public function getPoste(): string
    {
        return $this->poste;
    }

    public function getEntreprise(): ?string
    {
        return $this->entreprise;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut !== null ? \DateTimeImmutable::createFromInterface($this->dateDebut) : null;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin !== null ? \DateTimeImmutable::createFromInterface($this->dateFin) : null;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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
