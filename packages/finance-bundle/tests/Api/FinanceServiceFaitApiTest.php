<?php

namespace FinanceBundle\Tests\Api;

use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceReception;
use FinanceBundle\Enum\StatutCommandeEnum;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service fait en deux temps : le demandeur constate (tout ou partie) en déposant le ou les bons de livraison (PDF),
 * puis le service financier vise chaque réception (numéros MIGO possibles) ; le dernier visa certifie le service fait.
 */
final class FinanceServiceFaitApiTest extends FinanceApiTestCase
{
    private function commandeTransmise(): array
    {
        $dept = $this->departement();
        $demandeur = $this->personnel($dept, ['ROLE_FINANCE_DEMANDEUR']);
        $gestionnaire = $this->personnel($dept, ['ROLE_FINANCE_MANAGER']);
        $etranger = $this->personnel($this->departement('Autre'), ['ROLE_FINANCE_DEMANDEUR']);
        $c = $this->commande($dept, $demandeur, StatutCommandeEnum::COMMANDE_TRANSMISE);
        $this->em->flush();

        return [$c->getId(), $demandeur, $gestionnaire, $etranger];
    }

    private function contenu(Response $reponse): string
    {
        if ($reponse instanceof StreamedResponse || method_exists($reponse, 'getFile')) {
            ob_start();
            $reponse->sendContent();

            return (string) ob_get_clean();
        }

        return (string) $reponse->getContent();
    }

    public function testLeBonDeLivraisonEstObligatoireEtEnPdf(): void
    {
        [$id, $demandeur] = $this->commandeTransmise();

        self::assertSame(422, $this->constater($id, $demandeur, [], 0)->getStatusCode(), 'sans bon de livraison');
        $fauxPdf = ['bonsLivraison' => [$this->fichier('<script>x</script>', 'piege.pdf')]];
        self::assertSame(422, $this->requete('POST', sprintf('/api/finance_bon_commandes/%d/transitions/constater_service_fait', $id), $demandeur, null, $fauxPdf)->getStatusCode(), 'pas un PDF');
        self::assertSame([], $this->receptions($id));

        $reponse = $this->constater($id, $demandeur, ['typeReception' => 'PARTIELLE', 'detailsReceptionPartielle' => '3 écrans sur 5', 'prestationsConformes' => 'false'], 2);
        self::assertSame(200, $reponse->getStatusCode(), (string) $reponse->getContent());
        [$reception] = $this->receptions($id);
        $r = $this->em->find(FinanceReception::class, $reception);
        self::assertCount(2, $r->getDocuments());
        self::assertFalse($r->isPrestationsConformes(), 'booléen du formulaire');
        self::assertFalse($r->isVisee());
    }

    public function testBonsLivraisonLectureAjoutEtRetrait(): void
    {
        [$id, $demandeur, $gestionnaire, $etranger] = $this->commandeTransmise();
        self::assertSame(200, $this->constater($id, $demandeur)->getStatusCode());
        [$reception] = $this->receptions($id);
        $document = $this->em->find(FinanceReception::class, $reception)->getDocuments()->first()->getId();
        $uri = sprintf('/api/finance_receptions/%d/bons-livraison', $reception);

        $lecture = $this->requete('GET', $uri.'/'.$document, $gestionnaire);
        self::assertSame(200, $lecture->getStatusCode());
        self::assertStringStartsWith('%PDF', $this->contenu($lecture));
        self::assertSame(403, $this->requete('GET', $uri.'/'.$document, $etranger)->getStatusCode());

        self::assertSame(403, $this->requete('POST', $uri, $etranger, null, ['fichiers' => [$this->pdf()]])->getStatusCode());
        self::assertSame(201, $this->requete('POST', $uri, $demandeur, null, ['fichiers' => [$this->pdf()]])->getStatusCode());
        self::assertCount(2, $this->em->find(FinanceReception::class, $reception)->getDocuments());
        self::assertSame(204, $this->requete('DELETE', $uri.'/'.$document, $demandeur)->getStatusCode());
        $this->em->clear();
        self::assertCount(1, $this->em->find(FinanceReception::class, $reception)->getDocuments());
    }

    public function testVisaParLeServiceFinancierAvecMigo(): void
    {
        [$id, $demandeur, $gestionnaire] = $this->commandeTransmise();
        self::assertSame(200, $this->constater($id, $demandeur, ['typeReception' => 'PARTIELLE', 'detailsReceptionPartielle' => 'moitié'])->getStatusCode());
        [$partielle] = $this->receptions($id);

        self::assertSame(403, $this->viser($partielle, $demandeur)->getStatusCode(), 'visa réservé au service financier');
        self::assertSame(422, $this->viser($partielle, $gestionnaire, ['50004 12345'])->getStatusCode(), 'MIGO invalide');

        $reponse = $this->viser($partielle, $gestionnaire, ['5000412345', ' 5000412345 ', '']);
        self::assertSame(200, $reponse->getStatusCode(), (string) $reponse->getContent());
        self::assertFalse($this->json($reponse)['certifie'], 'réception partielle : on attend la suite');
        self::assertSame(409, $this->viser($partielle, $gestionnaire)->getStatusCode(), 'déjà visée');

        // Réception visée : ses bons de livraison sont figés
        $uri = sprintf('/api/finance_receptions/%d/bons-livraison', $partielle);
        self::assertSame(403, $this->requete('POST', $uri, $demandeur, null, ['fichiers' => [$this->pdf()]])->getStatusCode());

        self::assertSame(200, $this->constater($id, $demandeur)->getStatusCode());
        [, $totale] = $this->receptions($id);
        $reponse = $this->viser($totale, $gestionnaire);
        self::assertTrue($this->json($reponse)['certifie']);

        $this->em->clear();
        $c = $this->em->find(FinanceBonCommande::class, $id);
        self::assertSame(StatutCommandeEnum::SERVICE_FAIT_CERTIFIE, $c->getStatut());
        self::assertSame($gestionnaire->getId(), $c->getCertificateurServiceFait()->getId());
        self::assertSame(['5000412345'], $c->getReceptions()->first()->getNumerosMigo());
        self::assertSame($gestionnaire->getId(), $c->getReceptions()->first()->getViseur()->getId());
    }

    public function testUneLivraisonRestantPartielleSeCertifieALaMain(): void
    {
        [$id, $demandeur, $gestionnaire] = $this->commandeTransmise();
        self::assertSame(200, $this->constater($id, $demandeur, ['typeReception' => 'PARTIELLE', 'detailsReceptionPartielle' => 'le reste ne sera pas livré'])->getStatusCode());
        $transition = sprintf('/api/finance_bon_commandes/%d/transitions/certifier_service_fait', $id);
        self::assertSame(403, $this->requete('POST', $transition, $gestionnaire, [])->getStatusCode(), 'pas avant le visa');
        self::assertSame(200, $this->viser($this->receptions($id)[0], $gestionnaire)->getStatusCode());
        self::assertSame(200, $this->requete('POST', $transition, $gestionnaire, [])->getStatusCode());
    }
}
