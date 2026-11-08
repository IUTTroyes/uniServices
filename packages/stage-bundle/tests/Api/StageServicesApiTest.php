<?php

namespace StageBundle\Tests\Api;

use StageBundle\Entity\Stages\StageContact;
use StageBundle\Entity\Stages\StageEvaluationCritere;
use StageBundle\Entity\Stages\StageEvaluationEntreprise;
use StageBundle\Enum\EtatStageEnum;

/** Rapport, avenants, évaluation entreprise, périodes gérées, soutenances et modèle de convention. */
class StageServicesApiTest extends StageApiTestCase
{
    public function testRapportDeposeTelechargeEtRetire(): void
    {
        $dept = $this->departement();
        $tuteur = $this->personnel($dept);
        $alice = $this->etudiant();
        $intrus = $this->etudiant();
        $dossier = $this->dossier($this->periode($dept), $alice, EtatStageEnum::CONVENTION_RECUE, $tuteur);
        $this->enregistrer();
        $url = '/api/stage_etudiants/'.$dossier->getId().'/rapport';

        self::assertSame(403, $this->requete('POST', $url, $intrus, null, ['file' => $this->pdf()])->getStatusCode());

        $depot = $this->requete('POST', $url, $alice, null, ['file' => $this->pdf('Mon rapport.exe')]);
        self::assertSame(201, $depot->getStatusCode(), (string) $depot->getContent());
        self::assertSame('Mon rapport.pdf', $this->json($depot)['reportName']);

        self::assertSame(403, $this->requete('GET', $url, $intrus)->getStatusCode());
        $telechargement = $this->requete('GET', $url, $tuteur);
        self::assertSame(200, $telechargement->getStatusCode());
        self::assertStringContainsString('attachment', (string) $telechargement->headers->get('Content-Disposition'));
        self::assertStringContainsString('Rapport-stage_', (string) $telechargement->headers->get('Content-Disposition'));
        self::assertStringContainsString('sandbox', (string) $telechargement->headers->get('Content-Security-Policy'));

        self::assertSame(200, $this->requete('DELETE', $url, $alice)->getStatusCode());
        self::assertSame(404, $this->requete('GET', $url, $tuteur)->getStatusCode());
    }

    public function testLeRapportNEstPasAccepteAvantLaValidation(): void
    {
        $alice = $this->etudiant();
        $dossier = $this->dossier($this->periode($this->departement()), $alice, EtatStageEnum::DEPOSE);
        $this->enregistrer();

        self::assertSame(409, $this->requete('POST', '/api/stage_etudiants/'.$dossier->getId().'/rapport', $alice, null, ['file' => $this->pdf()])->getStatusCode());
    }

    public function testAvenantDemandeParLEtudiantPuisValideParLeGestionnaire(): void
    {
        $dept = $this->departement();
        $gestionnaire = $this->personnel($dept, ['ROLE_STAGE_MANAGER']);
        $alice = $this->etudiant();
        $nonSigne = $this->dossier($this->periode($dept), $alice, EtatStageEnum::VALIDE);
        $signe = $this->dossier($this->periode($dept), $alice, EtatStageEnum::CONVENTION_RECUE);
        $this->enregistrer();
        $avenant = fn ($dossier, bool $valide = false) => [
            'stageEtudiant' => '/api/stage_etudiants/'.$dossier->getId(),
            'libelle' => 'Prolongation', 'texte' => 'Deux semaines de plus', 'typeModification' => 'DATES',
            'newDateFin' => '2027-06-11', 'valide' => $valide,
        ];

        self::assertSame(422, $this->requete('POST', '/api/stage_avenants', $alice, $avenant($nonSigne))->getStatusCode(), 'avant signature, on modifie la demande');

        $cree = $this->requete('POST', '/api/stage_avenants', $alice, $avenant($signe, true));
        self::assertSame(201, $cree->getStatusCode(), (string) $cree->getContent());
        $id = $this->json($cree)['id'];
        self::assertFalse($this->json($cree)['valide'], 'un étudiant ne peut pas créer un avenant déjà validé');

        self::assertSame(403, $this->requete('POST', '/api/stage_avenants/'.$id.'/validate', $alice)->getStatusCode());
        self::assertSame(200, $this->requete('POST', '/api/stage_avenants/'.$id.'/validate', $gestionnaire)->getStatusCode());
        $dossier = $this->json($this->requete('GET', '/api/stage_etudiants/'.$signe->getId(), $alice));
        self::assertStringStartsWith('2027-06-11', (string) $dossier['dateFinStage']);
    }

