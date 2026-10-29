<?php

namespace App\Entity\Traits;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait OptionTrait
{
    #[ORM\Column]
    #[Groups([
        'diplome:read:full', 'diplome:read', 'diplome:detail', 'diplome:write',
        'scolarite:read', 'semestre:detail', 'semestre:read', 'semestre:write',
        'annee:read', 'annee:write',
        'departement:read', 'departement:write', 'departement:administration',
        'etudiant:read', 'etudiant:detail', 'etudiant:write',
        'questionnaire:read', 'questionnaire_section:read'
    ])]
    private array $opt = [];

    public function setOpt(array $opt): static
    {
        $resolver = new OptionsResolver();
        $resolver->setIgnoreUndefined(true);
        $this->configureOptions($resolver);
        $this->opt = $resolver->resolve(array_merge($this->opt, $opt));

        return $this;
    }

    public function setOption(string $key, mixed $value): static
    {
        return $this->setOpt([$key => $value]);
    }

    public function getOpt(): array
    {
        return $this->opt;
    }
}
