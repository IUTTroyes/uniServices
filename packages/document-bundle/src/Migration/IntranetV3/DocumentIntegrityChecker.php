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
        ];
    }

    private function row(string $label, string $sourceTable, string $targetTable): array
    {
        $source = (int) $this->source->fetchOne('SELECT COUNT(*) FROM '.$sourceTable);
        $target = (int) $this->target->fetchOne('SELECT COUNT(*) FROM '.$targetTable.' WHERE old_id IS NOT NULL');
        return ['ok' => $source === $target, 'label' => $label, 'source' => $source, 'target' => $target];
    }
}
