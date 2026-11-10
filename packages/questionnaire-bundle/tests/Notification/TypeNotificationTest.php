<?php

namespace QuestionnaireBundle\Tests\Notification;

use App\Enum\ModeNotificationEnum;
use App\Notification\NotificationType;
use PHPUnit\Framework\TestCase;
use QuestionnaireBundle\Email\QuestionnaireCompletedEmail;
use QuestionnaireBundle\Email\QuestionnaireInvitationEtudiantEmail;
use QuestionnaireBundle\Email\QuestionnaireInvitationPersonnelEmail;
use QuestionnaireBundle\Email\QuestionnaireRelanceEtudiantEmail;
use QuestionnaireBundle\Email\QuestionnaireRelancePersonnelEmail;
use QuestionnaireBundle\Email\QuestionnaireResultatsEmail;
use QuestionnaireBundle\Enum\TypeNotificationEnum;
use QuestionnaireBundle\Services\Notification\QuestionnaireNotificationTypes;

/** Types de notification du package : définitions valides, publics, modes, e-mails dédiés de même clé. */
final class TypeNotificationTest extends TestCase
{
    public function testToutesLesDefinitionsSontValidesEtUniques(): void
    {
        $cles = [];
        foreach ((new QuestionnaireNotificationTypes())->getTypes() as $type) {
            self::assertInstanceOf(NotificationType::class, $type);
            self::assertSame('questionnaire', $type->source);
            $cles[] = $type->cle;
        }
        self::assertCount(\count(TypeNotificationEnum::cases()), array_unique($cles));
    }

    public function testPublicsEtModes(): void
    {
        self::assertSame(NotificationType::AUDIENCE_ETUDIANT, TypeNotificationEnum::invitationPour(true)->audience());
        self::assertSame(NotificationType::AUDIENCE_PERSONNEL, TypeNotificationEnum::relancePour(false)->audience());

        $invitation = TypeNotificationEnum::INVITATION_ETUDIANT->definition();
        self::assertFalse($invitation->autorise(ModeNotificationEnum::AUCUN), 'L\'invitation porte le lien personnel : jamais ignorée.');
        $relance = TypeNotificationEnum::RELANCE_PERSONNEL->definition();
        self::assertFalse($relance->autorise(ModeNotificationEnum::RESUME), 'Un rappel n\'a pas de sens dans un résumé.');
        self::assertTrue($relance->autorise(ModeNotificationEnum::AUCUN));
    }

    public function testChaqueTypeAUnEmailDedieDeMemeCle(): void
    {
        $emails = [
            new QuestionnaireInvitationEtudiantEmail(), new QuestionnaireInvitationPersonnelEmail(),
            new QuestionnaireRelanceEtudiantEmail(), new QuestionnaireRelancePersonnelEmail(),
            new QuestionnaireCompletedEmail(), new QuestionnaireResultatsEmail(),
        ];
        $cles = array_map(fn ($e) => $e->getKey(), $emails);
        // Le retour d'un enseignant utilise l'e-mail générique des notifications (pas de gabarit dédié).
        foreach (array_filter(TypeNotificationEnum::cases(), fn ($t) => $t !== TypeNotificationEnum::RETOUR) as $type) {
            self::assertContains($type->cle(), $cles);
        }
        foreach ($emails as $email) {
            self::assertSame('notification', $email->getCategorie(), $email->getKey());
        }
        self::assertSame('questionnaire.invitation', (new QuestionnaireInvitationEtudiantEmail())->getParentKey());
        self::assertSame('questionnaire.reminder', (new QuestionnaireRelancePersonnelEmail())->getParentKey());
    }
}
