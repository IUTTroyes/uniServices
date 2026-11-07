<?php

namespace UnifolioBundle\Tests\Service;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Entity\NotificationEnAttente;
use UnifolioBundle\Entity\PreferenceNotification;
use UnifolioBundle\Enum\FrequenceResumeEnum;
use UnifolioBundle\Enum\ModeNotificationEnum;
use UnifolioBundle\Enum\TypeNotificationEnum;

class NotificationTest extends TestCase
{
    public function testChaqueTypeAUnPublicUnDefautEtDesModesCoherents(): void
    {
        foreach (TypeNotificationEnum::cases() as $type) {
            self::assertContains($type->getAudience(), [TypeNotificationEnum::AUDIENCE_ETUDIANT, TypeNotificationEnum::AUDIENCE_PERSONNEL]);
            self::assertContains($type->getModeParDefaut(), $type->getModesAutorises(), $type->value);
            self::assertNotSame('', $type->getLibelle());
            self::assertNotSame('', $type->getRubrique());
        }
        self::assertCount(7, TypeNotificationEnum::cases());
        self::assertSame(7, \count(TypeNotificationEnum::pourAudience('etudiant')) + \count(TypeNotificationEnum::pourAudience('personnel')));
    }

    public function testCePuiEstUrgentPartToutDeSuiteLeResteEstRegroupe(): void
    {
        self::assertSame(ModeNotificationEnum::IMMEDIAT, TypeNotificationEnum::EVALUATION_RENDUE->getModeParDefaut());
        self::assertSame(ModeNotificationEnum::IMMEDIAT, TypeNotificationEnum::REVISION_DEMANDEE->getModeParDefaut());
        self::assertSame(ModeNotificationEnum::RESUME, TypeNotificationEnum::COMMENTAIRE_ENSEIGNANT->getModeParDefaut());
        self::assertSame(ModeNotificationEnum::RESUME, TypeNotificationEnum::TRACE_DEPOSEE->getModeParDefaut());
        self::assertSame(ModeNotificationEnum::RESUME, TypeNotificationEnum::COMMENTAIRE_ETUDIANT->getModeParDefaut());
    }

    public function testUneRelanceNePeutPasEtreDansUnResumeNiUneModerationIgnoree(): void
    {
        self::assertNotContains(ModeNotificationEnum::RESUME, TypeNotificationEnum::RELANCE_EVALUATEUR->getModesAutorises());
        self::assertNotContains(ModeNotificationEnum::AUCUN, TypeNotificationEnum::PUBLICATION_MODEREE->getModesAutorises());
    }

    public function testSansReglageOnSuitLesValeursParDefaut(): void
    {
        $prefs = new PreferenceNotification(new Etudiant());
        self::assertTrue($prefs->isActif());
        self::assertSame(FrequenceResumeEnum::QUOTIDIEN, $prefs->getFrequence());
        foreach (TypeNotificationEnum::cases() as $type) {
            self::assertSame($type->getModeParDefaut(), $prefs->modePour($type));
            self::assertNull($prefs->reglage($type));
        }
    }

    public function testLeReglagePersonnelPrimeSurLeDefaut(): void
    {
        $prefs = new PreferenceNotification(new Personnel());
        $prefs->definirMode(TypeNotificationEnum::TRACE_DEPOSEE, ModeNotificationEnum::IMMEDIAT);
        $prefs->definirMode(TypeNotificationEnum::COMMENTAIRE_ETUDIANT, ModeNotificationEnum::AUCUN);

        self::assertSame(ModeNotificationEnum::IMMEDIAT, $prefs->modePour(TypeNotificationEnum::TRACE_DEPOSEE));
        self::assertSame(ModeNotificationEnum::AUCUN, $prefs->modePour(TypeNotificationEnum::COMMENTAIRE_ETUDIANT));
        self::assertSame(ModeNotificationEnum::IMMEDIAT, $prefs->modePour(TypeNotificationEnum::RELANCE_EVALUATEUR), 'les autres types gardent leur défaut');
    }

    public function testLInterrupteurGeneralCouperToutSansPerdreLesReglages(): void
    {
        $prefs = new PreferenceNotification(new Etudiant());
        $prefs->definirMode(TypeNotificationEnum::COMMENTAIRE_ENSEIGNANT, ModeNotificationEnum::IMMEDIAT);
        $prefs->setActif(false);
        foreach (TypeNotificationEnum::cases() as $type) {
            self::assertSame(ModeNotificationEnum::AUCUN, $prefs->modePour($type));
        }
        $prefs->setActif(true);
        self::assertSame(ModeNotificationEnum::IMMEDIAT, $prefs->modePour(TypeNotificationEnum::COMMENTAIRE_ENSEIGNANT), 'le réglage personnel est conservé');
    }

    public function testUnModeNonAutorisePourCeTypeEstRefuse(): void
    {
        $prefs = new PreferenceNotification(new Personnel());
        $this->expectException(\InvalidArgumentException::class);
        $prefs->definirMode(TypeNotificationEnum::RELANCE_EVALUATEUR, ModeNotificationEnum::RESUME);
    }

    public function testUnReglageDevenuInvalideRetombeSurLeDefaut(): void
    {
        $prefs = new PreferenceNotification(new Etudiant());
        (new \ReflectionProperty($prefs, 'reglages'))->setValue($prefs, ['publication_moderee' => 'aucun', 'evaluation_rendue' => 'n\'importe quoi']);
        self::assertSame(ModeNotificationEnum::IMMEDIAT, $prefs->modePour(TypeNotificationEnum::PUBLICATION_MODEREE));
        self::assertSame(ModeNotificationEnum::IMMEDIAT, $prefs->modePour(TypeNotificationEnum::EVALUATION_RENDUE));
    }

    public function testDesEvenementsIdentiquesSeFusionnent(): void
    {
        $n = new NotificationEnAttente(new Personnel(), TypeNotificationEnum::COMMENTAIRE_ETUDIANT, 'Premier', '/app/x');
        self::assertSame(1, $n->getNombre());
        $n->fusionner('Dernier');
        $n->fusionner('Tout dernier');
        self::assertSame(3, $n->getNombre());
        self::assertSame('Tout dernier', $n->getTexte());
        self::assertNull($n->getEnvoyeeLe());
        $n->marquerEnvoyee(new \DateTimeImmutable());
        self::assertNotNull($n->getEnvoyeeLe());
    }

    public function testLeTexteEtLeLienSontBornes(): void
    {
        $n = new NotificationEnAttente(new Etudiant(), TypeNotificationEnum::EVALUATION_RENDUE, str_repeat('é', 900), str_repeat('a', 400));
        self::assertSame(500, mb_strlen($n->getTexte()));
        self::assertSame(255, mb_strlen((string) $n->getUrl()));
    }
}
