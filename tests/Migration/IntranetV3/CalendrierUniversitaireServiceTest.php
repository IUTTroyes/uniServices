<?php

declare(strict_types=1);

namespace App\Tests\Migration\IntranetV3;

use App\Service\Calendrier\CalendrierUniversitaireService;
use PHPUnit\Framework\TestCase;

final class CalendrierUniversitaireServiceTest extends TestCase
{
    public function testDateFromContinuousV3Weeks(): void
    {
        $service = new CalendrierUniversitaireService();

        $reference = $service->getDateDebutDepuisCorrespondances([
            ['semaine_formation' => 1, 'semaine_reelle' => 35, 'date_lundi' => '2026-08-24'],
            ['semaine_formation' => 2, 'semaine_reelle' => 36, 'date_lundi' => '2026-08-31'],
        ]);

        self::assertSame('2026-08-24', $reference?->format('Y-m-d'));
    }

    public function testDuplicateWeekIsRefused(): void
    {
        $service = new CalendrierUniversitaireService();
        $this->expectException(\DomainException::class);

        $service->getDateDebutDepuisCorrespondances([
            ['semaine_formation' => 1, 'semaine_reelle' => 35, 'date_lundi' => '2026-08-24'],
            ['semaine_formation' => 1, 'semaine_reelle' => 35, 'date_lundi' => '2026-08-24'],
        ]);
    }
}
