<?php

namespace App\Tests\Notification;

use App\Entity\Notification\Notification;
use App\Entity\Notification\PreferenceNotification;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Enum\ModeNotificationEnum;
use App\Notification\NotificationType;
use App\Notification\NotificationTypeProviderInterface;
use App\Notification\NotificationTypeRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Enum\TypeNotificationEnum;
use UnifolioBundle\Service\Notification\UnifolioNotificationTypes;

class NotificationSystemTest extends TestCase
{
    private function type(string $cle = 'demo.evenement', ?array $modes = null, ModeNotificationEnum $defaut = ModeNotificationEnum::RESUME, string $audience = NotificationType::AUDIENCE_ETUDIANT): NotificationType
    {
        return new NotificationType($cle, explode('.', $cle)[0], 'Démo', 'Un événement', 'Description', 'Rubrique', $audience, $defaut, $modes);
    }

    #[DataProvider('clesInvalides')]
    public function testUneCleInvalideEstRefusee(string $cle): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NotificationType($cle, 'demo', 'Démo', 'L', 'D', 'R', NotificationType::AUDIENCE_ETUDIANT);
    }

    /** @return iterable<string, array{string}> */
    public static function clesInvalides(): iterable
    {
        yield 'sans point' => ['demoevenement'];
        yield 'majuscules' => ['demo.Evenement'];
        yield 'mauvais package' => ['autre.evenement'];
        yield 'trop de niveaux' => ['demo.un.deux'];
        yield 'espace' => ['demo.un evenement'];
    }

    public function testLeModeParDefautDoitEtreAutorise(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->type('demo.x', [ModeNotificationEnum::IMMEDIAT, ModeNotificationEnum::AUCUN], ModeNotificationEnum::RESUME);
    }

    public function testUnPublicInconnuEstRefuse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->type('demo.x', null, ModeNotificationEnum::RESUME, 'parent');
    }

    public function testLeRegistreRassembleLesPackagesEtRefuseLesDoublons(): void
    {
        $fournisseur = fn (NotificationType ...$types) => new class($types) implements NotificationTypeProviderInterface {
            public function __construct(private array $types)
            {
            }

            public function getTypes(): iterable
            {
                return $this->types;
            }
        };
        $registre = new NotificationTypeRegistry([
            $fournisseur($this->type('a.un'), $this->type('a.deux', null, ModeNotificationEnum::RESUME, NotificationType::AUDIENCE_PERSONNEL)),
            $fournisseur($this->type('b.un')),
        ]);
        self::assertSame(['a.un', 'a.deux', 'b.un'], array_keys($registre->all()));
        self::assertNull($registre->get('c.inconnu'));
        self::assertSame(['a.un', 'b.un'], array_map(static fn (NotificationType $t) => $t->cle, $registre->pourAudience('etudiant')));

        $this->expectException(\LogicException::class);
        (new NotificationTypeRegistry([$fournisseur($this->type('a.un')), $fournisseur($this->type('a.un'))]))->all();
    }

    public function testLesPreferencesSuiventLeDefautPuisLeReglagePuisLInterrupteur(): void
    {
        $t = $this->type('demo.evenement', null, ModeNotificationEnum::RESUME);
        $prefs = new PreferenceNotification(new Etudiant());
        self::assertSame(ModeNotificationEnum::RESUME, $prefs->modePour($t));

        $prefs->definirMode($t, ModeNotificationEnum::IMMEDIAT);
        self::assertSame(ModeNotificationEnum::IMMEDIAT, $prefs->modePour($t));
        self::assertSame(ModeNotificationEnum::IMMEDIAT, $prefs->modeAffiche($t));

        $prefs->setActif(false);
        self::assertSame(ModeNotificationEnum::AUCUN, $prefs->modePour($t));
        self::assertSame(ModeNotificationEnum::IMMEDIAT, $prefs->modeAffiche($t), 'l\'affichage garde le réglage personnel');
    }

    public function testUnModeInterditPourCeTypeEstRefuseEtIgnoreSiIlDevientInvalide(): void
    {
        $t = $this->type('demo.rappel', [ModeNotificationEnum::IMMEDIAT, ModeNotificationEnum::AUCUN], ModeNotificationEnum::IMMEDIAT);
        $prefs = new PreferenceNotification(new Personnel());
        try {
            $prefs->definirMode($t, ModeNotificationEnum::RESUME);
            self::fail('Un mode interdit doit être refusé.');
        } catch (\InvalidArgumentException) {
        }

        (new \ReflectionProperty($prefs, 'reglages'))->setValue($prefs, ['demo.rappel' => 'resume']);
        self::assertSame(ModeNotificationEnum::IMMEDIAT, $prefs->modePour($t), 'un ancien réglage devenu interdit retombe sur le défaut');
    }

    /** @param bool $valide */
    #[DataProvider('liens')]
    public function testSeulsLesCheminsInternesSontAcceptes(string $url, bool $valide): void
    {
        self::assertSame($valide, Notification::urlInterneValide($url));
        if (!$valide) {
            $this->expectException(\InvalidArgumentException::class);
            new Notification(new Etudiant(), 'demo.evenement', 'demo', 'Texte', $url);
        }
        if ($valide) {
            self::assertSame($url, (new Notification(new Etudiant(), 'demo.evenement', 'demo', 'Texte', $url))->getUrl());
        }
    }

    /** @return iterable<string, array{string, bool}> */
    public static function liens(): iterable
    {
        yield 'chemin de l\'application' => ['/app/unifolio/traces/abc?x=1#y', true];
        yield 'racine' => ['/', true];
        yield 'adresse externe' => ['https://exemple.org/x', false];
        yield 'protocole relatif' => ['//exemple.org', false];
        yield 'javascript' => ['javascript:alert(1)', false];
        yield 'sans barre initiale' => ['app/x', false];
        yield 'espace' => ['/app/x y', false];
        yield 'antislash' => ['/\\exemple.org', false];
        yield 'trop long' => ['/'.str_repeat('a', 300), false];
    }

    public function testLaNotificationEstBorneeFusionneEtSeLit(): void
    {
        $n = new Notification(new Personnel(), 'demo.evenement', 'demo', str_repeat('é', 900), '/app');
        self::assertSame(Notification::TEXTE_MAX, mb_strlen($n->getTexte()));
        self::assertFalse($n->isLue());
        self::assertNull($n->getEmailTraiteLe());

        $n->marquerLue();
        self::assertTrue($n->isLue());
        $n->fusionner('Plus récent');
        self::assertSame(2, $n->getNombre());
        self::assertSame('Plus récent', $n->getTexte());
        self::assertFalse($n->isLue(), 'un événement fusionné redevient non lu');

        $n->marquerEmailTraite();
        self::assertNotNull($n->getEmailTraiteLe());
    }

    public function testUneNotificationNAppartientQuASonDestinataire(): void
    {
        $etudiant = new Etudiant();
        $n = new Notification($etudiant, 'demo.evenement', 'demo', 'Texte');
        self::assertTrue($n->appartientA($etudiant));
        self::assertFalse($n->appartientA(new Personnel()));
    }

    public function testToutesLesDefinitionsDUniFolioSontValides(): void
    {
        $types = iterator_to_array((new UnifolioNotificationTypes())->getTypes(), false);
        self::assertCount(\count(TypeNotificationEnum::cases()), $types);
        foreach ($types as $type) {
            self::assertStringStartsWith('unifolio.', $type->cle);
            self::assertSame('UniFolio', $type->sourceLibelle);
            self::assertTrue($type->autorise($type->modeParDefaut));
        }
        self::assertTrue(TypeNotificationEnum::PUBLICATION_MODEREE->definition()->autorise(ModeNotificationEnum::RESUME));
        self::assertFalse(TypeNotificationEnum::PUBLICATION_MODEREE->definition()->autorise(ModeNotificationEnum::AUCUN));
        self::assertFalse(TypeNotificationEnum::RELANCE_EVALUATEUR->definition()->autorise(ModeNotificationEnum::RESUME));
    }
}
