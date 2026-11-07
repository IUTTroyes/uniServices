<?php

namespace UnifolioBundle\Tests\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Service\Publication\ThemeNormalizer;

class ThemeNormalizerTest extends TestCase
{
    private ThemeNormalizer $themes;

    protected function setUp(): void
    {
        $this->themes = new ThemeNormalizer();
    }

    public function testLesValeursAbsentesPrennentLeDefaut(): void
    {
        self::assertSame(ThemeNormalizer::DEFAUT, $this->themes->normaliser([]));
        self::assertSame('classique', $this->themes->normaliser(['police' => 'classique'])['police']);
    }

    public function testLaCouleurEstNormalisee(): void
    {
        self::assertSame('#7c3aed', $this->themes->normaliser(['couleur' => ' #7C3AED '])['couleur']);
    }

    #[DataProvider('refus')]
    public function testValeursRefusees(array $donnees): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->themes->normaliser($donnees);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function refus(): iterable
    {
        yield 'police externe' => [['police' => 'Comic Sans']];
        yield 'police avec injection CSS' => [['police' => "systeme;}body{display:none"]];
        yield 'couleur sans dièse' => [['couleur' => '7c3aed']];
        yield 'couleur courte' => [['couleur' => '#fff']];
        yield 'couleur avec injection' => [['couleur' => '#000000;background:url(//x)']];
        yield 'nom de couleur' => [['couleur' => 'red']];
        yield 'couleur trop claire' => [['couleur' => '#ffff99']];
        yield 'blanc' => [['couleur' => '#ffffff']];
        yield 'en-tête inconnu' => [['entete' => 'flottant']];
        yield 'largeur inconnue' => [['largeur' => '100vw']];
        yield 'coins inconnus' => [['coins' => 'ronds']];
    }

    public function testLesPalettesProposeesSontToutesLisibles(): void
    {
        foreach (['#047857', '#1d4ed8', '#7c3aed', '#be123c', '#c2410c', '#0f766e', '#334155', '#a21caf'] as $couleur) {
            self::assertGreaterThanOrEqual(4.5, ThemeNormalizer::contraste($couleur, '#ffffff'), $couleur);
        }
    }

    public function testUnThemeStockeInvalideRevientAuDefautSansErreur(): void
    {
        self::assertSame(ThemeNormalizer::DEFAUT, $this->themes->lire(['couleur' => 'javascript:alert(1)']));
        self::assertSame(ThemeNormalizer::DEFAUT, $this->themes->lire([]));
    }

    public function testContrasteConnu(): void
    {
        self::assertEqualsWithDelta(21.0, ThemeNormalizer::contraste('#000000', '#ffffff'), 0.01);
        self::assertEqualsWithDelta(1.0, ThemeNormalizer::contraste('#777777', '#777777'), 0.001);
    }
}
