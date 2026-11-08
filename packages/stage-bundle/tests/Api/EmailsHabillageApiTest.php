<?php

namespace StageBundle\Tests\Api;

use App\Entity\Email\EmailTemplate;
use App\Entity\Etablissement;
use StageBundle\Entity\Stages\StageContact;
use StageBundle\Entity\Stages\StageEvaluationCritere;
use StageBundle\Enum\EtatStageEnum;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

/** Habillage de l'établissement : mentions imposées dans tous les e-mails, lien de préférences réservé aux notifications. */
class EmailsHabillageApiTest extends StageApiTestCase
{
    /** @var list<Email> */
    private array $envoyes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->envoyes = [];
        $this->service('event_dispatcher')->addListener(MessageEvent::class, function (MessageEvent $e): void {
            if (!$e->isQueued() && $e->getMessage() instanceof Email) {
                $this->envoyes[] = $e->getMessage();
            }
        });
        $etab = (new Etablissement())->setLibelle('IUT de Test');
        $etab->setIsMain(true);
        $etab->getUniversite()->setLibelle('Université de Test');
        $this->em->persist($etab);
    }

    private function habiller(): void
    {
        $admin = $this->personnel($this->departement(), ['SUPER_ADMIN']);
        $this->enregistrer();
        $reponse = $this->requete('PUT', '/api/email/habillage', $admin, [
            'couleur' => '#aa3300', 'expediteur_nom' => 'Scolarité IUT', 'signature' => 'Le service des stages',
            'mentions_legales' => 'IUT de Test, 9 rue X.', 'contact_rgpd' => 'dpo@test.fr',
        ], [], 'application/json');
        self::assertSame(200, $reponse->getStatusCode(), (string) $reponse->getContent());
    }

    public function testUneNotificationPersonnaliseeGardeMentionsSignatureEtLienDePreferences(): void
    {
        $this->habiller();
        $dept = $this->departement();
        $gestionnaire = $this->personnel($dept, ['ROLE_STAGE_MANAGER']);
        $dossier = $this->dossier($this->periode($dept), $this->etudiant(), EtatStageEnum::DEPOSE);
        // Corps personnalisé minimal, et même « sans layout » : les mentions doivent rester.
        $perso = new EmailTemplate('stage.convention_validee', 'Validé', '<p>{{ texte }}</p>');
        $perso->setUseLayout(false);
        $this->em->persist($perso);
        $this->enregistrer();

        $this->requete('PATCH', '/api/stage_etudiants/'.$dossier->getId(), $gestionnaire, ['etatStage' => EtatStageEnum::VALIDE->value]);

        $mail = $this->dernierEmail();
        $html = (string) $mail->getHtmlBody();
        self::assertStringContainsString('Le service des stages', $html, 'signature');
        self::assertStringContainsString('IUT de Test, 9 rue X.', $html, 'mentions légales');
        self::assertStringContainsString('dpo@test.fr', $html, 'contact RGPD');
        self::assertMatchesRegularExpression('#href="https?://[^"]+/app/auth/notifications"#', $html, 'lien absolu vers les préférences');
        self::assertStringContainsString('#aa3300', $html, 'couleur de l\'établissement');
        self::assertSame('Scolarité IUT', $mail->getFrom()[0]->getName());
    }

    public function testUnEmailTransactionnelNAPasDeLienDePreferencesMaisGardeLesMentions(): void
    {
        $this->habiller();
        $dept = $this->departement();
        $tuteur = $this->personnel($dept);
        $periode = $this->periode($dept);
        $this->dossier($periode, $this->etudiant(), EtatStageEnum::CONVENTION_RECUE, $tuteur)
            ->setTuteur((new StageContact())->setNom('Durand')->setEmail('durand@entreprise.test'));
        $critere = (new StageEvaluationCritere())->setLibelle('Autonomie');
        $periode->addCritereEvaluation($critere);
        $this->em->persist($critere);
        $this->enregistrer();
        $dossier = $this->em->getRepository(\StageBundle\Entity\Stages\StageEtudiant::class)->findOneBy(['stagePeriode' => $periode->getId()]);

        $this->requete('POST', '/api/stage_etudiants/'.$dossier->getId().'/evaluation-entreprise/envoyer', $tuteur);

        $html = (string) $this->dernierEmail()->getHtmlBody();
        self::assertStringNotContainsString('/app/auth/notifications', $html, 'pas de désabonnement pour un e-mail transactionnel');
        self::assertStringContainsString('IUT de Test, 9 rue X.', $html);
    }

    public function testHabillageInvalideRefuseEtCategoriesExposees(): void
    {
        $admin = $this->personnel($this->departement(), ['SUPER_ADMIN']);
        $this->enregistrer();

        self::assertSame(422, $this->requete('PUT', '/api/email/habillage', $admin, ['couleur' => 'rouge', 'reply_to' => 'pas-un-mail'])->getStatusCode());

        $items = [];
        foreach ($this->json($this->requete('GET', '/api/email/definitions', $admin)) as $groupe) {
            foreach ($groupe['items'] as $item) {
                $items[$item['key']] = $item;
            }
        }
        self::assertSame('transactionnel', $items['stage.evaluation_entreprise']['categorie']);
        self::assertFalse($items['stage.evaluation_entreprise']['desactivable']);
        self::assertSame('notification', $items['stage.convention_refusee']['categorie']);
        self::assertFalse($items['stage.convention_refusee']['desactivable'], 'un refus ne peut pas être réglé sur « jamais »');
        self::assertTrue($items['stage.convention_signee']['desactivable']);
        self::assertSame('notification', $items['unifolio.evaluation_rendue']['categorie'], 'e-mail dédié envoyé comme notification');

        $apercu = $this->json($this->requete('POST', '/api/email/habillage/apercu', $admin, ['reglages' => ['couleur' => '#123456', 'signature' => 'Signature d\'essai']]));
        self::assertStringContainsString('Signature d&#039;essai', $apercu['html']);
        self::assertStringContainsString('#123456', $apercu['html']);
    }

    private function dernierEmail(): Email
    {
        self::assertNotEmpty($this->envoyes, 'un e-mail aurait dû partir');

        return $this->envoyes[\count($this->envoyes) - 1];
    }
}