    public function testEvaluationParLEntrepriseDeBoutEnBout(): void
    {
        $dept = $this->departement();
        $tuteur = $this->personnel($dept);
        $periode = $this->periode($dept);
        $dossier = $this->dossier($periode, $this->etudiant(), EtatStageEnum::CONVENTION_RECUE, $tuteur)
            ->setTuteur((new StageContact())->setNom('Durand')->setEmail('durand@entreprise.test'));
        $critere = (new StageEvaluationCritere())->setLibelle('Autonomie');
        $periode->addCritereEvaluation($critere);
        $this->em->persist($critere);
        $this->enregistrer();

        $envoi = $this->requete('POST', '/api/stage_etudiants/'.$dossier->getId().'/evaluation-entreprise/envoyer', $tuteur);
        self::assertSame(200, $envoi->getStatusCode(), (string) $envoi->getContent());
        self::assertSame('durand@entreprise.test', $this->json($envoi)['envoyeA']);

        // Le jeton n'existe que dans l'e-mail : on en émet un connu pour simuler le clic du tuteur.
        $evaluation = $this->em->getRepository(StageEvaluationEntreprise::class)->findOneBy(['stageEtudiant' => $dossier->getId()]);
        $jeton = $evaluation->emettreJeton('durand@entreprise.test');
        $this->em->flush();
        $public = '/api/stage/evaluation-entreprise/'.$jeton;

        self::assertSame('ouverte', $this->json($this->requete('GET', $public))['statut'], 'sans authentification');
        self::assertSame(422, $this->requete('POST', $public, null, ['evaluateurNom' => 'M. Durand', 'reponses' => []])->getStatusCode());
        self::assertSame(200, $this->requete('POST', $public, null, [
            'evaluateurNom' => 'M. Durand', 'reponses' => [(string) $critere->getId() => ['niveau' => 4]], 'noteProposee' => 17,
        ])->getStatusCode());
        self::assertSame(404, $this->requete('POST', $public, null, ['evaluateurNom' => 'x'])->getStatusCode(), 'lien à usage unique');
        self::assertSame(404, $this->requete('GET', '/api/stage/evaluation-entreprise/'.str_repeat('x', 43))->getStatusCode());

        $vue = $this->json($this->requete('GET', '/api/stage_etudiants/'.$dossier->getId().'/evaluation-entreprise', $tuteur));
        self::assertSame('soumise', $vue['statut']);
        self::assertSame(17, (int) $vue['noteProposee']);
    }

    public function testLesPeriodesGereesNeMelangentPasLesDepartements(): void
    {
        $deptA = $this->departement('A');
        $deptB = $this->departement('B');
        $gestionnaireA = $this->personnel($deptA, ['ROLE_STAGE_MANAGER']);
        $responsableB = $this->personnel($deptB);
        $periodeA = $this->periode($deptA);
        $periodeB = $this->periode($deptB, $responsableB);
        $this->enregistrer();

        $ids = fn ($user) => array_column($this->json($this->requete('GET', '/api/stage_periodes?geres=1&pagination=false', $user)), 'id');
        self::assertContains($periodeA->getId(), $ids($gestionnaireA));
        self::assertNotContains($periodeB->getId(), $ids($gestionnaireA));
        self::assertSame([$periodeB->getId()], $ids($responsableB), 'responsable de la seule période B');

        self::assertSame(403, $this->requete('PATCH', '/api/stage_periodes/'.$periodeB->getId(), $gestionnaireA, ['libelle' => 'x'])->getStatusCode());
    }

    public function testLesSoutenancesSontReserveesAuGestionnaireDuDepartement(): void
    {
        $deptA = $this->departement('A');
        $deptB = $this->departement('B');
        $gestionnaireA = $this->personnel($deptA, ['ROLE_STAGE_MANAGER']);
        $gestionnaireB = $this->personnel($deptB, ['ROLE_STAGE_MANAGER']);
        $periodeB = $this->periode($deptB);
        $this->enregistrer();
        $creneaux = ['stagePeriodeId' => $periodeB->getId(), 'date' => '2027-06-28', 'startTime' => '09:00', 'endTime' => '10:00', 'duree' => 30];

        self::assertSame(403, $this->requete('POST', '/api/stage_soutenances/bulk-generate', $gestionnaireA, $creneaux)->getStatusCode());
        self::assertSame(400, $this->requete('POST', '/api/stage_soutenances/bulk-generate', $gestionnaireB, [...$creneaux, 'duree' => 0])->getStatusCode(), 'durée nulle refusée');
        $genere = $this->requete('POST', '/api/stage_soutenances/bulk-generate', $gestionnaireB, $creneaux);
        self::assertSame(2, $this->json($genere)['slotsCreated'] ?? null, (string) $genere->getContent());
    }

    public function testLeModeleDeConventionEstReserveAuSuperAdmin(): void
    {
        $dept = $this->departement();
        $gestionnaire = $this->personnel($dept, ['ROLE_STAGE_MANAGER']);
        $admin = $this->personnel($this->departement(), ['SUPER_ADMIN']);
        $this->dossier($this->periode($dept), $this->etudiant(), EtatStageEnum::VALIDE);
        $this->enregistrer();

        self::assertSame(403, $this->requete('GET', '/api/stage/modele-convention/balises', $gestionnaire)->getStatusCode());
        self::assertSame(403, $this->requete('POST', '/api/stage_convention_templates', $gestionnaire, ['code' => 'default_convention', 'libelle' => 'x', 'texte' => 'y'], [], 'application/ld+json')->getStatusCode());

        $apercu = $this->requete('POST', '/api/stage/modele-convention/apercu', $admin, ['texte' => '<html><body>{etudiant.prenom} {etudiant.nom}</body></html>']);
        self::assertSame(200, $apercu->getStatusCode(), (string) $apercu->getContent());
        self::assertStringContainsString('Alice', $this->json($apercu)['html']);
    }
}
