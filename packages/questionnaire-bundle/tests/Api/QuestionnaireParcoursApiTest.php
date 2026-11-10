<?php

namespace QuestionnaireBundle\Tests\Api;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Services\Dashboard\Provider\QuestionnaireWidgetDataProvider;

/**
 * Parcours fonctionnel : invitations réellement envoyées, ajout de participants, relances, « mes questionnaires »,
 * statistiques sur les seules réponses envoyées, widgets filtrés, clôture et bilan.
 */
final class QuestionnaireParcoursApiTest extends QuestionnaireApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->capturerEmails();
    }

    public function testLaPublicationEnvoieUneInvitationParAdresseAvecLeLienPublic(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a);

        $invitations = $this->publier($q, $gestionnaire, ['Alice@Exemple.test', 'alice@exemple.test', 'bob@exemple.test']);

        self::assertCount(2, $invitations, 'Une invitation par adresse (casse ignorée).');
        self::assertCount(2, $this->emails);
        $mail = $this->emails[0];
        self::assertStringContainsString((string) $q->getTitle(), (string) $mail->getSubject());
        $jeton = $this->jetonDe($invitations, $mail->getTo()[0]->getAddress());
        self::assertMatchesRegularExpression('#href="https?://[^"]+/app/questionnaire-reponse/'.$jeton.'"#', (string) $mail->getHtmlBody());
        self::assertSame(64, \strlen($jeton), 'Jeton de 256 bits.');
    }

    public function testPublicationRefuseUneAdresseInvalide(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a);
        $this->enregistrer();

        $reponse = $this->requete('POST', '/api/questionnaires/'.$q->getUuidString().'/publish', $gestionnaire, ['recipients' => ['pas-une-adresse']]);
        self::assertStatut(422, $reponse);
        self::assertSame([], $this->emails);
    }

    public function testAjoutDeParticipantsApresPublication(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $autre = $this->personnel($this->departement('B'), ['ROLE_QUESTIONNAIRE_MANAGER']);
        $brouillon = $this->questionnaire($a);
        $q = $this->questionnaire($a);
        $this->publier($q, $gestionnaire, ['deja@exemple.test']);
        $this->emails = [];
        $uri = '/api/questionnaires/'.$q->getUuidString().'/invitations';

        self::assertStatut(409, $this->requete('POST', '/api/questionnaires/'.$brouillon->getUuidString().'/invitations', $gestionnaire, ['emails' => ['x@exemple.test']]));
        self::assertStatut(403, $this->requete('POST', $uri, $autre, ['emails' => ['x@exemple.test']]));

        $reponse = $this->requete('POST', $uri, $gestionnaire, ['emails' => ['deja@exemple.test', 'nouveau@exemple.test']]);
        self::assertStatut(201, $reponse);
        self::assertSame(['invitationsCount' => 1, 'emailsCount' => 1, 'ignores' => 1], $this->json($reponse));
        self::assertCount(1, $this->emails);
        self::assertSame('nouveau@exemple.test', $this->emails[0]->getTo()[0]->getAddress());
    }

    public function testRelanceManuelleEnvoieLeMessageEtTraceLHistorique(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a);
        $invitation = $this->publier($q, $gestionnaire)[0];
        $this->emails = [];

        $reponse = $this->requete('POST', '/api/questionnaires/'.$q->getUuidString().'/reminders', $gestionnaire, [
            'invitations' => [$invitation->getId()],
            'subject' => 'Dernier rappel',
            'message' => 'Merci de répondre avant vendredi.',
        ]);
        self::assertStatut(200, $reponse);
        self::assertSame(1, $this->json($reponse)['sentCount']);
        self::assertCount(1, $this->emails);
        self::assertSame('Dernier rappel', $this->emails[0]->getSubject());
        self::assertStringContainsString('Merci de répondre avant vendredi.', (string) $this->emails[0]->getHtmlBody());
        self::assertStringContainsString('/app/questionnaire-reponse/', (string) $this->emails[0]->getHtmlBody());

        $invitation = $this->em->getRepository(QuestionnaireInvitation::class)->find($invitation->getId());
        self::assertSame(1, $invitation->getRemindersCount());
    }

    public function testMesQuestionnairesPourUnEtudiantEtUnPersonnel(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $etudiant = $this->etudiant();
        $enseignant = $this->personnel($a);
        $q = $this->questionnaire($a);
        $this->publier($q, $gestionnaire, [$etudiant->getMailUniv(), $enseignant->getMailUniv()]);
        $this->questionnaire($a); // brouillon : jamais listé

        foreach ([$etudiant, $enseignant] as $user) {
            $liste = $this->json($this->requete('GET', '/api/questionnaire/mes-invitations', $user));
            self::assertCount(1, $liste);
            self::assertSame($q->getUuidString(), $liste[0]['uuid']);
            self::assertTrue($liste[0]['ouvert']);
            self::assertSame(64, \strlen($liste[0]['token']), 'Le destinataire reçoit son propre jeton.');
        }
        self::assertSame([], $this->json($this->requete('GET', '/api/questionnaire/mes-invitations', $gestionnaire)));
        self::assertStatut(401, $this->requete('GET', '/api/questionnaire/mes-invitations'));
    }

    public function testStatistiquesSurLesSeulesReponsesEnvoyees(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a, null, false);
        [$envoyee, $commencee] = $this->publier($q, $gestionnaire, ['un@exemple.test', 'deux@exemple.test']);
        $section = (int) $this->em->getConnection()->fetchOne('SELECT id FROM questionnaire_section_instance WHERE questionnaire_id = ?', [$q->getId()]);
        $question = $this->premiereQuestion($q)->getId();

        foreach ([$envoyee, $commencee] as $i => $invitation) {
            $this->requete('PATCH', '/api/invitations/'.$invitation->getToken().'/answers', null, [
                'publishedSectionInstanceId' => $section,
                'answers' => [['questionId' => $question, 'value' => 'Réponse '.$i]],
            ]);
        }
        self::assertStatut(201, $this->requete('POST', '/api/invitations/'.$envoyee->getToken().'/submit', null, []));

        $stats = $this->json($this->requete('GET', '/api/questionnaires/'.$q->getUuidString().'/analytics', $gestionnaire));
        self::assertSame(1, $stats['totalResponses']);
        self::assertStringContainsString('Réponse 0', json_encode($stats, \JSON_UNESCAPED_UNICODE));
        self::assertStringNotContainsString('Réponse 1', json_encode($stats, \JSON_UNESCAPED_UNICODE), 'Une réponse non envoyée ne compte pas.');
    }

    public function testWidgetsLimitesAuPerimetre(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $gestionnaireA = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $gestionnaireB = $this->personnel($b, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $etudiant = $this->etudiant();
        $this->questionnaire($a);
        $qb = $this->questionnaire($b);
        $this->publier($qb, $gestionnaireB, [$etudiant->getMailUniv()]);

        /** @var QuestionnaireWidgetDataProvider $widgets */
        $widgets = $this->service(QuestionnaireWidgetDataProvider::class);
        $gestionnaireA = $this->em->getRepository(Personnel::class)->find($gestionnaireA->getId());
        $etudiant = $this->em->getRepository(Etudiant::class)->find($etudiant->getId());
        self::assertSame(['total' => 1, 'published' => 0, 'draft' => 1, 'closed' => 0], $widgets->getData('questionnaire.stats', $gestionnaireA));
        self::assertSame(['total' => 0, 'published' => 0, 'draft' => 0, 'closed' => 0], $widgets->getData('questionnaire.stats', $etudiant));

        $aRemplir = $widgets->getData('questionnaire.pending', $etudiant)['items'];
        self::assertCount(1, $aRemplir);
        self::assertSame($qb->getTitle(), $aRemplir[0]['titre']);
    }

    public function testClotureEtBilanALAuteur(): void
    {
        $a = $this->departement('A');
        $auteur = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a, $auteur);
        $this->publier($q, $auteur);
        $q = $this->em->getRepository(Questionnaire::class)->find($q->getId());
        $q->setClosingDate(new \DateTime('-1 hour'));
        $this->enregistrer();
        $this->emails = [];

        $this->commande('app:questionnaire:check-completed');

        $q = $this->em->getRepository(Questionnaire::class)->find($q->getId());
        self::assertSame(QuestStatutEnum::CLOSED, $q->getStatus());
        self::assertTrue($q->isCompletionNotified());
        self::assertCount(1, $this->emails);
        self::assertSame($auteur->getMailUniv(), $this->emails[0]->getTo()[0]->getAddress());
        self::assertStringContainsString('/app/questionnaire/qualite/enquetes/analytics/'.$q->getUuidString(), (string) $this->emails[0]->getHtmlBody());

        $this->commande('app:questionnaire:check-completed');
        self::assertCount(1, $this->emails, 'Un seul bilan par questionnaire.');
    }

    public function testRelancesAutomatiques(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a);
        $invitation = $this->publier($q, $gestionnaire)[0];
        $this->em->getConnection()->executeStatement('UPDATE questionnaire_invitation SET created_at = ? WHERE id = ?', [(new \DateTime('-5 days'))->format('Y-m-d H:i:s'), $invitation->getId()]);
        $this->emails = [];

        $this->commande('app:questionnaire:send-reminders', ['--dry-run' => true]);
        self::assertSame([], $this->emails, 'Simulation : aucun envoi.');

        $this->commande('app:questionnaire:send-reminders', ['--days' => '3']);
        self::assertCount(1, $this->emails);
        $this->commande('app:questionnaire:send-reminders', ['--days' => '3']);
        self::assertCount(1, $this->emails, 'Pas de nouvelle relance avant le délai.');
    }

    /** @param list<QuestionnaireInvitation> $invitations */
    private function jetonDe(array $invitations, string $email): string
    {
        foreach ($invitations as $invitation) {
            if ($invitation->getEmail() === $email) {
                return (string) $invitation->getToken();
            }
        }
        self::fail('Invitation introuvable pour '.$email);
    }
}
