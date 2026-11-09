<?php

namespace FinanceBundle\Tests\Notification;

use App\Enum\ModeNotificationEnum;
use App\Notification\NotificationType;
use FinanceBundle\Enum\TypeNotificationEnum;
use FinanceBundle\Service\Notification\FinanceNotificationTypes;
use PHPUnit\Framework\TestCase;

/** Les types de notification du package sont valides (NotificationType lève une exception sinon) et cohérents. */
final class FinanceNotificationTypesTest extends TestCase
{
    public function testToutesLesDefinitionsSontValides(): void
    {
        $types = iterator_to_array((new FinanceNotificationTypes())->getTypes(), false);
        self::assertCount(\count(TypeNotificationEnum::cases()), $types);

        $cles = [];
        foreach ($types as $type) {
            self::assertInstanceOf(NotificationType::class, $type);
            self::assertStringStartsWith('finance.', $type->cle);
            self::assertSame('finance', $type->source);
            self::assertSame(NotificationType::AUDIENCE_PERSONNEL, $type->audience);
            self::assertContains($type->modeParDefaut, $type->modes(), $type->cle);
            $cles[] = $type->cle;
        }
        self::assertSame($cles, array_unique($cles), 'clés uniques');
    }

    public function testCeQuiAppelleUneReactionNePeutPasEtreIgnore(): void
    {
        foreach ([TypeNotificationEnum::AVIS_RENDU, TypeNotificationEnum::DEMANDE_REJETEE] as $type) {
            self::assertNotContains(ModeNotificationEnum::AUCUN, $type->getModesAutorises(), $type->value);
        }
        // Le récapitulatif est périodique : pas de « dans le résumé ».
        self::assertNotContains(ModeNotificationEnum::RESUME, TypeNotificationEnum::RECAPITULATIF->getModesAutorises());
        self::assertContains(ModeNotificationEnum::AUCUN, TypeNotificationEnum::RECAPITULATIF->getModesAutorises());
    }
}
