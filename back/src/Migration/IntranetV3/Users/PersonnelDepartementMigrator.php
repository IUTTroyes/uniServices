<?php

namespace App\Migration\IntranetV3\Users;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Personnel;
use App\Migration\IntranetV3\AbstractMigrator;
use App\Migration\IntranetV3\MigrationContext;
use App\Migration\IntranetV3\MigrationResult;
use App\Migration\IntranetV3\Structure\DepartementMigrator;
use App\Security\PermissionRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class PersonnelDepartementMigrator extends AbstractMigrator
{
    private const MAX_DIAGNOSTIC_SAMPLES = 20;

    /** Only roles with a clear semantic equivalent are converted automatically. */
    private const ROLE_MAP = [
        'ROLE_PERMANENT' => 'ROLE_PERMANENT',
        'ROLE_CDD' => 'ROLE_CHEF_DEPARTEMENT',
        'ROLE_DDE' => 'ROLE_DIRECTEUR_ETUDES',
        'ROLE_ASS' => 'ROLE_ASSISTANT',
        'ROLE_RP' => 'ROLE_RESP_PARCOURS',
        'ROLE_NOTES' => 'ROLE_NOTES_MANAGER',
    ];

    public function __construct(
        #[Autowire(service: 'doctrine.dbal.copy_connection')]
        Connection $source,
        EntityManagerInterface $entityManager,
        private readonly PermissionRegistry $permissionRegistry,
    ) {
        parent::__construct($source, $entityManager);
    }

    public function getName(): string
    {
        return 'personnel-departements';
    }

    public function getDependencies(): array
    {
        return [PersonnelMigrator::class, DepartementMigrator::class];
    }

    public function migrate(MigrationContext $context): MigrationResult
    {
        $created = $updated = $skipped = $failed = $processed = 0;
        $messages = [];
        $sampleCount = 0;
        $unknownRoles = [];
        $unresolvedPersonnel = $unresolvedDepartments = 0;

        $total = (int) $this->source->fetchOne('SELECT COUNT(*) FROM personnel_departement');
        $this->startProgress($context, 'Affectations personnels/départements', $total);

        foreach ($this->source->executeQuery(
            'SELECT id, personnel_id, departement_id, roles, defaut FROM personnel_departement ORDER BY id'
        )->iterateAssociative() as $row) {
            try {
                $personnel = $this->entityManager->getRepository(Personnel::class)
                    ->findOneBy(['oldId' => (int) $row['personnel_id']]);
                if (null === $personnel) {
                    ++$skipped;
                    ++$unresolvedPersonnel;
                    $this->addSample($messages, $sampleCount, sprintf('PersonnelDepartement #%s ignoré: personnel #%s non résolu.', $row['id'], $row['personnel_id']));
                    $this->advance($context, $processed);
                    continue;
                }

                $departement = $this->entityManager->getRepository(StructureDepartement::class)
                    ->findOneBy(['oldId' => (int) $row['departement_id']]);
                if (null === $departement) {
                    ++$skipped;
                    ++$unresolvedDepartments;
                    $this->addSample($messages, $sampleCount, sprintf('PersonnelDepartement #%s ignoré: département #%s non résolu.', $row['id'], $row['departement_id']));
                    $this->advance($context, $processed);
                    continue;
                }

                $entity = $this->entityManager->getRepository(StructureDepartementPersonnel::class)
                    ->findOneBy(['personnel' => $personnel, 'departement' => $departement]);
                $isNew = null === $entity;
                $entity ??= new StructureDepartementPersonnel();

                $permissions = [];
                foreach ($this->decodeRoles($row['roles'] ?? null) as $legacyRole) {
                    $targetRole = self::ROLE_MAP[$legacyRole] ?? null;
                    if (null === $targetRole || null === $this->permissionRegistry->getPermissionByRole($targetRole)) {
                        $unknownRoles[$legacyRole] = ($unknownRoles[$legacyRole] ?? 0) + 1;
                        continue;
                    }
                    $permissions[$targetRole] = true;
                }

                $entity
                    ->setPersonnel($personnel)
                    ->setDepartement($departement)
                    ->setDefaut((bool) $row['defaut'])
                    ->setPackages(['intranet'])
                    ->setPermissions(array_keys($permissions))
                    ->setAffectation(true);

                if ($isNew) {
                    $this->entityManager->persist($entity);
                    ++$created;
                } else {
                    ++$updated;
                }
            } catch (\Throwable $e) {
                ++$failed;
                $this->addSample($messages, $sampleCount, sprintf('PersonnelDepartement #%s: %s', $row['id'], $e->getMessage()));
            }

            $this->advance($context, $processed);
        }

        $this->flushAndClear($context);
        $this->finishProgress($context);

        if ($unresolvedPersonnel > 0 || $unresolvedDepartments > 0) {
            $messages[] = sprintf('Références non résolues: personnels=%d, départements=%d.', $unresolvedPersonnel, $unresolvedDepartments);
        }

        if ([] !== $unknownRoles) {
            arsort($unknownRoles);
            $parts = [];
            foreach ($unknownRoles as $role => $count) {
                $parts[] = sprintf('%s=%d', $role, $count);
            }
            $messages[] = 'Rôles V3 non convertis automatiquement: ' . implode(', ', $parts) . '.';
        }

        return new MigrationResult($created, $updated, $skipped, $failed, $messages);
    }

    /** @return list<string> */
    private function decodeRoles(mixed $value): array
    {
        if (null === $value || '' === trim((string) $value)) {
            return [];
        }

        try {
            $roles = json_decode((string) $value, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($roles)) {
            return [];
        }

        $result = [];
        foreach ($roles as $role) {
            if (is_string($role) && '' !== trim($role)) {
                $result[] = trim($role);
            }
        }

        return array_values(array_unique($result));
    }

    private function advance(MigrationContext $context, int &$processed): void
    {
        ++$processed;
        $context->advanceProgress();

        if ($processed > 0 && 0 === $processed % self::BATCH_SIZE) {
            $this->flushAndClear($context);
        }
    }

    /** @param list<string> $messages */
    private function addSample(array &$messages, int &$sampleCount, string $message): void
    {
        if ($sampleCount >= self::MAX_DIAGNOSTIC_SAMPLES) {
            return;
        }
        $messages[] = $message;
        ++$sampleCount;
    }
}
