<?php

namespace QuestionnaireBundle\Tests\Notification;

use App\Entity\Notification\Notification;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Notification\NotificationService;
use PHPUnit\Framework\TestCase;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Services\Notification\DestinataireResolver;
use QuestionnaireBundle\Services\Notification\QuestionnaireNotifier;
use QuestionnaireBundle\Services\QuestionnaireMailer;

/** Un compte est notifié avec le type de son public ; une adresse extérieure reçoit un e-mail direct. */
final class QuestionnaireNotifierTest extends TestCase
{
    /** @var list<array{0: Etudiant|Personnel, 1: string, 2: string, 3: ?string}> */
    private array $notifications = [];
    /** @var list<string> */
    private array $emails = [];

    private function notifier(Etudiant|Personnel|null $compte): QuestionnaireNotifier
    {
        $service = $this->createMock(NotificationService::class);
        $service->method('notifier')->willReturnCallback(function ($dest, $cle, $texte, $url = null, $envoi = null) {
            $this->notifications[] = [$dest, $cle, $texte, $url];
            $envoi && $envoi();

            return $this->createMock(Notification::class);
        });
        $mailer = $this->createMock(QuestionnaireMailer::class);
        $mailer->method('invitation')->willReturnCallback(function ($inv, $cle = 'questionnaire.invitation') {
            $this->emails[] = $cle;

            return true;
        });
        $mailer->method('relance')->willReturnCallback(function ($inv, $o = null, $m = null, $cle = 'questionnaire.reminder') {
            $this->emails[] = $cle;

            return true;
        });
        $resolver = $this->createMock(DestinataireResolver::class);
        $resolver->method('pourAdresse')->willReturn($compte);

        return new QuestionnaireNotifier($service, $mailer, $resolver);
    }

    private function invitation(): QuestionnaireInvitation
    {
        $q = (new Questionnaire())->setTitle('Satisfaction S3')->setClosingDate(new \DateTime('2027-01-31'));

        return new QuestionnaireInvitation($q, str_repeat('a', 64), 'x@exemple.fr');
    }

    public function testEtudiantNotifieAvecLeTypeEtudiant(): void
    {
        self::assertTrue($this->notifier(new Etudiant())->invitation($this->invitation()));
        [$dest, $cle, $texte, $url] = $this->notifications[0];
        self::assertSame('questionnaire.invitation_etudiant', $cle);
        self::assertStringContainsString('Satisfaction S3', $texte);
        self::assertStringContainsString('31/01/2027', $texte);
        self::assertSame(QuestionnaireNotifier::URL_REPONSE.str_repeat('a', 64), $url);
        self::assertSame(['questionnaire.invitation_etudiant'], $this->emails, 'E-mail dédié du type.');
    }

    public function testPersonnelRelanceAvecLeTypePersonnel(): void
    {
        $this->notifier(new Personnel())->relance($this->invitation());
        self::assertSame('questionnaire.relance_personnel', $this->notifications[0][1]);
        self::assertSame(['questionnaire.relance_personnel'], $this->emails);
    }

    public function testAdresseExterieureEmailDirect(): void
    {
        $notifier = $this->notifier(null);
        self::assertTrue($notifier->invitation($this->invitation()));
        self::assertTrue($notifier->relance($this->invitation()));
        self::assertSame([], $this->notifications);
        self::assertSame(['questionnaire.invitation', 'questionnaire.reminder'], $this->emails);
    }
}
