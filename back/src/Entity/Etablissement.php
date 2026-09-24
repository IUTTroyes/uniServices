<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\EtablissementRepository;
use App\State\Processor\EtablissementProcessor;
use App\State\Provider\EtablissementProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: EtablissementRepository::class)]
#[UniqueEntity(fields: ['isMain'], message: 'Il ne peut y avoir qu\'un seul établissement principal.')]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['etablissement:read']],
            provider: EtablissementProvider::class,
            processor: EtablissementProcessor::class
        ),
        new Patch(
            inputFormats: [
                'json' => ['application/merge-patch+json']
            ],
            denormalizationContext: ['groups' => ['etablissement:write']],
            securityPostDenormalize: "is_granted('CAN_EDIT_ETABLISSEMENT', object)",
            processor: EtablissementProcessor::class
        ),
        new Post(
            uriTemplate: '/etablissements/{id}/logo',
            inputFormats: [
                'multipart' => ['multipart/form-data']
            ],
            security: "is_granted('CAN_EDIT_ETABLISSEMENT', object)",
            deserialize: false,
            processor: EtablissementProcessor::class
        ),
        new Post(
            denormalizationContext: ['groups' => ['etablissement:write']],
            securityPostDenormalize: "is_granted('CAN_EDIT_ETABLISSEMENT', object)",
            processor: EtablissementProcessor::class
        )

    ],
)]
class Etablissement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['etablissement:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?string $libelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?string $logo_name = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?array $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?string $site_web = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isMain = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['etablissement:read', 'etablissement:write'])]
    private ?array $settings = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getLogoName(): ?string
    {
        return $this->logo_name;
    }

    public function setLogoName(?string $logo_name): static
    {
        $this->logo_name = $logo_name;

        return $this;
    }

    public function getAdresse(): ?array
    {
        return $this->adresse;
    }

    public function setAdresse(?array $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->site_web;
    }

    public function setSiteWeb(?string $site_web): static
    {
        $this->site_web = $site_web;

        return $this;
    }

    public function getIsMain(): ?bool
    {
        return $this->isMain;
    }

    public function setIsMain(?bool $isMain): void
    {
        $this->isMain = $isMain;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getSettings(): array
    {
        return $this->resolveSettings($this->settings ?? []);
    }

    public function setSettings(?array $settings): static
    {
        $this->settings = $this->resolveSettings($settings ?? []);

        return $this;
    }

    private function resolveSettings(array $settings): array
    {
        $defaults = $this->defaultSettings();

        $resolver = new OptionsResolver();
        $resolver->setDefaults($defaults);
        $resolver->setAllowedTypes('integrations', 'array');
        $resolver->setNormalizer('integrations', function ($options, array $integrations): array {
            $integrationsResolver = new OptionsResolver();
            $integrationsResolver->setDefaults([
                'edusign' => [
                    'enabled' => false,
                    'scope' => [],
                    'apiKey' => null,
                    'apiUrl' => null,
                ],
                'orebut' => [
                    'enabled' => false,
                    'apiUrl' => null,
                ],
            ]);
            $integrationsResolver->setAllowedTypes('edusign', 'array');
            $integrationsResolver->setAllowedTypes('orebut', 'array');
            $resolvedIntegrations = $integrationsResolver->resolve($integrations);

            $edusignResolver = new OptionsResolver();
            $edusignResolver->setDefaults([
                'enabled' => false,
                'scope' => [],
                'apiKey' => null,
                'apiUrl' => null,
            ]);
            $edusignResolver->setAllowedTypes('enabled', 'bool');
            $edusignResolver->setAllowedTypes('scope', 'array');
            $edusignResolver->setAllowedTypes('apiKey', ['null', 'string']);
            $edusignResolver->setAllowedTypes('apiUrl', ['null', 'string']);

            $orebutResolver = new OptionsResolver();
            $orebutResolver->setDefaults([
                'enabled' => false,
                'apiKey' => null,
                'apiUrl' => null,
            ]);
            $orebutResolver->setAllowedTypes('enabled', 'bool');
            $orebutResolver->setAllowedTypes('apiKey', ['null', 'string']);
            $orebutResolver->setAllowedTypes('apiUrl', ['null', 'string']);

            $resolvedIntegrations['edusign'] = $edusignResolver->resolve($resolvedIntegrations['edusign']);
            $resolvedIntegrations['orebut'] = $orebutResolver->resolve($resolvedIntegrations['orebut']);

            return $resolvedIntegrations;
        });

        return $resolver->resolve($settings);
    }

    private function defaultSettings(): array
    {
        return [
            'integrations' => [
                'edusign' => [
                    'enabled' => false,
                    'scope' => [],
                    'apiKey' => null,
                    'apiUrl' => null,
                ],
                'orebut' => [
                    'enabled' => false,
                    'apiUrl' => null,
                ],
            ],
        ];
    }
}
