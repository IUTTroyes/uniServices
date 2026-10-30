<?php

namespace MessageBundle\Entity;

use App\Entity\Contracts\TimestampableInterface;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use MessageBundle\Repository\MessageDestinataireRepository;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MessageDestinataireRepository::class)]
#[ORM\Table(name: 'message_destinataire')]
#[ORM\Index(name: 'idx_message_dest_user', columns: ['etudiant_id', 'personnel_id', 'is_lu'])]
class MessageDestinataire implements TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['message:read', 'message:detail'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'destinataires')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Message $message = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['message:detail'])]
    private ?Etudiant $etudiant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['message:detail'])]
    private ?Personnel $personnel = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['message:read', 'message:detail'])]
    private bool $isLu = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['message:read', 'message:detail'])]
    private ?\DateTimeImmutable $dateLecture = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['message:read', 'message:detail'])]
    private bool $isArchive = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['message:read', 'message:detail'])]
    private bool $isCopie = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getEtudiant(): ?Etudiant
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Etudiant $etudiant): static
    {
        $this->etudiant = $etudiant;

        return $this;
    }

    public function getPersonnel(): ?Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function isLu(): bool
    {
        return $this->isLu;
    }

    public function setIsLu(bool $isLu): static
    {
        $this->isLu = $isLu;
        if ($isLu && null === $this->dateLecture) {
            $this->dateLecture = new \DateTimeImmutable();
        } elseif (!$isLu) {
            $this->dateLecture = null;
        }

        return $this;
    }

    public function getDateLecture(): ?\DateTimeImmutable
    {
        return $this->dateLecture;
    }

    public function setDateLecture(?\DateTimeImmutable $dateLecture): static
    {
        $this->dateLecture = $dateLecture;

        return $this;
    }

    public function isArchive(): bool
    {
        return $this->isArchive;
    }

    public function setIsArchive(bool $isArchive): static
    {
        $this->isArchive = $isArchive;

        return $this;
    }

    public function isCopie(): bool
    {
        return $this->isCopie;
    }

    public function setIsCopie(bool $isCopie): static
    {
        $this->isCopie = $isCopie;

        return $this;
    }
}

