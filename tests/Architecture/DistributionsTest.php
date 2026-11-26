<?php

namespace App\Tests\Architecture;

use App\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

/** Chaque distribution compile son conteneur, ses mappings et ses routes avec ses seuls packages. */
class DistributionsTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['APP_DISTRIBUTION']);
        restore_exception_handler();
    }

    /** @return iterable<string, array{string, ?list<string>}> null : tous les packages de config/bundles.php */
    public static function distributions(): iterable
    {
        yield 'intranet' => ['intranet', null];
        yield 'unifolio' => ['unifolio', ['AuthBundle', 'UnifolioBundle']];
    }

    #[DataProvider('distributions')]
    public function testDemarre(string $distribution, ?array $attendus): void
    {
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
        (new Dotenv())->bootEnv(__DIR__.'/../../back/.env');
        $_SERVER['APP_DISTRIBUTION'] = $distribution;

        $noyau = new Kernel('test', true);
        $noyau->boot();
        $conteneur = $noyau->getContainer();

        $tous = array_map(
            static fn (string $d): string => str_replace(' ', '', ucwords(str_replace('-', ' ', basename($d)))),
            glob(__DIR__.'/../../packages/*-bundle') ?: [],
        );
        $actifs = array_map(
            static fn (string $classe): string => explode('\\', $classe)[0],
            array_keys(require __DIR__.'/../../back/config/bundles.php'),
        );
        $attendus ??= array_values(array_intersect($actifs, $tous));
        $packages = array_values(array_intersect(array_keys($noyau->getBundles()), $tous));
        self::assertEqualsCanonicalizing($attendus, $packages);

        $entites = array_map(
            static fn ($m): string => explode('\\', $m->getName())[0],
            $conteneur->get('doctrine')->getManager()->getMetadataFactory()->getAllMetadata(),
        );
        $autres = array_diff(array_intersect($entites, $tous), $attendus);
        self::assertSame([], array_values(array_unique($autres)));

        $chemins = array_map(static fn ($r): string => $r->getPath(), iterator_to_array($conteneur->get('router')->getRouteCollection()));
        self::assertNotEmpty(preg_grep('~^/api/unifolio/~', $chemins));
        self::assertSame(\in_array('StageBundle', $attendus, true), preg_grep('~^/api/stage~', $chemins) !== []);

        $noyau->shutdown();
    }
}
