<?php

namespace FinanceBundle\Tests\Api;

use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceReception;
use FinanceBundle\Enum\StatutCommandeEnum;

/** Droits d'accès à l'API du package Finances : profils, départements, champs fixés par le serveur, documents. */
final class FinanceSecuriteApiTest extends FinanceApiTestCase
{
    public function testUnVisiteurAnonymeNAccedeANRien(): void
    {
        $dept = $this->departement();
        $c = $this->commande($dept, $this->personnel($dept, ['ROLE_FINANCE_DEMANDEUR']));
        $this->em->flush();

        foreach ([
            ['GET', '/api/finance_bon_commandes'],
            ['GET', $this->iri('finance_bon_commandes', $c->getId())],
            ['GET', '/api/finance_fournisseurs'],
            ['GET', '/api/finance_receptions'],
            ['GET', sprintf('/api/finance_bon_commandes/%d/pdf', $c->getId())],
            ['GET', sprintf('/api/finance_bon_commandes/%d/download-signed-pdf', $c->getId())],
            ['DELETE', $this->iri('finance_bon_commandes', $c->getId())],
        ] as [$methode, $uri]) {
            self::assertSame(401, $this->requete($methode, $uri)->getStatusCode(), "$methode $uri");
        }
        self::assertSame(401, $this->requete('POST', '/api/finance_bon_commandes', null, ['objetDemande' => 'x', 'montantHt' => '1'])->getStatusCode());
        self::assertSame(401, $this->requete('POST', sprintf('/api/finance_bon_commandes/%d/upload-signed-pdf', $c->getId()), null, null, ['file' => $this->pdf()])->getStatusCode());
        $admin = $this->personnel($this->em->find($dept::class, $dept->getId()), ['SUPER_ADMIN']);
        $this->em->flush();
        self::assertSame(404, $this->requete('GET', sprintf('/api/finance_bon_commandes/%d/html', $c->getId()), $admin)->getStatusCode(), 'route de débogage supprimée');
    }

    public function testLaListeNeContientQueCeQueLOnPeutVoir(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $demandeur = $this->personnel($a, ['ROLE_FINANCE_DEMANDEUR']);
        $collegue = $this->personnel($a, ['ROLE_FINANCE_DEMANDEUR']);
        $gestionnaireA = $this->personnel($a, ['ROLE_FINANCE_MANAGER']);
        $valideurA = $this->personnel($a, ['ROLE_FINANCE_VALIDEUR']);
        $gestionnaireB = $this->personnel($b, ['ROLE_FINANCE_MANAGER']);
        $responsable = $this->personnel($b);
        $admin = $this->personnel($b, ['SUPER_ADMIN']);

        $mienne = $this->commande($a, $demandeur);
        $autre = $this->commande($a, $collegue);
        $autre->setResponsable($responsable);
        $dansB = $this->commande($b, $gestionnaireB);
        $this->em->flush();

        $voit = fn ($user): array => $this->ids($this->requete('GET', '/api/finance_bon_commandes?itemsPerPage=100', $user));

        self::assertEqualsCanonicalizing([$mienne->getId()], array_intersect($voit($demandeur), [$mienne->getId(), $autre->getId(), $dansB->getId()]));
        self::assertEqualsCanonicalizing([$mienne->getId(), $autre->getId(), $dansB->getId()], array_values(array_intersect($voit($gestionnaireA), [$mienne->getId(), $autre->getId(), $dansB->getId()])));
        self::assertEqualsCanonicalizing([$mienne->getId(), $autre->getId()], array_values(array_intersect($voit($valideurA), [$mienne->getId(), $autre->getId(), $dansB->getId()])));
        // Service financier : toutes les demandes de l'établissement, quel que soit le département de son rôle.
        self::assertEqualsCanonicalizing([$mienne->getId(), $autre->getId(), $dansB->getId()], array_values(array_intersect($voit($gestionnaireB), [$mienne->getId(), $autre->getId(), $dansB->getId()])));
        self::assertEqualsCanonicalizing([$autre->getId()], array_values(array_intersect($voit($responsable), [$mienne->getId(), $autre->getId(), $dansB->getId()])));
        self::assertEqualsCanonicalizing([$mienne->getId(), $autre->getId(), $dansB->getId()], array_values(array_intersect($voit($admin), [$mienne->getId(), $autre->getId(), $dansB->getId()])));

        // Lecture d'une demande hors périmètre (le valideur reste limité à ses départements)
        self::assertSame(200, $this->requete('GET', $this->iri('finance_bon_commandes', $mienne->getId()), $gestionnaireB)->getStatusCode());
        self::assertSame(403, $this->requete('GET', $this->iri('finance_bon_commandes', $dansB->getId()), $valideurA)->getStatusCode());
        self::assertSame(403, $this->requete('GET', $this->iri('finance_bon_commandes', $mienne->getId()), $collegue)->getStatusCode());
        self::assertSame(200, $this->requete('GET', $this->iri('finance_bon_commandes', $mienne->getId()), $gestionnaireA)->getStatusCode());
    }

