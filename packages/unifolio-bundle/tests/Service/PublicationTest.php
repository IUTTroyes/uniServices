<?php

namespace UnifolioBundle\Tests\Service;

use App\Entity\Users\Etudiant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Entity\Portfolio;
use UnifolioBundle\Entity\PortfolioPublication;
use UnifolioBundle\Enum\StatutRenduEnum;
use UnifolioBundle\Enum\TypePortfolioEnum;
use UnifolioBundle\Enum\VisibilitePortfolioEnum;
use UnifolioBundle\Service\Publication\PublicationInstantane;
use UnifolioBundle\Service\Publication\PublicationStorage;

class PublicationTest extends TestCase
{
    #[DataProvider('nomsDeFichier')]
    public function testNomsDeFichierPublies(string $nom, bool $valide): void
    {
        self::assertSame($valide, PublicationStorage::nomValide($nom));
    }

    /** @return iterable<string, array{string, bool}> */
    public static function nomsDeFichier(): iterable
    {
        yield 'page' => ['index.html', true];
        yield 'média' => ['media/ab12cd34-5.png', true];
        yield 'remontée de dossier' => ['../secret.html', false];
        yield 'remontée dans média' => ['media/../index.html', false];
        yield 'sous-sous-dossier' => ['media/a/b.png', false];
        yield 'fichier caché' => ['.htaccess', false];
        yield 'chemin absolu' => ['/etc/passwd', false];
        yield 'octet nul' => ["index.html\0.png", false];
        yield 'vide' => ['', false];
    }

    public function testLesMediasAcopierSontDeduitsDeLInstantane(): void
    {
        $instantane = ['donnees' => ['traces' => [
            ['medias' => [['fichier' => ['url' => 'media/a-1.png', 'mediaId' => 1]], ['fichier' => null, 'url' => 'https://exemple.org']]],
            ['medias' => [['fichier' => ['url' => 'media/b-7.pdf', 'mediaId' => 7]]]],
        ]]];

        self::assertSame(
            [['mediaId' => 1, 'destination' => 'media/a-1.png'], ['mediaId' => 7, 'destination' => 'media/b-7.pdf']],
            PublicationInstantane::medias($instantane)
        );
    }

    public function testUnePublicationNeDevientActiveQuUneFoisGeneree(): void
    {
        $publication = new PortfolioPublication($this->vitrine(), 1, []);
        try {
            $publication->activer();
            self::fail('Une publication non générée ne doit pas être activable.');
        } catch (\DomainException) {
        }

        $publication->marquerGeneree(['index.html']);
        self::assertSame(StatutRenduEnum::GENERE, $publication->getStatut());
        self::assertTrue($publication->activer()->isActive());
        self::assertTrue($publication->aFichier('index.html'));
        self::assertFalse($publication->aFichier('autre.html'));

        $publication->desactiver();
        self::assertFalse($publication->isActive());
        self::assertNotNull($publication->getDesactiveeLe());
    }

    public function testUneErreurDeGenerationEstConservee(): void
    {
        $publication = new PortfolioPublication($this->vitrine(), 1, []);
        $publication->marquerEnErreur(str_repeat('x', 5000));
        self::assertSame(StatutRenduEnum::ERREUR, $publication->getStatut());
        self::assertSame(2000, mb_strlen((string) $publication->getErreur()));
    }

    public function testLaModerationExigeUnMotifEtSeRetablit(): void
    {
        $publication = new PortfolioPublication($this->vitrine(), 1, []);
        $this->expectException(\InvalidArgumentException::class);
        try {
            $publication->moderer('   ');
        } finally {
            self::assertFalse($publication->isModeree());
            $publication->moderer('Hors sujet');
            self::assertTrue($publication->isModeree());
            self::assertFalse($publication->retablir()->isModeree());
        }
    }

    #[DataProvider('adresses')]
    public function testAdresseDuPortfolio(string $slug, bool $valide): void
    {
        $portfolio = $this->vitrine();
        if (!$valide) {
            $this->expectException(\InvalidArgumentException::class);
        }
        $portfolio->setSlug($slug);
        self::assertSame(strtolower(trim($slug)), $portfolio->getSlug());
    }

    /** @return iterable<string, array{string, bool}> */
    public static function adresses(): iterable
    {
        yield 'simple' => ['lauren-lamoureux', true];
        yield 'majuscules normalisées' => ['Lauren-L', true];
        yield 'trop courte' => ['ab', false];
        yield 'tiret double' => ['a--b', false];
        yield 'tiret initial' => ['-abc', false];
        yield 'accent' => ['élève', false];
        yield 'point' => ['sitemap.xml', false];
        yield 'espace' => ['a b c', false];
        yield 'trop longue' => [str_repeat('a', 70), false];
    }

    public function testLeJetonDePartageEstSecretEtRegenerable(): void
    {
        $portfolio = $this->vitrine();
        self::assertNull($portfolio->getTokenPartage());
        $premier = $portfolio->regenererTokenPartage()->getTokenPartage();
        self::assertMatchesRegularExpression('/^[a-f0-9]{48}$/', (string) $premier);
        self::assertNotSame($premier, $portfolio->regenererTokenPartage()->getTokenPartage());
    }

    public function testUnPortfolioAcademiqueNePeutPasEtrePublic(): void
    {
        self::assertFalse(TypePortfolioEnum::ACADEMIQUE->autoriseVisibilite(VisibilitePortfolioEnum::PUBLIC));
        self::assertTrue(TypePortfolioEnum::ACADEMIQUE->autoriseVisibilite(VisibilitePortfolioEnum::ETABLISSEMENT));
        self::assertTrue(TypePortfolioEnum::VITRINE->autoriseVisibilite(VisibilitePortfolioEnum::LIEN_SECRET));
    }

    public function testLaDescriptionEstBornee(): void
    {
        $portfolio = $this->vitrine()->setDescription('  '.str_repeat('é', 500).'  ');
        self::assertSame(300, mb_strlen((string) $portfolio->getDescription()));
        self::assertNull($portfolio->setDescription('   ')->getDescription());
    }

    private function vitrine(): Portfolio
    {
        return new Portfolio(new Etudiant(), TypePortfolioEnum::VITRINE, 'Vitrine');
    }
}
