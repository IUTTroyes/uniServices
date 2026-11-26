<?php

namespace App\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Le cœur ne dépend d'aucun package et un package ne dépend d'aucun autre : c'est ce qui permet de composer des
 * distributions. Pour échanger, passer par les points d'extension de App\Extension.
 */
class IsolationDesPackagesTest extends TestCase
{
    private const RACINE = __DIR__.'/../..';

    /** @return iterable<string, array{string, ?string}> [dossier, espace de noms du package] */
    public static function perimetres(): iterable
    {
        // La reprise des données de l'intranet V3 connaît par nature tous les packages.
        yield 'cœur' => [self::RACINE.'/back/src', null];
        foreach (glob(self::RACINE.'/packages/*-bundle') ?: [] as $dossier) {
            $espace = self::espaceDeNoms($dossier);
            yield $espace => [$dossier.'/src', $espace];
        }
    }

    #[DataProvider('perimetres')]
    public function testAucuneReferenceAUnAutrePackage(string $dossier, ?string $espace): void
    {
        $packages = array_map(self::espaceDeNoms(...), glob(self::RACINE.'/packages/*-bundle') ?: []);
        $interdits = array_values(array_diff($packages, [$espace]));
        $motif = '/(?<![A-Za-z0-9_])('.implode('|', $interdits).')\\\\{1,2}[A-Z]/';

        $fautes = [];
        $fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dossier, \FilesystemIterator::SKIP_DOTS));
        foreach ($fichiers as $fichier) {
            $chemin = $fichier->getPathname();
            if ($fichier->getExtension() !== 'php' || str_contains($chemin, '/back/src/Migration/')) {
                continue;
            }
            if (preg_match_all($motif, (string) file_get_contents($chemin), $trouves)) {
                $fautes[] = substr($chemin, \strlen($dossier) + 1).' → '.implode(', ', array_unique($trouves[1]));
            }
        }

        self::assertSame([], $fautes);
    }

    private static function espaceDeNoms(string $dossier): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', basename($dossier))));
    }
}