    public function testLaListeEstPaginee(): void
    {
        $dept = $this->departement();
        $gestionnaire = $this->personnel($dept, ['ROLE_FINANCE_MANAGER']);
        for ($i = 0; $i < 3; ++$i) {
            $this->commande($dept, $gestionnaire);
        }
        $this->em->flush();

        $reponse = $this->requete('GET', sprintf('/api/finance_bon_commandes?itemsPerPage=2&departement.id=%d', $dept->getId()), $gestionnaire);
        self::assertCount(2, $this->ids($reponse));
        self::assertSame(3, $this->json($reponse)['totalItems']);
    }

    public function testCreationLeServeurFixeDemandeurStatutEtDate(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $demandeur = $this->personnel($a, ['ROLE_FINANCE_DEMANDEUR']);
        $autre = $this->personnel($a, ['ROLE_FINANCE_DEMANDEUR']);
        $this->em->flush();

        $corps = [
            'objetDemande' => 'Câbles',
            'montantHt' => '42.00',
            'departement' => $this->iri('structure_departements', $a->getId()),
        ];
        // Champs fixés par le serveur : refusés explicitement.
        foreach ([
            ['demandeur' => $this->iri('personnels', $autre->getId())],
            ['statut' => 'PAYE'],
            ['dateDemande' => '2001-01-01T00:00:00+00:00'],
            ['pdfFilePath' => '../.env'],
        ] as $interdit) {
            self::assertSame(403, $this->requete('POST', '/api/finance_bon_commandes', $demandeur, $corps + $interdit)->getStatusCode(), json_encode($interdit));
        }
        $reponse = $this->requete('POST', '/api/finance_bon_commandes', $demandeur, $corps);
        self::assertSame(201, $reponse->getStatusCode(), (string) $reponse->getContent());
        $data = $this->json($reponse);
        self::assertSame('BROUILLON', $data['statut']);
        self::assertFalse($data['pdfSigne']);
        self::assertArrayNotHasKey('pdfFilePath', $data);

        $this->em->clear();
        $c = $this->em->find(FinanceBonCommande::class, $data['id']);
        self::assertSame($demandeur->getId(), $c->getDemandeur()->getId());
        self::assertNull($c->getPdfFilePath());
        self::assertSame(date('Y-m-d'), $c->getDateDemande()->format('Y-m-d'));

        // Pas le rôle de demandeur dans le département B
        self::assertSame(403, $this->requete('POST', '/api/finance_bon_commandes', $demandeur, [
            'objetDemande' => 'x', 'montantHt' => '1', 'departement' => $this->iri('structure_departements', $b->getId()),
        ])->getStatusCode());
        // Département obligatoire
        self::assertSame(422, $this->requete('POST', '/api/finance_bon_commandes', $demandeur, ['objetDemande' => 'x', 'montantHt' => '1'])->getStatusCode());
        // Un simple accès au package ne permet pas de créer
        $simple = $this->personnel($this->em->find($a::class, $a->getId()));
        $this->em->flush();
        self::assertSame(403, $this->requete('POST', '/api/finance_bon_commandes', $simple, [
            'objetDemande' => 'x', 'montantHt' => '1', 'departement' => $this->iri('structure_departements', $a->getId()),
        ])->getStatusCode());
    }

