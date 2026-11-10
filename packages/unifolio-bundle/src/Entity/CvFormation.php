<?php

namespace UnifolioBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'unifolio_cv_formation')]
class CvFormation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Cv $cv;

    #[ORM\Column(length: 255)]
    private string $diplome;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etablissement = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private int $ordre = 0;

    public function __construct(Cv $cv, string $diplome, ?string $etablissement = null, ?\DateTimeInterface $dateDebut = null, ?\DateTimeInterface $dateFin = null, ?string $description = null)
    {
        $diplome = trim($diplome);
        if ($diplome === '') {
            throw new \InvalidArgumentException('Chaque formation doit avoir un intitulé.');
        }
        if ($dateDebut !== null && $dateFin !== null && $dateFin < $dateDebut) {
            throw new \InvalidArgumentException(sprintf('Formation « %s » : la date de fin précède la date de début.', $diplome));
        }
        $this->cv = $cv;
        $this->diplome = mb_substr($diplome, 0, 255);
        $this->etablissement = $etablissement !== null && trim($etablissement) !== '' ? mb_substr(trim($etablissement), 0, 255) : null;
        $this->dateDebut = $dateDebut !== null ? \DateTime::createFromInterface($dateDebut) : null;
        $this->dateFin = $dateFin !== null ? \DateTime::createFromInterface($dateFin) : null;
        $this->description = $description !== null && trim($description) !== '' ? mb_substr(trim($description), 0, 2000) : null;
    }

    public function getDiplome(): string
    {
        return $this->diplome;
    }

    public function getEtablissement(): ?string
    {
        return $this->etablissement;
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
