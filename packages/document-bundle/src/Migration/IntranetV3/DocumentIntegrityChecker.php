<?php

namespace DocumentBundle\Migration\IntranetV3;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DocumentIntegrityChecker
{
    public function __construct(
        #[Autowire(service: 'doctrine.dbal.copy_connection')] private Connection $source,
        private Connection $target,
    ) {}

    /** @return list<array{ok: bool, label: string, source: int, target: int}> */
    public function check(): array
    {
        return [
            $this->row('Catégories', 'type_document', 'document_category'),
            $this->row('Documents', 'document', 'document'),
            $this->customRow('Catégories originales', 'SELECT COUNT(*) FROM type_document WHERE originaux = 1', 'SELECT COUNT(*) FROM document_category WHERE old_id IS NOT NULL AND is_original = 1'),
            $this->customRow('Catégories avec parent', 'SELECT COUNT(*) FROM type_document WHERE parent_id IS NOT NULL', 'SELECT COUNT(*) FROM document_category WHERE old_id IS NOT NULL AND parent_id IS NOT NULL'),
            $this->customRow('Documents catégorisés', 'SELECT COUNT(*) FROM document WHERE type_document_id IS NOT NULL', 'SELECT COUNT(*) FROM document WHERE old_id IS NOT NULL AND category_id IS NOT NULL'),
        ];
    }

    private function customRow(string $label, string $sourceSql, string $targetSql): array
    {
        $source = (int) $this->source->fetchOne($sourceSql);
        $target = (int) $this->target->fetchOne($targetSql);
        return ['ok' => $source === $target, 'label' => $label, 'source' => $source, 'target' => $target];
    }

    private function row(string $label, string $sourceTable, string $targetTable): array
    {
        $source = (int) $this->source->fetchOne('SELECT COUNT(*) FROM '.$sourceTable);
        $target = (int) $this->target->fetchOne('SELECT COUNT(*) FROM '.$targetTable.' WHERE old_id IS NOT NULL');
        return ['ok' => $source === $target, 'label' => $label, 'source' => $source, 'target' => $target];
    }
}
