<?php
/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Entity/QuestChoix.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 16/01/2024 08:06
 */

namespace App\Entity;

use App\Entity\Traits\LifeCycleTrait;
use App\Repository\QuestChoixRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestChoixRepository::class)]
#[ORM\HasLifecycleCallbacks]
class QuestChoix extends BaseEntity
{
    use LifeCycleTrait;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $valeur = null;

    #[ORM\Column(length: 20)]
    private ?string $typeDestinataire = null;

    #[ORM\Column]
    private ?int $idQuestChoix = null;

    #[ORM\ManyToOne(inversedBy: 'questChoixes')]
    private ?QuestQuestion $question = null;

    #[ORM\Column(length: 100)]
    private ?string $cleReponse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $complement = null;

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): self
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getTypeDestinataire(): ?string
    {
        return $this->typeDestinataire;
    }

    public function setTypeDestinataire(string $type_destinataire): self
    {
        $this->typeDestinataire = $type_destinataire;

        return $this;
    }

    public function getIdQuestChoix(): ?int
    {
        return $this->idQuestChoix;
    }

    public function setIdQuestChoix(int $idQuestChoix): self
    {
        $this->idQuestChoix = $idQuestChoix;

        return $this;
    }

    public function getQuestion(): ?QuestQuestion
    {
        return $this->question;
    }

    public function setQuestion(?QuestQuestion $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getCleReponse(): ?string
    {
        return $this->cleReponse;
    }

    public function setCleReponse(string $cleReponse): self
    {
        $this->cleReponse = $cleReponse;

        return $this;
    }

    public function getComplement(): ?string
    {
        return $this->complement;
    }

    public function setComplement(?string $complement): self
    {
        $this->complement = $complement;

        return $this;
    }

    public function getCleTypeDestinataire(): string
    {
        return $this->getTypeDestinataire() . '_' . $this->getIdQuestChoix();
    }
}
