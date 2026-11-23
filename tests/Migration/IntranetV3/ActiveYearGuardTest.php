<?php

declare(strict_types=1);

namespace App\Tests\Migration\IntranetV3;

use App\Migration\IntranetV3\ActiveYearGuard;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ActiveYearGuardTest extends TestCase
{
    public function testSingleActiveYear2026IsAccepted(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchFirstColumn')->willReturn([2026]);

        (new ActiveYearGuard($connection))->assertYear2026();

        self::assertTrue(true);
    }

    /** @return iterable<string, array{list<int>}> */
    public static function invalidYears(): iterable
    {
        yield 'no active year' => [[]];
        yield 'old year' => [[2025]];
        yield 'multiple active years' => [[2025, 2026]];
        yield 'wrong year' => [[2027]];
    }

    #[DataProvider('invalidYears')]
    public function testOtherActiveYearsAreRejected(array $years): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchFirstColumn')->willReturn($years);

        $this->expectException(\DomainException::class);
        (new ActiveYearGuard($connection))->assertYear2026();
    }
}
