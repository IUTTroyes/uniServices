<?php

namespace FinanceBundle\Tests\Api;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Export Excel du tableau de bord : lignes reçues dans l'ordre, limitées à ce que l'utilisateur peut voir. */
final class FinanceExportApiTest extends FinanceApiTestCase
{
    private const URI = '/api/finance_bon_commandes/export-excel';

    public function testExporteLesLignesDemandeesDansLOrdreAvecLeFournisseur(): void
    {
        $dept = $this->departement();
        $demandeur = $this->personnel($dept, ['ROLE_FINANCE_DEMANDEUR']);
        $avecFournisseur = $this->commande($dept, $demandeur);
        $avecFournisseur->setFournisseur($this->fournisseur('BUREAU'));
        $propose = $this->commande($dept, $demandeur);
        $propose->setFournisseurPropose('=HYPERLINK("http://exemple.test")');
        $this->em->flush();

        $reponse = $this->requete('POST', self::URI, $demandeur, ['ids' => [$propose->getId(), $avecFournisseur->getId()]]);
        self::assertSame(Response::HTTP_OK, $reponse->getStatusCode());
        $feuille = $this->classeur($reponse)->getActiveSheet();

        self::assertSame('Fournisseur', $feuille->getCell('E1')->getValue());
        self::assertSame($propose->getObjetDemande(), $feuille->getCell('C2')->getValue());
        self::assertSame('=HYPERLINK("http://exemple.test") (proposé)', $feuille->getCell('E2')->getValue(), 'texte saisi jamais interprété comme formule');
        self::assertSame('s', $feuille->getCell('E2')->getDataType());
        self::assertSame($avecFournisseur->getFournisseur()->getNomFournisseur(), $feuille->getCell('E3')->getValue());
        self::assertEquals(100.0, $feuille->getCell('H3')->getValue());
        self::assertNull($feuille->getCell('A4')->getValue());
    }

    public function testNExportePasCeQueLOnNePeutPasVoir(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $demandeur = $this->personnel($a, ['ROLE_FINANCE_DEMANDEUR']);
        $mienne = $this->commande($a, $demandeur);
        $autre = $this->commande($b, $this->personnel($b, ['ROLE_FINANCE_DEMANDEUR']));
        $this->em->flush();

        $reponse = $this->requete('POST', self::URI, $demandeur, ['ids' => [$autre->getId(), $mienne->getId(), 999999999]]);
        self::assertSame(Response::HTTP_OK, $reponse->getStatusCode());
        $feuille = $this->classeur($reponse)->getActiveSheet();
        self::assertSame($mienne->getObjetDemande(), $feuille->getCell('C2')->getValue());
        self::assertNull($feuille->getCell('C3')->getValue());
    }

    public function testRequeteInvalideOuAnonyme(): void
    {
        $dept = $this->departement();
        $demandeur = $this->personnel($dept, ['ROLE_FINANCE_DEMANDEUR']);
        $this->em->flush();

        self::assertSame(401, $this->requete('POST', self::URI, null, ['ids' => [1]])->getStatusCode());
        self::assertSame(400, $this->requete('POST', self::URI, $demandeur, ['ids' => 'x'])->getStatusCode());
    }

    private function classeur(Response $reponse): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        if ($reponse instanceof StreamedResponse) {
            ob_start();
            $reponse->sendContent();
            $contenu = (string) ob_get_clean();
        } else {
            $contenu = (string) $reponse->getContent();
        }
        $fichier = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($fichier, $contenu);
        try {
            return IOFactory::load($fichier);
        } finally {
            @unlink($fichier);
        }
    }
}
