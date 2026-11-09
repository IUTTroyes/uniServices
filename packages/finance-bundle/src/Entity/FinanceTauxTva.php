<?php

namespace FinanceBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use FinanceBundle\Repository\FinanceTauxTvaRepository;
use FinanceBundle\State\FinanceReferentielProcessor;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Taux de TVA proposés à la saisie d'une demande (référentiel de l'établissement, géré par le service financier).
 * Une demande recopie la valeur du taux choisi : modifier ou désactiver un taux ne change pas les demandes existantes.
 * Un taux qui n'a plus cours est désactivé (il n'est plus proposé) plutôt que supprimé.
 */
#[ORM\Entity(repositoryClass: FinanceTauxTvaRepository::class)]
#[UniqueEntity(fields: ['taux'], message: 'Ce taux existe déjà.')]
#[ApiResource(
    operations: [
        new GetCollection(
            order: ['ordre' => 'ASC', 'taux' => 'DESC'],
            security: "is_granted('FINANCE_ACCES')",
            paginationClientItemsPerPage: true,
            paginationMaximumItemsPerPage: 100,
        ),
        new Get(security: "is_granted('FINANCE_ACCES')"),
        new Post(security: "is_granted('FINANCE_REFERENTIEL_GERER')", processor: FinanceReferentielProcessor::class),
        new Patch(security: "is_granted('FINANCE_REFERENTIEL_GERER')", processor: FinanceReferentielProcessor::class),
        new Delete(security: "is_granted('FINANCE_REFERENTIEL_GERER')"),
    ],
    normalizationContext: ['groups' => ['taux_tva:read']],
    denormalizationContext: ['groups' => ['taux_tva:write']],
)]
class FinanceTauxTva
{
    /** Taux en vigueur en France (taux normal, intermédiaire, réduit, particulier, exonéré). */
    public const TAUX_PAR_DEFAUT = [
        ['taux' => '20.00', 'libelle' => 'Taux normal (20 %)', 'parDefaut' => true],
        ['taux' => '10.00', 'libelle' => 'Taux intermédiaire (10 %)', 'parDefaut' => false],
        ['taux' => '5.50', 'libelle' => 'Taux réduit (5,5 %)', 'parDefaut' => false],
        ['taux' => '2.10', 'libelle' => 'Taux particulier (2,1 %)', 'parDefaut' => false],
        ['taux' => '0.00', 'libelle' => 'Exonéré / non assujetti (0 %)', 'parDefaut' => false],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['taux_tva:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, unique: true)]
    #[Groups(['taux_tva:read', 'taux_tva:write'])]
    #[Assert\NotBlank]
    #[Assert\Range(notInRangeMessage: 'Le taux doit être compris entre 0 et 100 %.', min: 0, max: 100)]
    private ?string $taux = null;

    #[ORM\Column(length: 100)]
    #[Groups(['taux_tva:read', 'taux_tva:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $libelle = null;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['taux_tva:read', 'taux_tva:write'])]
    private bool $actif = true;

    /** Taux proposé par défaut pour une nouvelle demande. */
    #[ORM\Column(options: ['default' => false])]
    #[Groups(['taux_tva:read', 'taux_tva:write'])]
    private bool $parDefaut = false;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['taux_tva:read', 'taux_tva:write'])]
    private int $ordre = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaux(): ?string
    {
        return $this->taux;
    }

    public function setTaux(string|int|float|null $taux): static
    {
        $this->taux = $taux === null || $taux === '' ? null : number_format((float) $taux, 2, '.', '');

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = trim($libelle);

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function isParDefaut(): bool
    {
        return $this->parDefaut;
    }

    public function setParDefaut(bool $parDefaut): static
    {
        $this->parDefaut = $parDefaut;

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
