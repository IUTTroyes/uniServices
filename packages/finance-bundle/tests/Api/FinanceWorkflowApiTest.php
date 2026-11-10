<?php

namespace FinanceBundle\Tests\Api;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceTauxTva;
use FinanceBundle\Enum\StatutCommandeEnum;
use Symfony\Component\HttpFoundation\Response;

/** Circuit d'une demande d'achat côté serveur : transitions, droits par profil, champs par étape, montants, référentiels. */
final class FinanceWorkflowApiTest extends FinanceApiTestCase
{
    private StructureDepartement $dept;
    private Personnel $demandeur;
    private Personnel $valideur;
    private Personnel $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dept = $this->departement();
        $this->demandeur = $this->personnel($this->dept, ['ROLE_FINANCE_DEMANDEUR']);
        $this->valideur = $this->personnel($this->dept, ['ROLE_FINANCE_VALIDEUR']);
        $this->gestionnaire = $this->personnel($this->dept, ['ROLE_FINANCE_MANAGER']);
        $this->em->flush();
    }

    /** @param array<string, mixed> $corps */
    private function action(int $id, string $action, Personnel $user, array $corps = []): Response
    {
        return $this->requete('POST', sprintf('/api/finance_bon_commandes/%d/transitions/%s', $id, $action), $user, $corps);
    }

    private function statut(int $id): StatutCommandeEnum
    {
        $this->em->clear();

        return $this->em->find(FinanceBonCommande::class, $id)->getStatut();
    }

    private function creer(array $corps = []): int
    {
        $reponse = $this->requete('POST', '/api/finance_bon_commandes', $this->demandeur, $corps + [
            'objetDemande' => 'Vidéoprojecteur',
            'montantHt' => '1000.00',
            'tauxTva' => '20',
            'departement' => $this->iri('structure_departements', $this->dept->getId()),
        ]);
        self::assertSame(201, $reponse->getStatusCode(), (string) $reponse->getContent());

        return (int) $this->json($reponse)['id'];
    }

    /** @return list<string> */
    private function actions(int $id, Personnel $user): array
    {
        $data = $this->json($this->requete('GET', $this->iri('finance_bon_commandes', $id), $user));

        return array_column($data['droits']['actions'] ?? [], 'action');
    }

    public function testParcoursCompletAvecSignataires(): void
    {
        $f = $this->fournisseur();
        $this->em->flush();
        $id = $this->creer();

        self::assertSame(['soumettre'], $this->actions($id, $this->demandeur));
        self::assertSame(200, $this->action($id, 'soumettre', $this->demandeur)->getStatusCode());
        self::assertSame(StatutCommandeEnum::SOUMIS, $this->statut($id));

        self::assertSame(['avis_favorable', 'avis_defavorable', 'demander_information'], $this->actions($id, $this->valideur));
        self::assertSame([], $this->actions($id, $this->demandeur));
        self::assertSame(200, $this->action($id, 'avis_favorable', $this->valideur)->getStatusCode());

        // Vérification : n° SIFAC et fournisseur obligatoires
        self::assertSame(422, $this->action($id, 'verifier', $this->gestionnaire)->getStatusCode());
        self::assertSame(200, $this->requete('PATCH', $this->iri('finance_bon_commandes', $id), $this->gestionnaire, [
            'numeroBonCommande' => '4500264424',
            'fournisseur' => $this->iri('finance_fournisseurs', $f->getId()),
        ])->getStatusCode());
        foreach (['verifier', 'viser', 'transmettre'] as $action) {
            self::assertSame(200, $this->action($id, $action, $this->gestionnaire)->getStatusCode(), $action);
        }
        self::assertSame(StatutCommandeEnum::COMMANDE_TRANSMISE, $this->statut($id));

        // Réception partielle puis totale par le demandeur
        self::assertSame(422, $this->action($id, 'constater_service_fait', $this->demandeur, ['typeReception' => 'PARTIELLE'])->getStatusCode());
        self::assertSame(200, $this->action($id, 'constater_service_fait', $this->demandeur, ['typeReception' => 'PARTIELLE', 'detailsReceptionPartielle' => '1 sur 2', 'montantTtcConstate' => '600'])->getStatusCode());
        self::assertContains('constater_service_fait', $this->actions($id, $this->demandeur));
        self::assertSame(200, $this->action($id, 'constater_service_fait', $this->demandeur, ['qualiteReceptionnaire' => 'Enseignant'])->getStatusCode());
        self::assertNotContains('constater_service_fait', $this->actions($id, $this->demandeur));

        self::assertSame(200, $this->action($id, 'certifier_service_fait', $this->gestionnaire)->getStatusCode());
        self::assertSame(200, $this->action($id, 'recevoir_facture', $this->gestionnaire, ['date' => '2026-11-02'])->getStatusCode());
        self::assertSame(422, $this->action($id, 'rejeter_facture', $this->gestionnaire)->getStatusCode());
        self::assertSame(200, $this->action($id, 'rejeter_facture', $this->gestionnaire, ['commentaire' => 'Montant erroné'])->getStatusCode());
        self::assertSame(200, $this->action($id, 'recevoir_facture', $this->gestionnaire)->getStatusCode());
        self::assertSame(200, $this->action($id, 'payer', $this->gestionnaire, ['date' => '2026-11-05'])->getStatusCode());
        self::assertSame(200, $this->action($id, 'cloturer', $this->gestionnaire)->getStatusCode());

        $this->em->clear();
        $c = $this->em->find(FinanceBonCommande::class, $id);
        self::assertSame(StatutCommandeEnum::CLOTURE, $c->getStatut());
        self::assertSame($this->valideur->getId(), $c->getSignataireDirecteur()->getId());
        self::assertSame($this->gestionnaire->getId(), $c->getVerificateurFinancier()->getId());
        self::assertSame($this->gestionnaire->getId(), $c->getSignataireVisaEngagement()->getId());
        self::assertSame($this->gestionnaire->getId(), $c->getCertificateurServiceFait()->getId());
        self::assertTrue($c->isBcTransmis());
        self::assertNotNull($c->getDateSignatureDemandeur());
        self::assertSame('2026-11-05', $c->getDatePaiement()->format('Y-m-d'));
        self::assertNull($c->getRejetFactureMotif());
        self::assertCount(2, $c->getReceptions());
        self::assertSame($this->demandeur->getId(), $c->getReceptions()->first()->getReceptionnaire()->getId());
        self::assertSame('600.00', $c->getReceptions()->first()->getMontantTtcConstate());
        self::assertSame([], $this->actions($id, $this->gestionnaire));
    }

    public function testActionsRefuseesSelonProfilStatutEtDepartement(): void
    {
        $id = $this->creer();
        $autreValideur = $this->personnel($this->departement('Autre'), ['ROLE_FINANCE_VALIDEUR']);
        $this->em->flush();

        self::assertSame(409, $this->action($id, 'avis_favorable', $this->valideur)->getStatusCode(), 'pas encore soumise');
        self::assertSame(403, $this->action($id, 'soumettre', $this->valideur)->getStatusCode());
        $this->action($id, 'soumettre', $this->demandeur);
        self::assertSame(403, $this->action($id, 'avis_favorable', $this->demandeur)->getStatusCode());
        self::assertSame(403, $this->action($id, 'avis_favorable', $this->gestionnaire)->getStatusCode(), 'avis réservé au valideur');
        self::assertSame(403, $this->action($id, 'avis_favorable', $autreValideur)->getStatusCode(), 'valideur d\'un autre département');
        self::assertSame(404, $this->action($id, 'inexistante', $this->valideur)->getStatusCode());
        self::assertSame(422, $this->action($id, 'avis_defavorable', $this->valideur)->getStatusCode(), 'motif obligatoire');
        self::assertSame(StatutCommandeEnum::SOUMIS, $this->statut($id));

        // Après soumission, le demandeur ne modifie plus la demande ; le valideur jamais.
        self::assertSame(403, $this->requete('PATCH', $this->iri('finance_bon_commandes', $id), $this->demandeur, ['objetDemande' => 'x'])->getStatusCode());
        self::assertSame(403, $this->requete('PATCH', $this->iri('finance_bon_commandes', $id), $this->valideur, ['objetDemande' => 'x'])->getStatusCode());
        self::assertSame(200, $this->requete('PATCH', $this->iri('finance_bon_commandes', $id), $this->gestionnaire, ['centreFinancier' => 'CF1'])->getStatusCode());
    }

    public function testDemandeDePrecisionsPuisRejetEtReouverture(): void
    {
        $id = $this->creer();
        $this->action($id, 'soumettre', $this->demandeur);

        self::assertSame(200, $this->action($id, 'demander_information', $this->valideur, ['commentaire' => 'Joindre un devis'])->getStatusCode());
        self::assertSame(StatutCommandeEnum::BROUILLON, $this->statut($id));
        self::assertSame(200, $this->requete('PATCH', $this->iri('finance_bon_commandes', $id), $this->demandeur, ['noteExplicative' => 'Devis joint'])->getStatusCode());
        self::assertSame(200, $this->action($id, 'soumettre', $this->demandeur)->getStatusCode());
        $this->em->clear();
        self::assertNull($this->em->find(FinanceBonCommande::class, $id)->getAvisDirecteur(), 'nouvelle soumission : avis remis à zéro');

        self::assertSame(200, $this->action($id, 'avis_defavorable', $this->valideur, ['commentaire' => 'Budget épuisé'])->getStatusCode());
        $this->em->clear();
        $c = $this->em->find(FinanceBonCommande::class, $id);
        self::assertSame(StatutCommandeEnum::REJETE, $c->getStatut());
        self::assertSame('Budget épuisé', $c->getMotifRejet());

        self::assertSame(['reouvrir'], $this->actions($id, $this->demandeur));
        self::assertSame(200, $this->action($id, 'reouvrir', $this->demandeur)->getStatusCode());
        self::assertSame(StatutCommandeEnum::BROUILLON, $this->statut($id));
    }

    public function testChampsModifiablesExposesParLeServeur(): void
    {
        $id = $this->creer();
        $droits = fn (Personnel $u): array => $this->json($this->requete('GET', $this->iri('finance_bon_commandes', $id), $u))['droits'];

        self::assertContains('objetDemande', $droits($this->demandeur)['champs']);
        self::assertNotContains('numeroBonCommande', $droits($this->demandeur)['champs']);
        self::assertTrue($droits($this->demandeur)['supprimer']);
        self::assertSame([], $droits($this->valideur)['champs']);

        $this->action($id, 'soumettre', $this->demandeur);
        self::assertSame([], $droits($this->demandeur)['champs']);
        self::assertContains('numeroBonCommande', $droits($this->gestionnaire)['champs']);
        self::assertFalse($droits($this->demandeur)['supprimer']);
        self::assertArrayNotHasKey('droits', $this->json($this->requete('GET', '/api/finance_bon_commandes', $this->gestionnaire))['member'][0] ?? []);
    }

    public function testMontantsHtTtcSansEcraserLaSaisie(): void
    {
        $lire = function (int $id): FinanceBonCommande {
            $this->em->clear();

            return $this->em->find(FinanceBonCommande::class, $id);
        };

        // HT + taux → TTC
        $c = $lire($this->creer(['montantHt' => '100.00', 'tauxTva' => '5.5']));
        self::assertSame('105.50', $c->getMontantTtc());

        // TTC + taux → HT (sens inverse)
        $id = $this->creer(['montantHt' => null, 'montantTtc' => '120.00', 'tauxTva' => '20']);
        self::assertSame('100.00', $lire($id)->getMontantHt());

        // Deux montants saisis : conservés tels quels
        $c = $lire($this->creer(['montantHt' => '100.00', 'montantTtc' => '119.99', 'tauxTva' => '20']));
        self::assertSame('100.00', $c->getMontantHt());
        self::assertSame('119.99', $c->getMontantTtc());

        // Sans HT ni (TTC + taux) : refusé
        $reponse = $this->requete('POST', '/api/finance_bon_commandes', $this->demandeur, [
            'objetDemande' => 'x', 'montantTtc' => '10', 'departement' => $this->iri('structure_departements', $this->dept->getId()),
        ]);
        self::assertSame(422, $reponse->getStatusCode());
    }

    public function testReferentiels(): void
    {
        // Taux de TVA : lecture pour tous, gestion par le service financier, un seul taux par défaut.
        self::assertSame(200, $this->requete('GET', '/api/finance_taux_tvas', $this->demandeur)->getStatusCode());
        self::assertSame(403, $this->requete('POST', '/api/finance_taux_tvas', $this->demandeur, ['taux' => '7', 'libelle' => 'x'])->getStatusCode());
        $a = $this->requete('POST', '/api/finance_taux_tvas', $this->gestionnaire, ['taux' => '7.7', 'libelle' => 'Test A', 'parDefaut' => true]);
        self::assertSame(201, $a->getStatusCode(), (string) $a->getContent());
        self::assertSame(201, $this->requete('POST', '/api/finance_taux_tvas', $this->gestionnaire, ['taux' => '8.8', 'libelle' => 'Test B', 'parDefaut' => true])->getStatusCode());
        self::assertSame(422, $this->requete('POST', '/api/finance_taux_tvas', $this->gestionnaire, ['taux' => '8.80', 'libelle' => 'Doublon'])->getStatusCode());
        $this->em->clear();
        $parDefaut = $this->em->getRepository(FinanceTauxTva::class)->findBy(['parDefaut' => true]);
        self::assertCount(1, $parDefaut);
        self::assertSame('8.80', $parDefaut[0]->getTaux());

        // Fournisseurs : pas de doublon (casse, espaces, numéro), pas de suppression d'un fournisseur utilisé.
        $f = $this->fournisseur('ACME');
        $c = $this->commande($this->em->find(StructureDepartement::class, $this->dept->getId()), null);
        $c->setFournisseur($f);
        $this->em->flush();
        self::assertSame(422, $this->requete('POST', '/api/finance_fournisseurs', $this->gestionnaire, ['nomFournisseur' => '  '.strtolower((string) $f->getNomFournisseur()).' '])->getStatusCode());
        self::assertSame(422, $this->requete('POST', '/api/finance_fournisseurs', $this->gestionnaire, ['nomFournisseur' => $this->unique('AUTRE'), 'numeroFournisseur' => '1000'])->getStatusCode());
        self::assertSame(409, $this->requete('DELETE', $this->iri('finance_fournisseurs', $f->getId()), $this->gestionnaire)->getStatusCode());
        $libre = $this->fournisseur('LIBRE');
        $libre->setNumeroFournisseur(null);
        $this->em->flush();
        self::assertSame(204, $this->requete('DELETE', $this->iri('finance_fournisseurs', $libre->getId()), $this->gestionnaire)->getStatusCode());
    }

    public function testDepotDuDocumentSelonLEtape(): void
    {
        $id = $this->creer();
        $depot = sprintf('/api/finance_bon_commandes/%d/upload-signed-pdf', $id);
        self::assertSame(403, $this->requete('POST', $depot, $this->valideur, null, ['file' => $this->pdf()])->getStatusCode());
        self::assertSame(200, $this->requete('POST', $depot, $this->demandeur, null, ['file' => $this->pdf()])->getStatusCode());
        $this->action($id, 'soumettre', $this->demandeur);
        $this->action($id, 'avis_favorable', $this->valideur);
        self::assertSame(403, $this->requete('POST', $depot, $this->demandeur, null, ['file' => $this->pdf()])->getStatusCode(), 'après l\'avis');
        self::assertSame(200, $this->requete('POST', $depot, $this->gestionnaire, null, ['file' => $this->pdf()])->getStatusCode());
    }

    /** @param array<string, string> $champs */
    private function deposer(int $id, Personnel $user, array $champs, bool $avecFichier = true): \Symfony\Component\HttpFoundation\Response
    {
        $uri = sprintf('/api/finance_bon_commandes/%d/upload-signed-pdf', $id);

        return $this->requete('POST', $uri, $user, null, $avecFichier ? ['file' => $this->pdf()] : [], $champs);
    }

    public function testLeDocumentSigneValideLaSuiteSansRessaisie(): void
    {
        $id = $this->creer();
        $reponse = $this->deposer($id, $this->demandeur, ['decision' => 'favorable', 'commentaire' => 'OK pour la DSI']);
        self::assertSame(200, $reponse->getStatusCode(), (string) $reponse->getContent());
        self::assertSame('VERIFICATION_FINANCIERE', $this->json($reponse)['statut']);

        $this->em->clear();
        $c = $this->em->find(FinanceBonCommande::class, $id);
        self::assertSame('FAVORABLE', $c->getAvisDirecteur()->value);
        self::assertStringContainsString('OK pour la DSI', $c->getAvisDirecteurCommentaire());
        self::assertStringContainsString('document signé', $c->getAvisDirecteurCommentaire());
        self::assertNotNull($c->getDateAvisDirecteur());
        self::assertTrue($c->hasPdfSigne());
        // L'avis est rendu : plus de décision possible, ni par document ni par transition.
        self::assertSame(403, $this->deposer($id, $this->demandeur, ['decision' => 'defavorable', 'commentaire' => 'x'])->getStatusCode(), 'le demandeur ne dépose plus après l\'avis');
        self::assertSame(409, $this->deposer($id, $this->gestionnaire, ['decision' => 'defavorable', 'commentaire' => 'x'])->getStatusCode(), 'l\'avis est déjà rendu');
        self::assertSame(409, $this->action($id, 'avis_favorable', $this->valideur)->getStatusCode());
        self::assertContains('verifier', $this->actions($id, $this->gestionnaire));
    }

    public function testLeRefusParDocumentRejetteLaDemande(): void
    {
        $id = $this->creer();
        // Motif obligatoire ; document facultatif.
        self::assertSame(422, $this->deposer($id, $this->demandeur, ['decision' => 'defavorable'], false)->getStatusCode());
        self::assertSame(StatutCommandeEnum::BROUILLON, $this->statut($id));

        $reponse = $this->deposer($id, $this->demandeur, ['decision' => 'defavorable', 'commentaire' => 'Hors budget'], false);
        self::assertSame(200, $reponse->getStatusCode(), (string) $reponse->getContent());
        $this->em->clear();
        $c = $this->em->find(FinanceBonCommande::class, $id);
        self::assertSame(StatutCommandeEnum::REJETE, $c->getStatut());
        self::assertSame('Hors budget', $c->getMotifRejet());
        self::assertFalse($c->hasPdfSigne());
        self::assertSame(['reouvrir'], $this->actions($id, $this->demandeur));
    }

    public function testDecisionParDocumentReserveEtControlee(): void
    {
        $id = $this->creer();
        self::assertSame(403, $this->deposer($id, $this->valideur, ['decision' => 'favorable'])->getStatusCode(), 'le valideur passe par son avis');
        self::assertSame(422, $this->deposer($id, $this->demandeur, ['decision' => 'peut-etre'])->getStatusCode());
        self::assertSame(400, $this->deposer($id, $this->demandeur, ['decision' => 'favorable'], false)->getStatusCode(), 'document requis pour un avis favorable');
        self::assertSame(StatutCommandeEnum::BROUILLON, $this->statut($id));

        $droits = $this->json($this->requete('GET', $this->iri('finance_bon_commandes', $id), $this->demandeur))['droits'];
        self::assertTrue($droits['decisionDocument']);
        self::assertFalse($this->json($this->requete('GET', $this->iri('finance_bon_commandes', $id), $this->valideur))['droits']['decisionDocument']);
    }
}
