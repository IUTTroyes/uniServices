<?php

namespace QuestionnaireBundle\Tests\Api;

use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;

/** Durée de conservation : réponses supprimées, invitations anonymisées, statistiques agrégées conservées. */
final class QuestionnairePurgeApiTest extends QuestionnaireApiTestCase
{
    public function testPurgeApresLaDureeDeConservation(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $ancien = $this->questionnaire($a, null, false);
        $recent = $this->questionnaire($a, null, false);
        $invitations = [];
        foreach ([$ancien, $recent] as $q) {
            [$invitation] = $this->publier($q, $gestionnaire, ['repondant@exemple.test']);
            $invitations[] = $invitation;
            $section = (int) $this->em->getConnection()->fetchOne('SELECT id FROM questionnaire_section_instance WHERE questionnaire_id = ?', [$q->getId()]);
            $this->requete('PATCH', '/api/invitations/'.$invitation->getToken().'/answers', null, ['publishedSectionInstanceId' => $section, 'answers' => [['questionId' => $this->premiereQuestion($q)->getId(), 'value' => 'Très bien']]]);
            self::assertStatut(201, $this->requete('POST', '/api/invitations/'.$invitation->getToken().'/submit', null, []));
            $this->requete('POST', '/api/questionnaires/'.$q->getUuidString().'/cloture', $gestionnaire);
        }
        // Clôturé il y a 6 ans (conservation par défaut : 5 ans).
        $this->em->getConnection()->executeStatement('UPDATE questionnaire SET cloture_le = ? WHERE id = ?', [(new \DateTime('-6 years'))->format('Y-m-d H:i:s'), $ancien->getId()]);
        $avant = $this->json($this->requete('GET', '/api/questionnaires/'.$ancien->getUuidString().'/analytics', $gestionnaire));

        $this->commande('questionnaire:purger', ['--dry-run' => true]);
        self::assertSame(2, $this->nombreDeReponses(), 'Simulation : rien n\'est supprimé.');

        $this->commande('questionnaire:purger');
        self::assertSame(1, $this->nombreDeReponses(), 'Seules les réponses du questionnaire ancien sont supprimées.');
        $this->em->clear();
        $invitation = $this->em->getRepository(QuestionnaireInvitation::class)->find($invitations[0]->getId());
        self::assertNull($invitation->getEmail());
        self::assertNotSame($invitations[0]->getToken(), $invitation->getToken(), 'L\'ancien lien ne fonctionne plus.');
        self::assertStatut(404, $this->requete('GET', '/api/invitations/'.$invitations[0]->getToken()));
        self::assertNotNull($this->em->getRepository(Questionnaire::class)->find($ancien->getId())->getPurgeLe());

        // Les statistiques restent consultables, à l'identique.
        $apres = $this->json($this->requete('GET', '/api/questionnaires/'.$ancien->getUuidString().'/analytics', $gestionnaire));
        self::assertNotNull($apres['archiveLe']);
        self::assertSame($avant['totalResponses'], $apres['totalResponses']);
        self::assertSame(array_column($avant['sections'], 'sectionTitle'), array_column($apres['sections'], 'sectionTitle'));
        self::assertSame($avant['sections'][0]['questions'][0]['stats'], $apres['sections'][0]['questions'][0]['stats']);

        $this->commande('questionnaire:purger');
        self::assertSame(1, $this->nombreDeReponses(), 'Une seule purge par questionnaire.');
    }

    private function nombreDeReponses(): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM questionnaire_answer');
    }
}
