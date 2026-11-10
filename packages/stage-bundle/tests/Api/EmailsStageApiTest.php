<?php

namespace StageBundle\Tests\Api;

use App\Entity\Email\EmailTemplate;
use StageBundle\Enum\EtatStageEnum;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

/** Éditeur des e-mails (cœur) appliqué au stage : modèles paramétrables, vérification, aperçu et envoi réel. */
class EmailsStageApiTest extends StageApiTestCase
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
    }

    public function testLesEmailsDuStageSontListesEtReservesAuSuperAdmin(): void
    {
        $admin = $this->personnel($this->departement(), ['SUPER_ADMIN']);
        $gestionnaire = $this->personnel($this->departement(), ['ROLE_STAGE_MANAGER']);
        $this->enregistrer();

        self::assertSame(403, $this->requete('GET', '/api/email/definitions', $gestionnaire)->getStatusCode());

        $groupes = array_column($this->json($this->requete('GET', '/api/email/definitions', $admin)), 'items', 'bundle');
        $cles = array_column($groupes['Stage'] ?? [], 'key');
        self::assertContains('stage.convention_validee', $cles, 'chaque notification du stage est paramétrable');
        self::assertContains('stage.evaluation_entreprise', $cles);

        $modele = $this->json($this->requete('GET', '/api/email/templates/stage.convention_validee', $admin));
        self::assertSame('defaut', $modele['personnalisation']);
        self::assertSame('notification.immediate', $modele['parentKey']);
        self::assertStringContainsString('{{ texte }}', (string) $modele['defaultBodyHtml'], 'on part du vrai message par défaut');
    }

    public function testUnModeleInvalideEstRefuseAvantEnregistrement(): void
    {
        $admin = $this->personnel($this->departement(), ['SUPER_ADMIN']);
        $this->enregistrer();

        $reponse = $this->requete('POST', '/api/email/templates', $admin, [
            'emailKey' => 'stage.convention_validee', 'subject' => 'Objet', 'bodyHtml' => "{{ [1]|map(x => 'z')|join }}",
        ]);
        self::assertSame(422, $reponse->getStatusCode());
        self::assertStringContainsString('non autorisée', $this->json($reponse)['error']);

        $apercu = $this->json($this->requete('POST', '/api/email/apercu', $admin, [
            'emailKey' => 'stage.convention_validee', 'subject' => 'Info : {{ titre }}', 'bodyHtml' => '<p>{{ texte }}</p><a href="{{ urlNotification }}">ouvrir</a>',
        ]));
        self::assertSame('Info : [titre]', $apercu['subject']);
        self::assertStringContainsString('<p>[texte]</p>', $apercu['html']);
    }

    public function testUneNotificationDuStagePartAvecLeModelePersonnalise(): void
    {
        $admin = $this->personnel($this->departement(), ['SUPER_ADMIN']);
        $dept = $this->departement();
        $gestionnaire = $this->personnel($dept, ['ROLE_STAGE_MANAGER']);
        $dossier = $this->dossier($this->periode($dept), $this->etudiant(), EtatStageEnum::DEPOSE);
        $this->enregistrer();

        $enregistre = $this->requete('POST', '/api/email/templates', $admin, [
            'emailKey' => 'stage.convention_validee',
            'subject' => 'Bonne nouvelle, {{ destinataire_nom }}',
            'bodyHtml' => '<p>{{ texte }}</p><a href="{{ urlNotification }}">Voir ma convention</a>',
        ]);
        self::assertSame(200, $enregistre->getStatusCode(), (string) $enregistre->getContent());
        $liste = array_column($this->json($this->requete('GET', '/api/email/definitions', $admin)), 'items', 'bundle');
        $item = array_values(array_filter($liste['Stage'], fn ($i) => $i['key'] === 'stage.convention_validee'))[0];
        self::assertTrue($item['isCustomized'], 'personnalisation de l\'établissement visible dans la liste');

        $this->requete('PATCH', '/api/stage_etudiants/'.$dossier->getId(), $gestionnaire, ['etatStage' => EtatStageEnum::VALIDE->value]);

        $mail = $this->dernierEmail();
        self::assertStringStartsWith('Bonne nouvelle, Alice', (string) $mail->getSubject());
        self::assertStringContainsString('a été validée', (string) $mail->getHtmlBody());
        self::assertMatchesRegularExpression('#href="https?://[^"]+/app/stage/etudiant"#', (string) $mail->getHtmlBody(), 'lien absolu');
    }

    public function testUnModelePersonnaliseCasseNEmpechePasLEnvoi(): void
    {
        $dept = $this->departement();
        $gestionnaire = $this->personnel($dept, ['ROLE_STAGE_MANAGER']);
        $dossier = $this->dossier($this->periode($dept), $this->etudiant(), EtatStageEnum::DEPOSE);
        // Écrit directement en base (contourne la vérification de l'éditeur) : objet hors sandbox.
        $this->em->persist(new EmailTemplate('stage.convention_validee', "{{ [1]|map(x => 'PIEGE')|join }}", '<p>{{ texte }}</p>'));
        $this->enregistrer();

        $this->requete('PATCH', '/api/stage_etudiants/'.$dossier->getId(), $gestionnaire, ['etatStage' => EtatStageEnum::VALIDE->value]);

        $mail = $this->dernierEmail();
        self::assertStringNotContainsString('PIEGE', (string) $mail->getSubject());
        self::assertSame('[Stages] Ma convention', $mail->getSubject(), 'repli sur le modèle par défaut');
    }

    private function dernierEmail(): Email
    {
        self::assertNotEmpty($this->envoyes, 'un e-mail aurait dû partir');

        return $this->envoyes[\count($this->envoyes) - 1];
    }
}
