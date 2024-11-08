<?php
/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Entity/MccTypeEpreuve.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 24/02/2024 08:51
 */

namespace App\Entity;

use App\Repository\MccTypeEpreuveRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MccTypeEpreuveRepository::class)]
class MccTypeEpreuve extends BaseEntity
{
    #[ORM\Column(length: 255)]
    private ?string $libelle = null;


    #[ORM\ManyToOne]
    private ?AnneeUniversitaire $anneeUniversitaire = null;

    #[ORM\OneToMany(mappedBy: 'typeEpreuve', targetEntity: Mcc::class)]
    private Collection $mccs;

    #[ORM\ManyToOne(inversedBy: 'mccTypeEpreuves')]
    private ?TypeDiplome $type_diplome = null;

    public function __construct()
    {
        $this->mccs = new ArrayCollection();
    }

    public function getAnneeUniversitaire(): ?AnneeUniversitaire
    {
        return $this->anneeUniversitaire;
    }

    public function setAnneeUniversitaire(?AnneeUniversitaire $anneeUniversitaire): self
    {
        $this->anneeUniversitaire = $anneeUniversitaire;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return Collection<int, Mcc>
     */
    public function getMccs(): Collection
    {
        return $this->mccs;
    }

    public function addMcc(Mcc $mcc): self
    {
        if (!$this->mccs->contains($mcc)) {
            $this->mccs->add($mcc);
            $mcc->setTypeEpreuve($this);
        }

        return $this;
    }

    public function removeMcc(Mcc $mcc): self
    {
        // set the owning side to null (unless already changed)
        if ($this->mccs->removeElement($mcc) && $mcc->getTypeEpreuve() === $this) {
            $mcc->setTypeEpreuve(null);
        }

        return $this;
    }

    public function getTypeDiplome(): ?TypeDiplome
    {
        return $this->type_diplome;
    }

    public function setTypeDiplome(?TypeDiplome $type_diplome): self
    {
        $this->type_diplome = $type_diplome;

        return $this;
    }
}
