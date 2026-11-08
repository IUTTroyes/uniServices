<?php

namespace StageBundle\Tests\Service;

use App\Entity\Etablissement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;
use StageBundle\Service\StageReglesLegales;

class StageReglesLegalesTest extends TestCase
{
    private function regles(): StageReglesLegales
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn(new Etablissement()); // taux par défaut : 4,35 €/h
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        return new StageReglesLegales($em);
    }

    private function stage(string $debut, string $fin, int $jours = 0): StageEtudiant
    {
        return (new StageEtudiant())->setDateDebutStage(new \DateTime($debut))->setDateFinStage(new \DateTime($fin))
            ->setDureeJoursStage($jours)->setDureeHebdomadaire(35.0);
    }

    /** @return list<string> */
    private function champs(StageEtudiant $stage): array
    {
        return array_column($this->regles()->verifier($stage), 'champ');
    }

    public function testUnStageCourtSansGratificationEstValide(): void
    {
        self::assertSame([], $this->champs($this->stage('2027-04-05', '2027-05-28'))); // 40 jours ouvrés
    }

    public function testAuDelaDeDeuxMoisLaGratificationEstObligatoire(): void
    {
        $stage = $this->stage('2027-04-05', '2027-06-25'); // 60 jours ouvrés
        self::assertSame(['gratificationMontant'], $this->champs($stage));

        $stage->setGratification(true)->setGratificationMontant(4.0);
        self::assertSame(['gratificationMontant'], $this->champs($stage), 'sous le minimum légal');

        $stage->setGratificationMontant(4.35);
        self::assertSame([], $this->champs($stage));
    }

    public function testLaGratificationMensuelleEstComparéeAuMinimumMensuel(): void
    {
        $stage = $this->stage('2027-04-05', '2027-06-25')->setGratification(true)->setGratificationPeriode('M')->setGratificationMontant(600.0);
        self::assertSame(['gratificationMontant'], $this->champs($stage), '4,35 × 35 h × 52 / 12 ≈ 659,75 €');

        $stage->setGratificationMontant(660.0);
        self::assertSame([], $this->champs($stage));
    }

    public function testUnStageNePeutDepasserSixMois(): void
    {
        $stage = $this->stage('2027-01-04', '2027-08-27', 140)->setGratification(true)->setGratificationMontant(5.0);
        self::assertSame(['dureeJoursStage'], $this->champs($stage));
    }

    public function testDatesIncoherentesEtHorsPeriode(): void
    {
        self::assertSame(['dateFinStage'], $this->champs($this->stage('2027-05-01', '2027-04-01')));

        $periode = (new StagePeriode())->setLibelle('Stage BUT2')->setDateDebut(new \DateTime('2027-04-01'))->setDateFin(new \DateTime('2027-06-30'));
        $stage = $this->stage('2027-03-25', '2027-04-30')->setStagePeriode($periode);
        self::assertSame(['dateDebutStage'], $this->champs($stage));

        $periode->setDatesFlexibles(true);
        self::assertSame([], $this->champs($stage));
    }

    public function testDureeHebdomadaire(): void
    {
        self::assertSame(['dureeHebdomadaire'], $this->champs($this->stage('2027-04-05', '2027-04-30')->setDureeHebdomadaire(50.0)));
    }
}
