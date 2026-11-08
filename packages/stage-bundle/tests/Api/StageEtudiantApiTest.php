<?php

namespace StageBundle\Tests\Api;

use StageBundle\Enum\EtatStageEnum;

/** Droits et règles sur /api/stage_etudiants. */
class StageEtudiantApiTest extends StageApiTestCase
{
    public function testUnAnonymeNaAccesAAucunDossier(): void
    {
        $periode = $this->periode($this->departement());
        $dossier = $this->dossier($periode, $this->etudiant());
        $this->enregistrer();

        self::assertSame(401, $this->requete('GET', '/api/stage_etudiants/'.$dossier->getId())->getStatusCode());
        self::assertContains($this->requete('GET', '/api/stage_etudiants')->getStatusCode(), [401, 403]);
    }

    public function testChacunNeVoitQueSesDossiers(): void
    {
        $deptA = $this->departement('A');
        $deptB = $this->departement('B');
        $gestionnaireA = $this->personnel($deptA, ['ROLE_STAGE_MANAGER']);
        $tuteur = $this->personnel($deptB);
        $alice = $this->etudiant();
        $bob = $this->etudiant();
        $dossierA = $this->dossier($this->periode($deptA), $alice, EtatStageEnum::DEPOSE);
        $dossierB = $this->dossier($this->periode($deptB), $bob, EtatStageEnum::DEPOSE, $tuteur);
        $this->enregistrer();

        $ids = fn ($user) => array_column($this->json($this->requete('GET', '/api/stage_etudiants?pagination=false', $user)), 'id');

        self::assertSame([$dossierA->getId()], $ids($alice), 'un étudiant ne voit que son dossier');
        self::assertSame([$dossierB->getId()], $ids($tuteur), 'un tuteur ne voit que ses stagiaires');
        self::assertContains($dossierA->getId(), $ids($gestionnaireA));
        self::assertNotContains($dossierB->getId(), $ids($gestionnaireA), 'le gestionnaire ne voit pas un autre département');

        self::assertSame(403, $this->requete('GET', '/api/stage_etudiants/'.$dossierB->getId(), $alice)->getStatusCode());
        self::assertSame(403, $this->requete('GET', '/api/stage_etudiants/'.$dossierB->getId(), $gestionnaireA)->getStatusCode());
        self::assertSame(200, $this->requete('GET', '/api/stage_etudiants/'.$dossierB->getId(), $tuteur)->getStatusCode());
    }

    public function testLeGestionnaireValideEtLesDatesSontPoseesParLeServeur(): void
    {
        $dept = $this->departement();
        $gestionnaire = $this->personnel($dept, ['ROLE_STAGE_MANAGER']);
        $dossier = $this->dossier($this->periode($dept), $this->etudiant(), EtatStageEnum::DEPOSE);
        $this->enregistrer();

        $reponse = $this->requete('PATCH', '/api/stage_etudiants/'.$dossier->getId(), $gestionnaire, [
            'etatStage' => EtatStageEnum::VALIDE->value,
            'dateValidation' => '2000-01-01T00:00:00+00:00', // ignorée : posée par le serveur
        ]);

        self::assertSame(200, $reponse->getStatusCode(), (string) $reponse->getContent());
        $donnees = $this->json($reponse);
        self::assertSame(EtatStageEnum::VALIDE->value, $donnees['etatStage']);
        self::assertStringStartsWith(date('Y-m-d'), (string) $donnees['dateValidation']);
        self::assertContains(EtatStageEnum::CONVENTION_IMPRIMEE->value, $donnees['transitionsPossibles']);
    }

    public function testUneTransitionImpossibleEstRefusee(): void
    {
        $dept = $this->departement();
        $gestionnaire = $this->personnel($dept, ['ROLE_STAGE_MANAGER']);
        $dossier = $this->dossier($this->periode($dept), $this->etudiant(), EtatStageEnum::REFUS);
        $this->enregistrer();

        $reponse = $this->requete('PATCH', '/api/stage_etudiants/'.$dossier->getId(), $gestionnaire, ['etatStage' => EtatStageEnum::CONVENTION_RECUE->value]);
        self::assertSame(422, $reponse->getStatusCode());
    }

    public function testLEtudiantDeposeMaisNePeutNiSeValiderNiSeNoter(): void
    {
        $dept = $this->departement();
        $alice = $this->etudiant();
        $dossier = $this->dossier($this->periode($dept), $alice);
        $this->enregistrer();
        $url = '/api/stage_etudiants/'.$dossier->getId();

        self::assertSame(403, $this->requete('PATCH', $url, $alice, ['etatStage' => EtatStageEnum::VALIDE->value])->getStatusCode());
        self::assertSame(403, $this->requete('PATCH', $url, $alice, ['evaluationNote' => 20])->getStatusCode());

        $depot = $this->requete('PATCH', $url, $alice, ['etatStage' => EtatStageEnum::DEPOSE->value, 'sujetStage' => 'Développement web']);
        self::assertSame(200, $depot->getStatusCode(), (string) $depot->getContent());
        self::assertNotNull($this->json($depot)['dateDepotFormulaire']);
    }

    public function testLesReglesLegalesBloquentUnDepotSansGratification(): void
    {
        $alice = $this->etudiant();
        $dossier = $this->dossier($this->periode($this->departement()), $alice)
            ->setDateFinStage(new \DateTime('2027-06-25')); // 60 jours ouvrés : gratification obligatoire
        $this->enregistrer();

        $reponse = $this->requete('PATCH', '/api/stage_etudiants/'.$dossier->getId(), $alice, ['etatStage' => EtatStageEnum::DEPOSE->value]);

        self::assertSame(422, $reponse->getStatusCode());
        self::assertSame(['gratificationMontant'], array_column($this->json($reponse)['violations'] ?? [], 'propertyPath'));
    }

    public function testUnTuteurNeSaisitQueLeSuiviEtLEvaluation(): void
    {
        $dept = $this->departement();
        $tuteur = $this->personnel($dept);
        $dossier = $this->dossier($this->periode($dept), $this->etudiant(), EtatStageEnum::CONVENTION_RECUE, $tuteur);
        $this->enregistrer();
        $url = '/api/stage_etudiants/'.$dossier->getId();

        self::assertSame(200, $this->requete('PATCH', $url, $tuteur, ['evaluationNote' => 15.5, 'evaluationCommentaire' => 'Bien'])->getStatusCode());
        self::assertSame(403, $this->requete('PATCH', $url, $tuteur, ['sujetStage' => 'autre'])->getStatusCode());
    }
}
