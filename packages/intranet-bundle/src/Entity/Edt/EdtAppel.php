<?php

namespace IntranetBundle\Entity\Edt;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Edt\EdtEvent;
use App\Entity\Traits\LifeCycleTrait;
use IntranetBundle\Repository\Edt\EdtAppelRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EdtAppelRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['pointage:read']],
        ),
        new GetCollection(
            uriTemplate: '/stats/suivi_pointage',
            normalizationContext: ['groups' => ['pointage:stats:read']],
        ),
    ],
)]
class EdtAppel
{
    use LifeCycleTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['edt_pointage:read'])]
    private ?bool $etat = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?EdtEvent $edtEvent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isEtat(): ?bool
    {
        return $this->etat;
    }

    public function setEtat(bool $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getEdtEvent(): ?EdtEvent
    {
        return $this->edtEvent;
    }

    public function setEdtEvent(?EdtEvent $edtEvent): static
    {
        $this->edtEvent = $edtEvent;

        return $this;
    }
}