    public function testModificationChampsProtegesEtPerimetre(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $demandeur = $this->personnel($a, ['ROLE_FINANCE_DEMANDEUR']);
        $gestionnaire = $this->personnel($a, ['ROLE_FINANCE_MANAGER']);
        $c = $this->commande($a, $demandeur);
        $this->em->flush();
        $id = $c->getId();

        // Champs fixés par le serveur : refusés explicitement, rien n'est modifié.
        foreach ([
            ['departement' => $this->iri('structure_departements', $b->getId())],
            ['demandeur' => $this->iri('personnels', $gestionnaire->getId())],
            ['pdfFilePath' => '../.env'],
            ['statut' => 'PAYE'],
            ['dateVisaEngagement' => '2020-01-01'],
            ['objetDemande' => 'x', 'avisDirecteur' => 'FAVORABLE'],
        ] as $corps) {
            $reponse = $this->requete('PATCH', $this->iri('finance_bon_commandes', $id), $gestionnaire, $corps);
            self::assertSame(403, $reponse->getStatusCode(), json_encode($corps).' '.$reponse->getContent());
        }
        $reponse = $this->requete('PATCH', $this->iri('finance_bon_commandes', $id), $gestionnaire, ['objetDemande' => 'Nouvel objet']);
        self::assertSame(200, $reponse->getStatusCode(), (string) $reponse->getContent());

        $this->em->clear();
        $c = $this->em->find(FinanceBonCommande::class, $id);
        self::assertSame('Nouvel objet', $c->getObjetDemande());
        self::assertSame($a->getId(), $c->getDepartement()->getId());
        self::assertSame($demandeur->getId(), $c->getDemandeur()->getId());
        self::assertSame(StatutCommandeEnum::BROUILLON, $c->getStatut());
        self::assertNull($c->getPdfFilePath());

        $gestionnaireB = $this->personnel($this->em->find($b::class, $b->getId()), ['ROLE_FINANCE_MANAGER']);
        $collegue = $this->personnel($this->em->find($a::class, $a->getId()), ['ROLE_FINANCE_DEMANDEUR']);
        $this->em->flush();
        self::assertSame(200, $this->requete('PATCH', $this->iri('finance_bon_commandes', $id), $gestionnaireB, ['objetDemande' => 'x'])->getStatusCode(), 'service financier : tout l\'établissement');
        self::assertSame(403, $this->requete('PATCH', $this->iri('finance_bon_commandes', $id), $collegue, ['objetDemande' => 'x'])->getStatusCode());
    }

    public function testSuppression(): void
    {
        $dept = $this->departement();
        $demandeur = $this->personnel($dept, ['ROLE_FINANCE_DEMANDEUR']);
        $brouillon = $this->commande($dept, $demandeur);
        $soumise = $this->commande($dept, $demandeur, StatutCommandeEnum::SOUMIS);
        $this->em->flush();

        self::assertSame(403, $this->requete('DELETE', $this->iri('finance_bon_commandes', $soumise->getId()), $demandeur)->getStatusCode());
        // Le bouton « Supprimer » suit le droit calculé par le serveur : brouillon seulement pour le demandeur
        self::assertFalse($this->json($this->requete('GET', $this->iri('finance_bon_commandes', $soumise->getId()), $demandeur))['droits']['supprimer']);
        self::assertTrue($this->json($this->requete('GET', $this->iri('finance_bon_commandes', $brouillon->getId()), $demandeur))['droits']['supprimer']);
        self::assertSame(204, $this->requete('DELETE', $this->iri('finance_bon_commandes', $brouillon->getId()), $demandeur)->getStatusCode());
        $gestionnaire = $this->personnel($this->em->find($dept::class, $dept->getId()), ['ROLE_FINANCE_MANAGER']);
        $this->em->flush();
        self::assertSame(204, $this->requete('DELETE', $this->iri('finance_bon_commandes', $soumise->getId()), $gestionnaire)->getStatusCode());
    }

    public function testDocumentSigneStockeHorsWebrootEtControle(): void
    {
        $dept = $this->departement();
        $demandeur = $this->personnel($dept, ['ROLE_FINANCE_DEMANDEUR']);
        $etranger = $this->personnel($this->departement('Autre'), ['ROLE_FINANCE_DEMANDEUR']);
        $c = $this->commande($dept, $demandeur);
        $this->em->flush();
        $id = $c->getId();
        $depot = sprintf('/api/finance_bon_commandes/%d/upload-signed-pdf', $id);
        $lecture = sprintf('/api/finance_bon_commandes/%d/download-signed-pdf', $id);

        // Contenu non PDF malgré l'extension
        self::assertSame(422, $this->requete('POST', $depot, $demandeur, null, ['file' => $this->fichier('<script>alert(1)</script>', 'piege.pdf')])->getStatusCode());
        self::assertSame(403, $this->requete('POST', $depot, $etranger, null, ['file' => $this->pdf()])->getStatusCode());

        $reponse = $this->requete('POST', $depot, $demandeur, null, ['file' => $this->pdf()]);
        self::assertSame(200, $reponse->getStatusCode(), (string) $reponse->getContent());
        self::assertSame('BROUILLON', $this->json($reponse)['statut'], 'le dépôt ne soumet pas la demande');

        $this->em->clear();
        $chemin = $this->em->find(FinanceBonCommande::class, $id)->getPdfFilePath();
        self::assertMatchesRegularExpression('#^\d{4}/[0-9a-f-]{36}\.pdf$#', $chemin);
        self::assertFileExists(self::$dossierDocuments.'/'.$chemin);

        self::assertSame(403, $this->requete('GET', $lecture, $etranger)->getStatusCode());
        $reponse = $this->requete('GET', $lecture, $demandeur);
        self::assertSame(200, $reponse->getStatusCode());
        self::assertSame('application/pdf', $reponse->headers->get('Content-Type'));

        // Un chemin forgé en base ne sort jamais du dossier des documents.
        foreach (['../.env', '../../back/.env', 'uploads/finance/../../../.env', '/etc/passwd'] as $forge) {
            $this->em->getConnection()->executeStatement('UPDATE finance_bon_commande SET pdf_file_path = ? WHERE id = ?', [$forge, $id]);
            self::assertSame(404, $this->requete('GET', $lecture, $demandeur)->getStatusCode(), $forge);
        }
    }

