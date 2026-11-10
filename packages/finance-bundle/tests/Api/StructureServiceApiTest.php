<?php

namespace FinanceBundle\Tests\Api;

use App\Entity\Structure\StructureService;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Enum\StatutCommandeEnum;

/**
 * Gestion des services de l'établissement (Configuration du portail), servis notamment à l'imputation des demandes
 * d'achat : écriture réservée au super administrateur, suppression refusée tant qu'un service est utilisé.
 * (Placé ici pour profiter du noyau de test HTTP du package Finances.)
 */
final class StructureServiceApiTest extends FinanceApiTestCase
{
    public function testSeulLeSuperAdministrateurGereLesServices(): void
    {
        $dept = $this->departement();
        $gestionnaire = $this->personnel($dept, ['ROLE_FINANCE_MANAGER']);
        $admin = $this->personnel($dept, ['SUPER_ADMIN']);
        $this->em->flush();
        $libelle = $this->unique('Patrimoine');

        self::assertSame(401, $this->requete('POST', '/api/structure_services', null, ['libelle' => $libelle])->getStatusCode());
        self::assertSame(403, $this->requete('POST', '/api/structure_services', $gestionnaire, ['libelle' => $libelle])->getStatusCode());

        $reponse = $this->requete('POST', '/api/structure_services', $admin, ['libelle' => $libelle]);
        self::assertSame(201, $reponse->getStatusCode(), (string) $reponse->getContent());
        $id = $this->json($reponse)['id'];

        self::assertSame(422, $this->requete('POST', '/api/structure_services', $admin, ['libelle' => $libelle])->getStatusCode(), 'doublon');
        self::assertSame(422, $this->requete('POST', '/api/structure_services', $admin, ['libelle' => ''])->getStatusCode(), 'vide');
        self::assertSame(422, $this->requete('POST', '/api/structure_services', $admin, ['libelle' => str_repeat('x', 51)])->getStatusCode(), 'trop long');

        $uri = $this->iri('structure_services', $id);
        self::assertSame(403, $this->requete('PATCH', $uri, $gestionnaire, ['libelle' => 'x'])->getStatusCode());
        $reponse = $this->requete('PATCH', $uri, $admin, ['libelle' => $libelle.' bis']);
        self::assertSame(200, $reponse->getStatusCode(), (string) $reponse->getContent());
        self::assertSame($libelle.' bis', $this->json($reponse)['libelle']);

        self::assertSame(403, $this->requete('DELETE', $uri, $gestionnaire)->getStatusCode());
        self::assertSame(204, $this->requete('DELETE', $uri, $admin)->getStatusCode());
        self::assertNull($this->em->find(StructureService::class, $id));
    }

    public function testUnServiceUtiliseNeSeSupprimePas(): void
    {
        $dept = $this->departement();
        $admin = $this->personnel($dept, ['SUPER_ADMIN']);
        $service = (new StructureService())->setLibelle($this->unique('Informatique'));
        $this->em->persist($service);
        $c = (new FinanceBonCommande())->setService($service)->setDemandeur($admin)
            ->setObjetDemande('x')->setMontantHt('1.00')->setStatut(StatutCommandeEnum::BROUILLON);
        $this->em->persist($c);
        $membre = (new StructureService())->setLibelle($this->unique('Scolarite'));
        $membre->addPersonnel($this->personnel($dept));
        $this->em->persist($membre);
        $this->em->flush();

        self::assertSame(409, $this->requete('DELETE', $this->iri('structure_services', $service->getId()), $admin)->getStatusCode(), 'demande d\'achat');
        self::assertSame(409, $this->requete('DELETE', $this->iri('structure_services', $membre->getId()), $admin)->getStatusCode(), 'personnel rattaché');
    }
}
