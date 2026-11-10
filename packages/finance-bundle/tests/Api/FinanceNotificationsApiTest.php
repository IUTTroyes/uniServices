<?php

namespace FinanceBundle\Tests\Api;

use App\Entity\Notification\Notification;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Enum\StatutCommandeEnum;
use FinanceBundle\Service\Notification\FinanceRecapitulatif;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

/** Notifications du circuit : bons destinataires, jamais l'auteur, cloche, e-mail immédiat et récapitulatif. */
final class FinanceNotificationsApiTest extends FinanceApiTestCase
{
    /** @var list<Email> */
    private array $envoyes = [];
    private StructureDepartement $dept;
    private Personnel $demandeur;
    private Personnel $valideur;
    private Personnel $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();
        $this->envoyes = [];
        $this->service('event_dispatcher')->addListener(MessageEvent::class, function (MessageEvent $e): void {
            if (!$e->isQueued() && $e->getMessage() instanceof Email) {
                $this->envoyes[] = $e->getMessage();
            }
        });
        $this->dept = $this->departement();
        $this->demandeur = $this->personnel($this->dept, ['ROLE_FINANCE_DEMANDEUR']);
        $this->valideur = $this->personnel($this->dept, ['ROLE_FINANCE_VALIDEUR']);
        $this->gestionnaire = $this->personnel($this->dept, ['ROLE_FINANCE_MANAGER']);
        $this->em->flush();
    }

    /** @return list<string> types (sans « finance. ») des notifications reçues par cette personne */
    private function recues(Personnel $p): array
    {
        $this->em->clear();
        $notifications = $this->em->getRepository(Notification::class)->findBy(['personnel' => $this->em->find(Personnel::class, $p->getId())]);

        return array_map(static fn (Notification $n): string => substr($n->getType(), \strlen('finance.')), $notifications);
    }

    private function creerEtSoumettre(): int
    {
        $c = $this->commande($this->em->find($this->dept::class, $this->dept->getId()), $this->em->find(Personnel::class, $this->demandeur->getId()));
        $c->setNumeroBonCommande('4500000001')->setFournisseur($this->fournisseur());
        $this->em->flush();

        return $c->getId();
    }

    private function action(int $id, string $action, Personnel $user, array $corps = []): int
    {
        return $this->requete('POST', sprintf('/api/finance_bon_commandes/%d/transitions/%s', $id, $action), $user, $corps)->getStatusCode();
    }

    public function testLeCircuitPrevientLesBonnesPersonnes(): void
    {
        $id = $this->creerEtSoumettre();

        self::assertSame(200, $this->action($id, 'soumettre', $this->demandeur));
        self::assertSame(['demande_a_viser'], $this->recues($this->valideur));
        self::assertSame([], $this->recues($this->demandeur), 'jamais l\'auteur');
        self::assertSame([], $this->recues($this->gestionnaire));

        self::assertSame(200, $this->action($id, 'avis_favorable', $this->valideur));
        self::assertSame(['avis_rendu'], $this->recues($this->demandeur));
        self::assertSame(['demande_a_verifier'], $this->recues($this->gestionnaire));

        self::assertSame(200, $this->action($id, 'verifier', $this->gestionnaire));
        self::assertSame(200, $this->action($id, 'viser', $this->gestionnaire));
        self::assertSame(['avis_rendu', 'commande_engagee'], $this->recues($this->demandeur));

        self::assertSame(200, $this->action($id, 'transmettre', $this->gestionnaire));
        self::assertContains('service_fait_a_constater', $this->recues($this->demandeur));

        self::assertSame(200, $this->constater($id, $this->demandeur)->getStatusCode());
        self::assertContains('service_fait_a_certifier', $this->recues($this->gestionnaire));

        self::assertSame(200, $this->viser($this->receptions($id)[0], $this->gestionnaire)->getStatusCode());
        foreach (['recevoir_facture', 'payer'] as $action) {
            self::assertSame(200, $this->action($id, $action, $this->gestionnaire), $action);
        }
        self::assertContains('commande_payee', $this->recues($this->demandeur));
    }

    public function testRejetEtPrecisionsPortentLeMotif(): void
    {
        $id = $this->creerEtSoumettre();
        $this->action($id, 'soumettre', $this->demandeur);

        self::assertSame(200, $this->action($id, 'demander_information', $this->valideur, ['commentaire' => 'Joindre un devis']));
        $this->em->clear();
        $notif = $this->em->getRepository(Notification::class)->findOneBy(['type' => 'finance.avis_rendu']);
        self::assertStringContainsString('Joindre un devis', $notif->getTexte());
        self::assertSame('/app/finance/demande/'.$id, $notif->getUrl());

        $this->action($id, 'soumettre', $this->demandeur);
        self::assertSame(200, $this->action($id, 'avis_defavorable', $this->valideur, ['commentaire' => 'Budget épuisé']));
        self::assertContains('demande_rejetee', $this->recues($this->demandeur));
    }

    public function testUneAutrePersonneDuDepartementNEstPasPrevenue(): void
    {
        $curieux = $this->personnel($this->em->find($this->dept::class, $this->dept->getId()), ['ROLE_FINANCE_DEMANDEUR']);
        $autreDept = $this->departement('Autre');
        $this->em->flush();
        $valideurAutre = $this->personnel($autreDept, ['ROLE_FINANCE_VALIDEUR']);
        $this->em->flush();
        $id = $this->creerEtSoumettre();
        $this->action($id, 'soumettre', $this->demandeur);

        self::assertSame([], $this->recues($curieux));
        self::assertSame([], $this->recues($valideurAutre));
    }

    public function testRecapitulatifParRoleEtParDepartement(): void
    {
        $autre = $this->departement('Autre');
        $this->em->flush();
        $depart = $this->em->find($this->dept::class, $this->dept->getId());
        $a = $this->commande($depart, $this->demandeur, StatutCommandeEnum::SOUMIS);
        $b = $this->commande($depart, $this->demandeur, StatutCommandeEnum::VERIFICATION_FINANCIERE);
        $c = $this->commande($depart, $this->demandeur, StatutCommandeEnum::BROUILLON);
        $d = $this->commande($this->em->find($autre::class, $autre->getId()), $this->demandeur, StatutCommandeEnum::SOUMIS);
        $this->em->flush();

        /** @var FinanceRecapitulatif $recap */
        $recap = $this->service(FinanceRecapitulatif::class);
        $gestionnaire = $recap->pour(['ROLE_FINANCE_MANAGER' => [$this->dept->getId()]]);
        self::assertSame(3, $gestionnaire['total'], 'service financier : tous les départements, sans les brouillons');
        self::assertSame(['Demande soumise', 'Vérification financière'], array_column($gestionnaire['groupes'], 'statut'));
        self::assertSame(2, $gestionnaire['groupes'][0]['nombre']);
        self::assertSame($a->getObjetDemande(), $gestionnaire['groupes'][0]['lignes'][0]['objet']);

        $valideur = $recap->pour(['ROLE_FINANCE_VALIDEUR' => [$this->dept->getId()]]);
        self::assertSame(1, $valideur['total'], 'le valideur ne voit que les demandes à viser de son département');

        self::assertNull($recap->pour(['ROLE_FINANCE_VALIDEUR' => [$autre->getId() + 999999]]));
        self::assertNull($recap->pour([]));
        self::assertNotNull($c->getId());
        self::assertNotNull($b->getId());
        self::assertNotNull($d->getId());
    }

    public function testRecapitulatifEnvoyeParEmailDedie(): void
    {
        $depart = $this->em->find($this->dept::class, $this->dept->getId());
        $this->commande($depart, $this->demandeur, StatutCommandeEnum::SOUMIS)->setObjetDemande('Écran 27 pouces');
        $this->em->flush();
        $this->service('FinanceBundle\\Service\\Notification\\FinanceNotifier')->recapitulatif(
            $this->em->find(Personnel::class, $this->gestionnaire->getId()),
            $this->service(FinanceRecapitulatif::class)->pour(['ROLE_FINANCE_MANAGER' => [$this->dept->getId()]]),
        );

        // « Dans le résumé » par défaut pour ce type n'existe pas : le récapitulatif part tout de suite.
        self::assertCount(1, $this->envoyes);
        self::assertStringContainsString('en attente', (string) $this->envoyes[0]->getSubject());
        self::assertStringContainsString('Écran 27 pouces', (string) $this->envoyes[0]->getHtmlBody());
        self::assertStringContainsString('/app/finance', (string) $this->envoyes[0]->getHtmlBody());
    }

    public function testEmailGeneriqueDesNotificationsParametrableDansLEditeur(): void
    {
        $admin = $this->personnel($this->em->find($this->dept::class, $this->dept->getId()), ['SUPER_ADMIN']);
        $this->em->flush();
        $groupes = array_column($this->json($this->requete('GET', '/api/email/definitions', $admin)), 'items', 'bundle');
        $cles = array_column(array_merge(...array_values(array_filter($groupes, static fn ($k) => stripos((string) $k, 'financ') !== false, \ARRAY_FILTER_USE_KEY))), 'key');
        self::assertContains('finance.demande_a_viser', $cles);
        self::assertContains('finance.recapitulatif', $cles);
        self::assertInstanceOf(FinanceBonCommande::class, $this->em->getRepository(FinanceBonCommande::class)->findOneBy([]) ?? new FinanceBonCommande());
    }
}
