<?php

namespace QuestionnaireBundle\Tests\Domain;

use PHPUnit\Framework\TestCase;
use QuestionnaireBundle\Domain\Questionnaire\Publish\PublishQuestionnaireService;
use QuestionnaireBundle\Domain\Questionnaire\Runtime\InvitationRuntime;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** Règles portées par le questionnaire et la publication. */
final class QuestionnaireRegleMetierTest extends TestCase
{
    public function testOuvertureSelonStatutEtDates(): void
    {
        $maintenant = new \DateTime('2027-03-10 12:00');
        $q = (new Questionnaire())->setStatus(QuestStatutEnum::DRAFT);
        self::assertFalse($q->estOuvert($maintenant), 'Brouillon.');

        $q->setStatus(QuestStatutEnum::PUBLISHED);
        self::assertTrue($q->estOuvert($maintenant), 'Publié sans dates.');
        $q->setOpeningDate(new \DateTime('2027-03-11'));
        self::assertFalse($q->estOuvert($maintenant), 'Pas encore ouvert.');
        $q->setOpeningDate(null)->setClosingDate(new \DateTime('2027-03-09'));
        self::assertFalse($q->estOuvert($maintenant), 'Date de clôture passée.');
        $q->setClosingDate(null)->setStatus(QuestStatutEnum::CLOSED);
        self::assertFalse($q->estOuvert($maintenant), 'Clôturé.');
    }

    public function testSeuilSeulementPourUnQuestionnaireAnonyme(): void
    {
        $q = new Questionnaire();
        $q->setOpt(['anonymous' => true, 'seuilMinReponses' => 5]);
        self::assertSame(5, $q->seuilResultats());
        $q->setOpt(['anonymous' => false]);
        self::assertSame(0, $q->seuilResultats(), 'Questionnaire nominatif : pas de seuil.');
    }

    public function testAdressesDedoublonneesEtValidees(): void
    {
        self::assertSame(['a@exemple.fr', 'b@exemple.fr'], PublishQuestionnaireService::adresses([' A@Exemple.fr', 'a@exemple.fr', '', 'b@exemple.fr']));
        $this->expectException(UnprocessableEntityHttpException::class);
        PublishQuestionnaireService::adresses(['pas-une-adresse']);
    }

    public function testReponseVide(): void
    {
        foreach ([null, '', '   ', []] as $vide) {
            self::assertTrue(InvitationRuntime::estVide($vide));
        }
        foreach ([0, '0', 'non', ['a']] as $plein) {
            self::assertFalse(InvitationRuntime::estVide($plein));
        }
    }
}
