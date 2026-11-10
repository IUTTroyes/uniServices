<?php

namespace UnifolioBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\SourceCritereEnum;
use UnifolioBundle\Enum\TypeCritereEnum;

#[ORM\Entity]
#[ORM\Table(name: 'unifolio_grille_critere')]
class GrilleCritere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'criteres')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GrilleVersion $version;

    #[ORM\Column(length: 255)]
    private string $libelle;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Indicateurs observables, affichés à l'évaluateur. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aide = null;

    #[ORM\Column(length: 20, enumType: TypeCritereEnum::class)]
    private TypeCritereEnum $type;

    #[ORM\Column(length: 20, enumType: SourceCritereEnum::class, options: ['default' => 'saisie'])]
    private SourceCritereEnum $source = SourceCritereEnum::SAISIE;

    #[ORM\Column(type: Types::FLOAT, options: ['default' => 1])]
    private float $poids = 1.0;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $noteMax = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $obligatoire = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $visibleEtudiant = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $utiliseAutoEval = true;

    #[ORM\Column]
    private int $ordre = 0;

    public function __construct(GrilleVersion $version, string $libelle, TypeCritereEnum $type)
    {
        $this->version = $version;
        $this->setLibelle($libelle);
        $this->setType($type);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): GrilleVersion
    {
        return $this->version;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $libelle = trim($libelle);
        if ($libelle === '') {
            throw new \InvalidArgumentException('Chaque critère doit avoir un libellé.');
        }
        $this->libelle = mb_substr($libelle, 0, 255);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $this->texte($description);

        return $this;
    }

    public function getAide(): ?string
    {
        return $this->aide;
    }

    public function setAide(?string $aide): static
    {
        $this->aide = $this->texte($aide);

        return $this;
    }

    public function getType(): TypeCritereEnum
    {
        return $this->type;
    }

    public function setType(TypeCritereEnum $type): static
    {
        $this->type = $type;
        if ($type !== TypeCritereEnum::NOTE) {
            $this->noteMax = null;
        }
        if ($type === TypeCritereEnum::TEXTE) {
            $this->poids = 0.0;
            $this->obligatoire = false;
        }

        return $this;
    }

    public function getSource(): SourceCritereEnum
    {
        return $this->source;
    }

    public function setSource(SourceCritereEnum $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getPoids(): float
    {
        return $this->poids;
    }

    public function setPoids(float $poids): static
    {
        if ($poids < 0 || $poids > 100) {
            throw new \InvalidArgumentException('Le poids d\'un critère doit être compris entre 0 et 100.');
        }
        $this->poids = $this->type === TypeCritereEnum::TEXTE ? 0.0 : $poids;

        return $this;
    }

    public function getNoteMax(): ?float
    {
        return $this->noteMax;
    }

    public function setNoteMax(?float $noteMax): static
    {
        $this->noteMax = $this->type === TypeCritereEnum::NOTE ? $noteMax : null;

        return $this;
    }

    /** Une appréciation libre (critère texte) ne bloque jamais la remise : elle reste facultative. */
    public function isObligatoire(): bool
    {
        return $this->obligatoire && $this->type !== TypeCritereEnum::TEXTE;
    }

    public function setObligatoire(bool $obligatoire): static
    {
        $this->obligatoire = $obligatoire;

        return $this;
    }

    public function isVisibleEtudiant(): bool
    {
        return $this->visibleEtudiant;
    }

    public function setVisibleEtudiant(bool $visibleEtudiant): static
    {
        $this->visibleEtudiant = $visibleEtudiant;

        return $this;
    }

    public function isUtiliseAutoEval(): bool
    {
        return $this->utiliseAutoEval;
    }

    public function setUtiliseAutoEval(bool $utiliseAutoEval): static
    {
        $this->utiliseAutoEval = $utiliseAutoEval;

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

    public function copierVers(self $cible): void
    {
        $cible->setDescription($this->description)
            ->setAide($this->aide)
            ->setSource($this->source)
            ->setPoids($this->poids)
            ->setNoteMax($this->noteMax)
            ->setObligatoire($this->obligatoire)
            ->setVisibleEtudiant($this->visibleEtudiant)
            ->setUtiliseAutoEval($this->utiliseAutoEval);
    }

    private function texte(?string $valeur): ?string
    {
        return $valeur !== null && trim($valeur) !== '' ? $valeur : null;
    }
}
