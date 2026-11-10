<?php

namespace UnifolioBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\AgregationEnum;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Repository\ParametrageFormationRepository;

/**
 * Choix d'une formation BUT (D5) : granularité de rattachement et d'évaluation,
 * grille par défaut, règle d'agrégation. Applicable à partir d'une année universitaire,
 * jusqu'au paramétrage suivant.
 */
#[ORM\Entity(repositoryClass: ParametrageFormationRepository::class)]
#[ORM\Table(name: 'unifolio_parametrage_formation')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_parametrage_diplome_annee', columns: ['diplome_id', 'applicable_depuis_id'])]
class ParametrageFormation implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StructureDiplome $diplome;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StructureAnneeUniversitaire $applicableDepuis;

    #[ORM\Column(length: 20, enumType: GranulariteEnum::class)]
    private GranulariteEnum $granularite;

    #[ORM\Column(length: 20, enumType: AgregationEnum::class, options: ['default' => 'max'])]
    private AgregationEnum $agregation = AgregationEnum::MAX;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Grille $grille = null;

    public function __construct(StructureDiplome $diplome, StructureAnneeUniversitaire $applicableDepuis, GranulariteEnum $granularite)
    {
        if ($diplome->getTypeDiplome()?->isApc() !== true) {
            throw new \InvalidArgumentException('Seuls les diplômes APC (BUT) se paramètrent dans UniFolio.');
        }
        $this->diplome = $diplome;
        $this->applicableDepuis = $applicableDepuis;
        $this->granularite = $granularite;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiplome(): StructureDiplome
    {
        return $this->diplome;
    }

    public function getApplicableDepuis(): StructureAnneeUniversitaire
    {
        return $this->applicableDepuis;
    }

    public function getGranularite(): GranulariteEnum
    {
        return $this->granularite;
    }

    public function setGranularite(GranulariteEnum $granularite): static
    {
        $this->granularite = $granularite;
        if ($this->grille !== null && $this->grille->getGranularite() !== $granularite) {
            $this->grille = null;
        }

        return $this;
    }

    public function getAgregation(): AgregationEnum
    {
        return $this->agregation;
    }

    public function setAgregation(AgregationEnum $agregation): static
    {
        $this->agregation = $agregation;

        return $this;
    }

    public function getGrille(): ?Grille
    {
        return $this->grille;
    }

    public function setGrille(?Grille $grille): static
    {
        if ($grille !== null) {
            if ($grille->getGranularite() !== $this->granularite) {
                throw new \InvalidArgumentException(sprintf(
                    'La grille évalue par « %s » alors que la formation évalue par « %s ».',
                    $grille->getGranularite()->getLibelle(),
                    $this->granularite->getLibelle()
                ));
            }
            if (!$grille->estApplicableA($this->diplome)) {
                throw new \InvalidArgumentException('Cette grille ne s\'applique pas à ce diplôme.');
            }
        }
        $this->grille = $grille;

        return $this;
    }
}
