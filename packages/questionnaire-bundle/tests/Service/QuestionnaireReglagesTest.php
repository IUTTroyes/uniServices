<?php

namespace QuestionnaireBundle\Tests\Service;

use App\Entity\Structure\StructureDepartement;
use PHPUnit\Framework\TestCase;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Services\QuestionnaireReglages;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class QuestionnaireReglagesTest extends TestCase
{
    public function testValeursParDefautSansDepartement(): void
    {
        self::assertSame(QuestionnaireReglages::DEFAUTS, (new QuestionnaireReglages())->pour(null));
    }

    public function testEnregistrementPartielDansLesOptionsDuDepartement(): void
    {
        $d = new StructureDepartement();
        $reglages = new QuestionnaireReglages();
        $reglages->enregistrer($d, ['duree_jours' => 7]);
        $reglages->enregistrer($d, ['anonymat' => false]);

        self::assertSame(7, $d->getOpt()['questionnaire']['duree_jours'], 'Un enregistrement partiel garde les autres valeurs.');
        self::assertFalse($reglages->pour($d)['anonymat']);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalides(): iterable
    {
        yield 'clé inconnue' => [['inconnu' => 1]];
        yield 'durée nulle' => [['duree_jours' => 0]];
        yield 'seuil trop haut' => [['seuil_min_reponses' => 51]];
        yield 'durée non numérique' => [['duree_jours' => 'abc']];
    }

    /** @param array<string, mixed> $valeurs */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalides')]
    public function testValeursRefusees(array $valeurs): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);
        (new QuestionnaireReglages())->enregistrer(new StructureDepartement(), $valeurs);
    }

    public function testCreationRecopieLaPolitiqueSaufLAnonymatChoisi(): void
    {
        $d = (new StructureDepartement())->setOpt(['questionnaire' => ['anonymat' => false, 'modification_reponses' => false, 'seuil_min_reponses' => 3]]);
        $reglages = new QuestionnaireReglages();

        $q = (new Questionnaire())->setDepartement($d);
        $reglages->appliquerALaCreation($q, false);
        self::assertFalse($q->estAnonyme());
        self::assertFalse($q->autoriseModificationReponses());
        self::assertSame(3, $q->getOpt()['seuilMinReponses']);

        $choisi = (new Questionnaire())->setDepartement($d);
        $choisi->setOpt(['anonymous' => true]);
        $reglages->appliquerALaCreation($choisi, true);
        self::assertTrue($choisi->estAnonyme(), 'La valeur de l\'éditeur prime.');
    }

    public function testDateDeClotureParDefautALaPublication(): void
    {
        $d = (new StructureDepartement())->setOpt(['questionnaire' => ['duree_jours' => 10]]);
        $q = (new Questionnaire())->setDepartement($d)->setOpeningDate(new \DateTime('2027-01-05 09:00'));
        (new QuestionnaireReglages())->appliquerALaPublication($q);
        self::assertSame('2027-01-15 23:59:59', $q->getClosingDate()?->format('Y-m-d H:i:s'));

        $fixe = (new Questionnaire())->setDepartement($d)->setClosingDate(new \DateTime('2027-02-01'));
        (new QuestionnaireReglages())->appliquerALaPublication($fixe);
        self::assertSame('2027-02-01', $fixe->getClosingDate()?->format('Y-m-d'), 'Une date fixée est conservée.');
    }
}
