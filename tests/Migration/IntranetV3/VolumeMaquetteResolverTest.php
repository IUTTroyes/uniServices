<?php

declare(strict_types=1);

namespace App\Tests\Migration\IntranetV3;

use IntranetBundle\Service\Previsionnel\VolumeMaquetteResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VolumeMaquetteResolverTest extends TestCase
{
    /** @return iterable<string, array{array<string, int|float>, float}> */
    public static function hours(): iterable
    {
        yield 'prefer PN when positive' => [['PN' => 12, 'IUT' => 16], 12.0];
        yield 'use IUT when PN is zero' => [['PN' => 0, 'IUT' => 16], 16.0];
        yield 'use IUT when PN is missing' => [['IUT' => 9], 9.0];
        yield 'all zero' => [['PN' => 0, 'IUT' => 0], 0.0];
        yield 'only PN' => [['PN' => 12], 12.0];
    }

    #[DataProvider('hours')]
    public function testHours(array $volumes, float $expected): void
    {
        self::assertSame($expected, VolumeMaquetteResolver::resolve($volumes));
    }
}
