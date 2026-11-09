<?php

namespace UnifolioBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UnifolioBundle\Enum\TypeCritereEnum;

/**
 * Contenu d'une grille à un instant donné : une échelle commune et des critères.
 * Brouillon tant qu'elle n'est pas publiée ; figée ensuite (les évaluations y font référence).
 */
#[ORM\Entity]
#[ORM\Table(name: 'unifolio_grille_version')]
#[ORM\UniqueConstraint(name: 'uniq_unifolio_grille_version_numero', columns: ['grille_id', 'numero'])]
class GrilleVersion
{
    public const ECHELLE_PAR_DEFAUT = [
        ['Non acquis', 0, '#b91c1c', 'Les attendus ne sont pas démontrés.'],
        ['En cours d\'acquisition', 1, '#d97706', 'Les attendus sont partiellement démontrés.'],
        ['Acquis', 2, '#16a34a', 'Les attendus sont démontrés.'],
        ['Maîtrisé', 3, '#166534', 'Les attendus sont démontrés avec autonomie et recul.'],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'versions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Grille $grille;

    #[ORM\Column]
    private int $numero;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publieeLe = null;

    /** @var Collection<int, GrilleEchelon> */
    #[ORM\OneToMany(targetEntity: GrilleEchelon::class, mappedBy: 'version', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $echelons;

    /** @var Collection<int, GrilleCritere> */
    #[ORM\OneToMany(targetEntity: GrilleCritere::class, mappedBy: 'version', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $criteres;

    public function __construct(Grille $grille, int $numero)
    {
        $this->grille = $grille;
        $this->numero = $numero;
        $this->echelons = new ArrayCollection();
        $this->criteres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGrille(): Grille
    {
        return $this->grille;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function getPublieeLe(): ?\DateTimeImmutable
    {
        return $this->publieeLe;
    }

    public function isPubliee(): bool
    {
        return $this->publieeLe !== null;
    }

    /** @return Collection<int, GrilleEchelon> */
    public function getEchelons(): Collection
    {
        return $this->echelons;
    }

    /** @return Collection<int, GrilleCritere> */
    public function getCriteres(): Collection
    {
        return $this->criteres;
    }

    public function ajouterEchelon(string $libelle, float $valeur, ?string $couleur = null, ?string $descripteur = null): GrilleEchelon
    {
        $this->assertModifiable();
        $echelon = new GrilleEchelon($this, $libelle, $valeur, $couleur, $descripteur);
        $echelon->setOrdre($this->echelons->count());
        $this->echelons->add($echelon);

        return $echelon;
    }

    public function ajouterCritere(string $libelle, TypeCritereEnum $type): GrilleCritere
    {
        $this->assertModifiable();
        $critere = new GrilleCritere($this, $libelle, $type);
        $critere->setOrdre($this->criteres->count());
        $this->criteres->add($critere);

        return $critere;
    }

    public function viderContenu(): void
    {
        $this->assertModifiable();
        $this->echelons->clear();
        $this->criteres->clear();
    }

    public function appliquerEchelleParDefaut(): void
    {
        $this->assertModifiable();
        $this->echelons->clear();
        foreach (self::ECHELLE_PAR_DEFAUT as [$libelle, $valeur, $couleur, $descripteur]) {
            $this->ajouterEchelon($libelle, $valeur, $couleur, $descripteur);
        }
    }

    public function copierContenuVers(self $cible): void
    {
        $cible->viderContenu();
        foreach ($this->echelons as $e) {
            $cible->ajouterEchelon($e->getLibelle(), $e->getValeur(), $e->getCouleur(), $e->getDescripteur());
        }
        foreach ($this->criteres as $c) {
            $c->copierVers($cible->ajouterCritere($c->getLibelle(), $c->getType()));
        }
    }

    /** @return string[] */
    public function getErreursPublication(): array
    {
        $erreurs = [];
        if ($this->criteres->isEmpty()) {
            $erreurs[] = 'Ajoutez au moins un critère.';
        }

        $echelle = false;
        $poids = 0.0;
        foreach ($this->criteres as $critere) {
            if ($critere->getType() === TypeCritereEnum::ECHELLE) {
                $echelle = true;
            }
            if ($critere->getType() === TypeCritereEnum::NOTE && ($critere->getNoteMax() ?? 0) <= 0) {
                $erreurs[] = sprintf('Le critère « %s » doit avoir une note maximale positive.', $critere->getLibelle());
            }
            if ($critere->getType()->isQuantifiable()) {
                $poids += $critere->getPoids();
            }
        }

        if ($echelle) {
            $valeurs = array_map(static fn (GrilleEchelon $e) => $e->getValeur(), $this->echelons->toArray());
            if (\count($valeurs) < 2) {
                $erreurs[] = 'L\'échelle doit comporter au moins deux niveaux.';
            } elseif (\count(array_unique($valeurs, SORT_REGULAR)) !== \count($valeurs)) {
                $erreurs[] = 'Chaque niveau de l\'échelle doit avoir une valeur différente.';
            }
        }
        if (!$this->criteres->isEmpty() && $poids <= 0) {
            $erreurs[] = 'Au moins un critère évalué (hors appréciation libre) doit avoir un poids positif.';
        }

        return $erreurs;
    }

    public function publier(?\DateTimeImmutable $le = null): void
    {
        $this->assertModifiable();
        $erreurs = $this->getErreursPublication();
        if ($erreurs !== []) {
            throw new \DomainException(implode(' ', $erreurs));
        }
        $this->publieeLe = $le ?? new \DateTimeImmutable();
    }

    public function assertModifiable(): void
    {
        if ($this->isPubliee()) {
            throw new \DomainException('Une version publiée ne peut plus être modifiée : créez une nouvelle version.');
        }
    }
}
