<?php

namespace FinanceBundle\Tests\Api;

use App\Entity\Structure\StructureService;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Enum\StatutCommandeEnum;

/**
 * Imputation d'une demande : un département OU un service (l'un des deux obligatoire). Une demande de service est déposée
 * par un demandeur (de n'importe quel département) et instruite par le service financier (gestionnaire quelque part).
 */
final class FinanceImputationServiceApiTest extends FinanceApiTestCase
{
    private function structureService(string $libelle = 'Informatique'): StructureService
    {
        $s = (new StructureService())->setLibelle($libelle);
        $this->em->persist($s);

        return $s;
    }

    public function testDepartementOuServiceMaisPasLesDeux(): void
    {
        $dept = $this->departement();
        $demandeur = $this->personnel($dept, ['ROLE_FINANCE_DEMANDEUR']);
        $service = $this->structureService();
        $this->em->flush();
        $corps = ['objetDemande' => 'Écrans', 'montantHt' => '300.00'];
        $iriDept = $this->iri('structure_departements', $dept->getId());
        $iriService = $this->iri('structure_services', $service->getId());

        self::assertSame(422, $this->requete('POST', '/api/finance_bon_commandes', $demandeur, $corps)->getStatusCode(), 'aucune imputation');
        self::assertSame(422, $this->requete('POST', '/api/finance_bon_commandes', $demandeur, $corps + ['departement' => $iriDept, 'service' => $iriService])->getStatusCode(), 'les deux');

        $reponse = $this->requete('POST', '/api/finance_bon_commandes', $demandeur, $corps + ['service' => $iriService]);
        self::assertSame(201, $reponse->getStatusCode(), (string) $reponse->getContent());
        $data = $this->json($this->requete('GET', $this->iri('finance_bon_commandes', $this->json($reponse)['id']), $demandeur));
        self::assertNull($data['departement'] ?? null);
        self::assertSame('Informatique', $data['service']['libelle'] ?? null);

        // L'imputation est fixée à la création.
        $autre = $this->structureService('Patrimoine');
        $this->em->flush();
        self::assertSame(403, $this->requete('PATCH', $this->iri('finance_bon_commandes', $data['id']), $demandeur, ['service' => $this->iri('structure_services', $autre->getId())])->getStatusCode());
    }

    public function testSansRoleDemandeurOnNeDeposePasPourUnService(): void
    {
        $dept = $this->departement();
        $simple = $this->personnel($dept);
        $service = $this->structureService();
        $this->em->flush();

        self::assertSame(403, $this->requete('POST', '/api/finance_bon_commandes', $simple, [
            'objetDemande' => 'x', 'montantHt' => '1', 'service' => $this->iri('structure_services', $service->getId()),
        ])->getStatusCode());
    }

    public function testUneDemandeDeServiceEstInstruiteParLeServiceFinancier(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $demandeur = $this->personnel($a, ['ROLE_FINANCE_DEMANDEUR']);
        $valideurA = $this->personnel($a, ['ROLE_FINANCE_VALIDEUR']);
        $gestionnaireB = $this->personnel($b, ['ROLE_FINANCE_MANAGER']);
        $c = (new FinanceBonCommande())->setService($this->structureService())->setDemandeur($demandeur)
            ->setObjetDemande($this->unique('Achat'))->setMontantHt('100.00')->setStatut(StatutCommandeEnum::SOUMIS);
        $this->em->persist($c);
        $this->em->flush();

        $voit = fn ($user): bool => \in_array($c->getId(), $this->ids($this->requete('GET', '/api/finance_bon_commandes?itemsPerPage=100', $user)), true);
        self::assertTrue($voit($demandeur));
        self::assertTrue($voit($gestionnaireB), 'service financier');
        self::assertFalse($voit($valideurA), 'un valideur de département ne voit pas les demandes de service');

        $avis = fn ($user): int => $this->requete('POST', sprintf('/api/finance_bon_commandes/%d/transitions/avis_favorable', $c->getId()), $user, [])->getStatusCode();
        self::assertSame(403, $avis($valideurA));
        self::assertSame(200, $avis($gestionnaireB));
    }
}
