<?php

namespace DocumentBundle\Migration\IntranetV3;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DocumentFileMigrator
{
    public function __construct(
        #[Autowire(service: 'doctrine.dbal.copy_connection')]
        private Connection $source,
    ) {}

    /**
     * @return array{expected:int,copied:int,existing:int,missing:int,failed:int,missingFiles:list<string>,errors:list<string>}
     */
    public function migrate(string $sourceDirectory, string $targetDirectory, bool $dryRun = false): array
    {
        $sourceDirectory = rtrim($sourceDirectory, DIRECTORY_SEPARATOR);
        $targetDirectory = rtrim($targetDirectory, DIRECTORY_SEPARATOR);

        if (!is_dir($sourceDirectory)) {
            throw new \RuntimeException(sprintf('Le dossier source des documents V3 n’existe pas : %s', $sourceDirectory));
        }
        if (!$dryRun && !is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new \RuntimeException(sprintf('Impossible de créer le dossier cible : %s', $targetDirectory));
        }

        $result = ['expected' => 0, 'copied' => 0, 'existing' => 0, 'missing' => 0, 'failed' => 0, 'missingFiles' => [], 'errors' => []];
        $rows = $this->source->fetchAllAssociative('SELECT id, document_name FROM document WHERE document_name IS NOT NULL AND document_name <> \'\' ORDER BY id');

        foreach ($rows as $row) {
            ++$result['expected'];
            $filename = basename((string) $row['document_name']);
            $source = $sourceDirectory.DIRECTORY_SEPARATOR.$filename;
            $target = $targetDirectory.DIRECTORY_SEPARATOR.$filename;

            if (!is_file($source)) {
                ++$result['missing'];
                if (count($result['missingFiles']) < 100) $result['missingFiles'][] = sprintf('#%s %s', $row['id'], $filename);
                continue;
            }

            if (is_file($target)) {
                // Un fichier déjà présent et de même taille est considéré comme déjà migré.
                if (filesize($source) === filesize($target)) {
                    ++$result['existing'];
                    continue;
                }
                ++$result['failed'];
                if (count($result['errors']) < 100) $result['errors'][] = sprintf('#%s %s : le fichier cible existe avec une taille différente.', $row['id'], $filename);
                continue;
            }

            if ($dryRun) {
                ++$result['copied'];
                continue;
            }

            if (@copy($source, $target)) ++$result['copied'];
            else {
                ++$result['failed'];
                if (count($result['errors']) < 100) $result['errors'][] = sprintf('#%s %s : échec de la copie.', $row['id'], $filename);
            }
        }

        return $result;
    }

    /** @return array{expected:int,present:int,missing:int,wrongSize:int,missingFiles:list<string>,wrongSizeFiles:list<string>} */
    public function check(string $directory): array
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);
        if (!is_dir($directory)) throw new \RuntimeException(sprintf('Le dossier de documents n’existe pas : %s', $directory));

        $result = ['expected' => 0, 'present' => 0, 'missing' => 0, 'wrongSize' => 0, 'missingFiles' => [], 'wrongSizeFiles' => []];
        $rows = $this->source->fetchAllAssociative('SELECT id, document_name, taille FROM document WHERE document_name IS NOT NULL AND document_name <> \'\' ORDER BY id');
        foreach ($rows as $row) {
            ++$result['expected'];
            $filename = basename((string) $row['document_name']);
            $path = $directory.DIRECTORY_SEPARATOR.$filename;
            if (!is_file($path)) {
                ++$result['missing'];
                if (count($result['missingFiles']) < 100) $result['missingFiles'][] = sprintf('#%s %s', $row['id'], $filename);
                continue;
            }
            ++$result['present'];
            $expectedSize = (int) round((float) ($row['taille'] ?? 0));
            if ($expectedSize > 0 && filesize($path) !== $expectedSize) {
                ++$result['wrongSize'];
                if (count($result['wrongSizeFiles']) < 100) $result['wrongSizeFiles'][] = sprintf('#%s %s (V3=%d, fichier=%d)', $row['id'], $filename, $expectedSize, filesize($path));
            }
        }
        return $result;
    }
}
