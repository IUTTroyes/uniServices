<?php

namespace MessageBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Users\Personnel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use MessageBundle\Repository\MessageRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'message')]
class Message implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['message:read', 'message:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['message:read', 'message:detail'])]
    private ?string $sujet = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['message:read', 'message:detail'])]
    private ?string $contenu = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read', 'message:detail'])]
    private ?Personnel $expediteur = null;

    #[ORM\Column(length: 50)]
    #[Groups(['message:read', 'message:detail'])]
    private string $typeCible = 'INDIVIDUEL'; // 'TOUS', 'PROMO', 'GROUPE', 'INDIVIDUEL'

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['message:read', 'message:detail'])]
    private ?string $cibleLibelle = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['message:read', 'message:detail'])]
    private bool $prioritaire = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['message:read', 'message:detail'])]
    private bool $copieEmail = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['message:detail'])]
    private ?StructureDepartement $departement = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['message:detail'])]
    private ?StructureAnneeUniversitaire $anneeUniversitaire = null;

    /**
     * @var Collection<int, MessageDestinataire>
     */
    #[ORM\OneToMany(targetEntity: MessageDestinataire::class, mappedBy: 'message', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $destinataires;

    /**
     * @var Collection<int, MessagePieceJointe>
     */
    #[ORM\OneToMany(targetEntity: MessagePieceJointe::class, mappedBy: 'message', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['message:detail'])]
    private Collection $piecesJointes;

    public function __construct()
    {
        $this->destinataires = new ArrayCollection();
        $this->piecesJointes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(string $sujet): static
    {
        $this->sujet = $sujet;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getExpediteur(): ?Personnel
    {
        return $this->expediteur;
    }

    public function setExpediteur(?Personnel $expediteur): static
    {
        $this->expediteur = $expediteur;

        return $this;
    }

    public function getTypeCible(): string
    {
        return $this->typeCible;
    }

    public function setTypeCible(string $typeCible): static
    {
        $this->typeCible = $typeCible;

        return $this;
    }

    public function getCibleLibelle(): ?string
    {
        return $this->cibleLibelle;
    }

    public function setCibleLibelle(?string $cibleLibelle): static
    {
        $this->cibleLibelle = $cibleLibelle;

        return $this;
    }

    public function isPrioritaire(): bool
    {
        return $this->prioritaire;
    }

    public function setPrioritaire(bool $prioritaire): static
    {
        $this->prioritaire = $prioritaire;

        return $this;
    }

    public function isCopieEmail(): bool
    {
        return $this->copieEmail;
    }

    public function setCopieEmail(bool $copieEmail): static
    {
        $this->copieEmail = $copieEmail;

        return $this;
    }

    public function getDepartement(): ?StructureDepartement
    {
        return $this->departement;
    }

    public function setDepartement(?StructureDepartement $departement): static
    {
        $this->departement = $departement;

        return $this;
    }

    public function getAnneeUniversitaire(): ?StructureAnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(?StructureAnneeUniversitaire $anneeUniversitaire): static
    {
        $this->anneeUniversitaire = $anneeUniversitaire;

        return $this;
    }

    /**
     * @return Collection<int, MessageDestinataire>
     */
    public function getDestinataires(): Collection
    {
        return $this->destinataires;
    }

    public function addDestinataire(MessageDestinataire $destinataire): static
    {
        if (!$this->destinataires->contains($destinataire)) {
            $this->destinataires->add($destinataire);
            $destinataire->setMessage($this);
        }

        return $this;
    }

    public function removeDestinataire(MessageDestinataire $destinataire): static
    {
        if ($this->destinataires->removeElement($destinataire)) {
            if ($destinataire->getMessage() === $this) {
                $destinataire->setMessage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MessagePieceJointe>
     */
    public function getPiecesJointes(): Collection
    {
        return $this->piecesJointes;
    }

    public function addPieceJointe(MessagePieceJointe $pieceJointe): static
    {
        if (!$this->piecesJointes->contains($pieceJointe)) {
            $this->piecesJointes->add($pieceJointe);
            $pieceJointe->setMessage($this);
        }

        return $this;
    }

    public function removePieceJointe(MessagePieceJointe $pieceJointe): static
    {
        if ($this->piecesJointes->removeElement($pieceJointe)) {
            if ($pieceJointe->getMessage() === $this) {
                $pieceJointe->setMessage(null);
            }
        }

        return $this;
    }
}

