<?php

namespace App\Initialization;

use App\Entity\Etablissement;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class EtablissementInitializer implements InitializerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(param: 'app.establishment')]
        private readonly array $configuration,
    ) {
    }

    public function getName(): string
    {
        return 'etablissement';
    }

    public function initialize(MigrationContext $context): MigrationResult
    {
        $libelle = trim((string) ($this->configuration['libelle'] ?? ''));
        if ('' === $libelle) {
            throw new \LogicException('The establishment initialization requires a non-empty "libelle".');
        }

        $repository = $this->entityManager->getRepository(Etablissement::class);
        $etablissement = $repository->findOneBy(['isMain' => true]);

        if (null === $etablissement) {
            $etablissement = $repository->findOneBy(['libelle' => $libelle]);
        }

        $isNew = null === $etablissement;
        $etablissement ??= new Etablissement();

        $etablissement
            ->setLibelle($libelle)
            ->setSiteWeb($this->nullableString('site_web'))
            ->setTelephone($this->nullableString('telephone'))
            ->setAdresse($this->configuration['adresse'] ?? null);
        $etablissement->setIsMain((bool) ($this->configuration['is_main'] ?? true));

        // Nouveaux champs : renseignés seulement s'ils sont configurés, pour ne pas effacer une saisie faite dans l'écran.
        if (null !== ($directeur = $this->nullableString('directeur'))) {
            $etablissement->setDirecteurNom($directeur);
        }
        $universite = \is_array($this->configuration['universite'] ?? null) ? $this->configuration['universite'] : [];
        foreach (['libelle' => 'setLibelle', 'telephone' => 'setTelephone', 'president' => 'setPresident'] as $cle => $setter) {
            $valeur = trim((string) ($universite[$cle] ?? ''));
            if ('' !== $valeur) {
                $etablissement->getUniversite()->{$setter}($valeur);
            }
        }
        $adresseUniversite = \is_array($universite['adresse'] ?? null) ? $universite['adresse'] : [];
        if ('' !== trim((string) ($adresseUniversite['ville'] ?? '')) || '' !== trim((string) ($adresseUniversite['adresse'] ?? ''))) {
            $etablissement->getUniversite()->setAdresse($adresseUniversite);
        }

        if ($isNew) {
            $this->entityManager->persist($etablissement);
        }

        $this->entityManager->flush();

        return new MigrationResult(
            created: $isNew ? 1 : 0,
            updated: $isNew ? 0 : 1,
        );
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) ($this->configuration[$key] ?? ''));

        return '' === $value ? null : $value;
    }
}
