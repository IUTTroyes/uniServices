<?php

namespace QuestionnaireBundle\Tests\Api;

use App\Entity\Notification\Notification;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Enum\TypeNotificationEnum;

/**
 * Notifications de bout en bout : un compte de l'application est notifié (cloche + e-mail selon ses préférences),
 * une adresse extérieure reçoit un e-mail direct.
 */
final class QuestionnaireNotificationApiTest extends QuestionnaireApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->capturerEmails();
    }

    public function testInvitationDUnEtudiantEtDUneAdresseExterieure(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $etudiant = $this->etudiant();
        $q = $this->questionnaire($a);
        $invitations = $this->publier($q, $gestionnaire, [$etudiant->getMailUniv(), 'externe@exemple.test']);

        $notifs = $this->notifications(TypeNotificationEnum::INVITATION_ETUDIANT->cle());
        self::assertCount(1, $notifs, 'Le compte étudiant est notifié (cloche).');
        self::assertSame($etudiant->getId(), $notifs[0]->getDestinataire()->getId());
        self::assertStringStartsWith('/app/questionnaire/qualite/enquetes/take/', (string) $notifs[0]->getUrl());
        self::assertNotNull($notifs[0]->getEmailTraiteLe(), 'Mode par défaut « tout de suite » : e-mail parti.');

        $adresses = array_map(fn ($m) => $m->getTo()[0]->getAddress(), $this->emails);
        $attendues = ['externe@exemple.test', mb_strtolower($etudiant->getMailUniv())];
        sort($adresses);
        sort($attendues);
        self::assertSame($attendues, $adresses);
        self::assertCount(2, $invitations);
        foreach ($this->emails as $mail) {
            self::assertStringContainsString('/app/questionnaire-reponse/', (string) $mail->getHtmlBody());
        }
    }

    public function testRelanceSelonLesPreferences(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $collegue = $this->personnel($a);
        $q = $this->questionnaire($a);
        [$invitation] = $this->publier($q, $gestionnaire, [$collegue->getMailUniv()]);

        // Le collègue coupe les e-mails de rappel : la cloche reste informée.
        self::assertStatut(200, $this->requete('PUT', '/api/notifications/preferences', $collegue, [
            'reglages' => [TypeNotificationEnum::RELANCE_PERSONNEL->cle() => 'aucun'],
        ]));
        $this->emails = [];

        $reponse = $this->requete('POST', '/api/questionnaires/'.$q->getUuidString().'/reminders', $gestionnaire, ['invitations' => [$invitation->getId()]]);
        self::assertSame(1, $this->json($reponse)['sentCount'], 'Relance transmise (cloche).');
        self::assertSame([], $this->emails, 'Aucun e-mail : préférence « jamais ».');
        self::assertCount(1, $this->notifications(TypeNotificationEnum::RELANCE_PERSONNEL->cle()));
    }

    public function testBilanNotifieLAuteur(): void
    {
        $a = $this->departement('A');
        $auteur = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a, $auteur);
        $this->publier($q, $auteur, ['externe@exemple.test']);
        $q = $this->em->getRepository(Questionnaire::class)->find($q->getId());
        $q->setClosingDate(new \DateTime('-1 hour'));
        $this->enregistrer();

        $this->commande('app:questionnaire:check-completed');

        $notifs = $this->notifications(TypeNotificationEnum::BILAN->cle());
        self::assertCount(1, $notifs);
        self::assertSame('/app/questionnaire/qualite/enquetes/analytics/'.$q->getUuidString(), $notifs[0]->getUrl());
    }

    public function testInstallationDesTachesSansDoublon(): void
    {
        $this->commande('questionnaire:taches:installer');
        $this->commande('questionnaire:taches:installer');
        $nombre = (int) $this->em->getConnection()->fetchOne("SELECT COUNT(*) FROM scheduler_task WHERE name LIKE 'Questionnaires — %'");
        self::assertSame(3, $nombre);
    }

    /** @return list<Notification> */
    private function notifications(string $type): array
    {
        $this->em->clear();

        return $this->em->getRepository(Notification::class)->findBy(['type' => $type]);
    }
}
