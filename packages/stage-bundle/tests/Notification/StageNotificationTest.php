<?php

namespace StageBundle\Tests\Notification;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Notification\NotificationService;
use App\Notification\NotificationType;
use PHPUnit\Framework\TestCase;
use StageBundle\Entity\Stages\StageAvenant;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;
use StageBundle\Entity\Stages\StageSoutenance;
use StageBundle\Enum\EtatStageEnum;
use StageBundle\Enum\TypeNotificationEnum;
use StageBundle\Service\Notification\StageNotificationTypes;
use StageBundle\Service\Notification\StageNotifier;

class StageNotificationTest extends TestCase
{
    /** @var list<array{0: Etudiant|Personnel, 1: string, 2: string, 3: ?string}> */
    private array $envois = [];

    private function notifier(): StageNotifier
    {
        $service = $this->createMock(NotificationService::class);
        $service->method('notifier')->willReturnCallback(function ($dest, $cle, $texte, $url = null) {
            $this->envois[] = [$dest, $cle, $texte, $url];

            return null;
        });

        return new StageNotifier($service);
    }

    private function etudiant(): Etudiant
    {
        return (new Etudiant())->setPrenom('Alice')->setNom('Dupont');
    }

    private function personnel(string $nom): Personnel
    {
        return (new Personnel())->setPrenom('P')->setNom($nom);
    }

    /** @return array{StageEtudiant, Etudiant, Personnel, Personnel} */
    private function dossier(EtatStageEnum $etat): array
    {
        $etudiant = $this->etudiant();
        $resp = $this->personnel('Responsable');
        $co = $this->personnel('CoResponsable');
        $periode = (new StagePeriode())->setLibelle('Stage BUT2')->setResponsablePrincipal($resp)->addCoResponsable($co);
        $stage = (new StageEtudiant())->setEtudiant($etudiant)->setStagePeriode($periode)->setEtatStage($etat);

        return [$stage, $etudiant, $resp, $co];
    }

    /** @return list<string> */
    private function cles(): array
    {
        return array_map(static fn (array $e) => $e[1], $this->envois);
    }

    public function testToutesLesDefinitionsSontValidesEtUniques(): void
    {
        $types = iterator_to_array((new StageNotificationTypes())->getTypes(), false);
        self::assertCount(\count(TypeNotificationEnum::cases()), $types);
        $cles = array_map(static fn (NotificationType $t) => $t->cle, $types);
        self::assertSame($cles, array_unique($cles));
        foreach ($types as $t) {
            self::assertSame('stage', $t->source, 'La source doit être le nom canonique du package (UserPackageResolver).');
            self::assertStringStartsWith('stage.', $t->cle);
        }
    }

    public function testDepotParLEtudiantPrevientLesResponsables(): void
    {
        [$stage, $etudiant, $resp, $co] = $this->dossier(EtatStageEnum::DEPOSE);
        $this->notifier()->dossierEnregistre($stage, EtatStageEnum::AUTORISE, null, $etudiant);

        self::assertSame(['stage.demande_deposee', 'stage.demande_deposee'], $this->cles());
        self::assertSame($resp, $this->envois[0][0]);
        self::assertSame($co, $this->envois[1][0]);
        self::assertStringContainsString('déposé', $this->envois[0][2]);
        self::assertStringStartsWith('/app/stage/responsable', (string) $this->envois[0][3]);
    }

    public function testLeResponsableAuteurNEstPasNotifie(): void
    {
        [$stage, , $resp, $co] = $this->dossier(EtatStageEnum::DEPOSE);
        $this->notifier()->dossierEnregistre($stage, EtatStageEnum::AUTORISE, null, $resp);

        self::assertSame(['stage.demande_deposee'], $this->cles());
        self::assertSame($co, $this->envois[0][0]);
    }

    public function testValidationPrevientLEtudiant(): void
    {
        [$stage, $etudiant, $resp] = $this->dossier(EtatStageEnum::VALIDE);
        $this->notifier()->dossierEnregistre($stage, EtatStageEnum::DEPOSE, null, $resp);

        self::assertSame(['stage.convention_validee'], $this->cles());
        self::assertSame($etudiant, $this->envois[0][0]);
        self::assertSame('/app/stage/etudiant', $this->envois[0][3]);
    }

    public function testPasDeNotificationSansChangementDeStatut(): void
    {
        [$stage, , $resp] = $this->dossier(EtatStageEnum::VALIDE);
        $this->notifier()->dossierEnregistre($stage, EtatStageEnum::VALIDE, null, $resp);

        self::assertSame([], $this->envois);
    }

    public function testImprimeePuisEnvoyeeNeNotifieQuUneFois(): void
    {
        [$stage, , $resp] = $this->dossier(EtatStageEnum::CONVENTION_ENVOYEE);
        $this->notifier()->dossierEnregistre($stage, EtatStageEnum::CONVENTION_IMPRIMEE, null, $resp);
        self::assertSame([], $this->envois);

        $this->notifier()->dossierEnregistre($stage, EtatStageEnum::VALIDE, null, $resp);
        self::assertSame(['stage.convention_a_signer'], $this->cles());
    }

    public function testTuteurAffecteEstPrevenuUneSeuleFois(): void
    {
        [$stage, , $resp] = $this->dossier(EtatStageEnum::VALIDE);
        $tuteur = $this->personnel('Tuteur');
        $stage->setTuteurUniversitaire($tuteur);

        $this->notifier()->dossierEnregistre($stage, EtatStageEnum::VALIDE, null, $resp);
        self::assertSame(['stage.tuteur_affecte'], $this->cles());
        self::assertSame($tuteur, $this->envois[0][0]);

        $this->envois = [];
        $this->notifier()->dossierEnregistre($stage, EtatStageEnum::VALIDE, $tuteur, $resp);
        self::assertSame([], $this->envois);
    }

    public function testAvenantDemandeEtValide(): void
    {
        [$stage, $etudiant, $resp] = $this->dossier(EtatStageEnum::CONVENTION_RECUE);
        $avenant = (new StageAvenant())->setStageEtudiant($stage)->setLibelle('Prolongation');

        $this->notifier()->avenantDemande($avenant, $etudiant);
        self::assertSame(['stage.avenant_demande', 'stage.avenant_demande'], $this->cles());

        $this->envois = [];
        $this->notifier()->avenantValide($avenant, $resp);
        self::assertSame(['stage.avenant_valide'], $this->cles());
        self::assertSame($etudiant, $this->envois[0][0]);
    }

    public function testSoutenancePrevientEtudiantTuteurEtAssesseurSansDoublon(): void
    {
        [$stage, $etudiant, $resp] = $this->dossier(EtatStageEnum::CONVENTION_RECUE);
        $tuteur = $this->personnel('Tuteur');
        $stage->setTuteurUniversitaire($tuteur);
        $soutenance = (new StageSoutenance())
            ->setStageEtudiant($stage)
            ->setEnseignantJury($tuteur)
            ->setDateSoutenance(new \DateTime('2027-06-15 14:30'))
            ->setSalle('B12');

        $this->notifier()->soutenancePlanifiee($soutenance, $resp);

        self::assertSame(['stage.soutenance_planifiee', 'stage.jury_soutenance'], $this->cles());
        self::assertSame($etudiant, $this->envois[0][0]);
        self::assertStringContainsString('15/06/2027 à 14:30 (salle B12)', $this->envois[0][2]);
    }
}