    public function testReferentielDesFournisseurs(): void
    {
        $dept = $this->departement();
        $demandeur = $this->personnel($dept, ['ROLE_FINANCE_DEMANDEUR']);
        $gestionnaire = $this->personnel($dept, ['ROLE_FINANCE_MANAGER']);
        $f = $this->fournisseur();
        $c = $this->commande($dept, $demandeur);
        $c->setFournisseur($f);
        $this->em->flush();

        self::assertSame(200, $this->requete('GET', '/api/finance_fournisseurs', $demandeur)->getStatusCode());
        self::assertSame(403, $this->requete('POST', '/api/finance_fournisseurs', $demandeur, ['nomFournisseur' => 'X'])->getStatusCode());
        self::assertSame(403, $this->requete('PATCH', $this->iri('finance_fournisseurs', $f->getId()), $demandeur, ['nomFournisseur' => 'X'])->getStatusCode());
        self::assertSame(201, $this->requete('POST', '/api/finance_fournisseurs', $gestionnaire, ['nomFournisseur' => $this->unique('NOUVEAU')])->getStatusCode());

        // Renommer une fiche existante au travers de sa demande : impossible (le fournisseur se désigne par IRI).
        $reponse = $this->requete('PATCH', $this->iri('finance_bon_commandes', $c->getId()), $demandeur, [
            'fournisseur' => ['@id' => $this->iri('finance_fournisseurs', $f->getId()), 'nomFournisseur' => 'PIRATE'],
        ]);
        self::assertSame(400, $reponse->getStatusCode(), (string) $reponse->getContent());
        $this->em->clear();
        self::assertNotSame('PIRATE', $this->em->find($f::class, $f->getId())->getNomFournisseur());
    }

    public function testUneReceptionNePeutPasChangerDeDemande(): void
    {
        $dept = $this->departement();
        $demandeur = $this->personnel($dept, ['ROLE_FINANCE_DEMANDEUR']);
        $autreDemandeur = $this->personnel($dept, ['ROLE_FINANCE_DEMANDEUR']);
        $sienne = $this->commande($dept, $autreDemandeur);
        $reception = (new FinanceReception())->setBonCommande($sienne);
        $this->em->persist($reception);
        $mienne = $this->commande($dept, $demandeur);
        $this->em->flush();

        self::assertSame(403, $this->requete('GET', $this->iri('finance_receptions', $reception->getId()), $demandeur)->getStatusCode());
        self::assertNotContains($reception->getId(), $this->ids($this->requete('GET', '/api/finance_receptions?itemsPerPage=100', $demandeur)));

        $reponse = $this->requete('PATCH', $this->iri('finance_bon_commandes', $mienne->getId()), $demandeur, [
            'receptions' => [['@id' => $this->iri('finance_receptions', $reception->getId()), 'qualiteReceptionnaire' => 'détournée']],
        ]);
        // Les réceptions ne s'écrivent plus au travers de la demande (constatation par transition).
        self::assertSame(403, $reponse->getStatusCode(), (string) $reponse->getContent());
        $this->em->clear();
        $intacte = $this->em->find(FinanceReception::class, $reception->getId());
        self::assertSame($sienne->getId(), $intacte->getBonCommande()->getId());
        self::assertNull($intacte->getQualiteReceptionnaire());
    }
}
