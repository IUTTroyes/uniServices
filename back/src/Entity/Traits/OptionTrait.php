<?php

namespace App\Entity\Traits;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait OptionTrait
{
    #[ORM\Column]
    #[Groups(['diplome:read:full', 'diplome:read', 'scolarite:read', 'semestre:read', 'annee:read', 'etudiant:read'])]
    private array $opt = [];

    public function setOpt(array $opt): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->opt = $resolver->resolve($opt);

        return $this;
    }

    public function getOpt(): array
    {
        return $this->opt;
    }
}
