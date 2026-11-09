<?php

namespace StageBundle\Tests\Entity;

use App\Entity\Users\Etudiant;
use PHPUnit\Framework\TestCase;
use StageBundle\Entity\Stages\StageAvenant;
use StageBundle\Entity\Stages\StageContact;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StageEvaluationEntreprise;
use StageBundle\Enum\EtatStageEnum;

class StageDossierTest extends TestCase
{
    public function testLAnonymisationEffaceLesDonneesPersonnellesEtGardeLesStatistiques(): void
    {
        $stage = (new StageEtudiant())
            ->setEtudiant(new Etudiant())
            ->setTuteur((new StageContact())->setNom('Martin'))
            ->setSujetStage('Sujet')->setActivites('Activités')->setAmenagementStage('Tiers temps')
            ->setAssuranceNumero('123')->setEvaluationCommentaire('Bien')->setSuiviRencontres([['date' => '01/01']])
            ->setMotifRefus('motif')->setEvaluationNote(14.5)->setGratificationMontant(4.35)
            ->setEtatStage(EtatStageEnum::CONVENTION_RECUE)
            ->deposerRapport('2020/x.pdf', 'rapport.pdf');
        $stage->addAvenant((new StageAvenant())->setLibelle('A')->setTexte('B'));

        $stage->anonymiser();

        self::assertNull($stage->getEtudiant());
        self::assertNull($stage->getTuteur());
        self::assertNull($stage->getSujetStage());
        self::assertNull($stage->getAmenagementStage());
        self::assertNull($stage->getAssuranceNumero());
        self::assertNull($stage->getEvaluationCommentaire());
        self::assertNull($stage->getSuiviRencontres());
        self::assertNull($stage->getMotifRefus());
        self::assertNull($stage->getReportPath());
        self::assertCount(0, $stage->getAvenants());
        self::assertNotNull($stage->getAnonymiseLe());
        // conservé pour les statistiques
        self::assertSame(14.5, $stage->getEvaluationNote());
        self::assertSame(4.35, $stage->getGratificationMontant());
        self::assertSame(EtatStageEnum::CONVENTION_RECUE, $stage->getEtatStage());
    }

    public function testLesTransitionsSontExposeesAuxEcrans(): void
    {
        $stage = (new StageEtudiant())->setEtatStage(EtatStageEnum::DEPOSE);
        self::assertContains(EtatStageEnum::VALIDE->value, $stage->getTransitionsPossibles());
        self::assertNotContains(EtatStageEnum::CONVENTION_RECUE->value, $stage->getTransitionsPossibles());
    }

    public function testLeJetonDEvaluationNestJamaisStockeEnClair(): void
    {
        $evaluation = new StageEvaluationEntreprise(new StageEtudiant());
        $jeton = $evaluation->emettreJeton('tuteur@exemple.test');

        self::assertGreaterThanOrEqual(40, \strlen($jeton));
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $jeton, 'utilisable tel quel dans une URL');
        $hash = (new \ReflectionProperty($evaluation, 'jetonHash'))->getValue($evaluation);
        self::assertSame(StageEvaluationEntreprise::empreinte($jeton), $hash);
        self::assertNotSame($jeton, $hash);
        self::assertFalse($evaluation->estExpire());

        $autre = $evaluation->emettreJeton('tuteur@exemple.test');
        self::assertNotSame($jeton, $autre, 'un renvoi produit un nouveau jeton');
    }

    public function testUneEvaluationSoumiseEstFigee(): void
    {
        $evaluation = new StageEvaluationEntreprise(new StageEtudiant());
        $evaluation->emettreJeton('t@e.test');
        self::assertFalse($evaluation->estSoumise());
        $evaluation->soumettre([['critere' => 1, 'libelle' => 'Autonomie', 'niveau' => 3, 'commentaire' => null]], 'Bien', 15.0, 'M. X');
        self::assertTrue($evaluation->estSoumise());
        self::assertSame(15.0, $evaluation->getNoteProposee());
    }
}
