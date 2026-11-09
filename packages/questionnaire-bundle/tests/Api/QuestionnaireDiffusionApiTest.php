<?php

namespace QuestionnaireBundle\Tests\Api;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Users\Personnel;
use IntranetBundle\Entity\Previsionnel\Previsionnel;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;

/**
 * Diffusion ciblée des résultats : pré-remplissage (intervenant, prévisionnel), validation, clôture, publication
 * (manuelle et automatique), restitution filtrée par destinataire avec seuil d'anonymat par section, retours.
 */
final class QuestionnaireDiffusionApiTest extends QuestionnaireApiTestCase
{
    /**
     * Questionnaire publié : une section générale + une section par ressource (R1 sur le prévisionnel de $prof1,
     * R2 rattachée à l'intervenant $prof2) ; deux invitations.
     *
     * @return array{0: Questionnaire, 1: Personnel, 2: Personnel, 3: Personnel, 4: list<\QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation>}
     */
    private function campagne(bool $anonyme = false, int $seuil = 0): array
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $prof1 = $this->personnel($a);
        $prof2 = $this->personnel($a);
        $annee = $this->anneeUniversitaire();
        $r1 = $this->enseignement('Ressource R1');
        $r2 = $this->enseignement('Ressource R2');
        $this->enregistrer();
        $this->em->persist((new Previsionnel())->setPersonnel($prof1)->setEnseignement($r1)->setAnneeUniversitaire($annee)->setReferent(true));

        $q = $this->questionnaire($a, $gestionnaire, $anonyme)->setAnneeUniversitaire($annee);
        $q->setOpt(['seuilMinReponses' => $seuil]);
        $this->sectionRepetee($q, [
            ['name' => 'Ressource R1', 'enseignementId' => $r1->getId()],
            ['name' => 'Ressource R2', 'enseignementId' => $r2->getId(), 'personnelId' => $prof2->getId()],
        ]);
        $invitations = $this->publier($q, $gestionnaire, ['un@exemple.test', 'deux@exemple.test']);

        return [$this->em->getRepository(Questionnaire::class)->find($q->getId()), $this->recharger($gestionnaire), $this->recharger($prof1), $this->recharger($prof2), $invitations];
    }

    public function testPreRemplissageValidationEtPublication(): void
    {
        [$q, $gestionnaire, $prof1, $prof2] = $this->campagne();
        $uri = '/api/questionnaires/'.$q->getUuidString();

        $etat = $this->json($this->requete('GET', $uri.'/diffusion', $gestionnaire));
        $parTitre = array_column($etat['sections'], null, 'titre');
        self::assertCount(3, $etat['sections']);
        self::assertSame([], $parTitre['Section 1']['destinataires'], 'Section générale : à compléter à la main.');
        self::assertSame([[$prof1->getId(), 'previsionnel']], array_map(fn ($d) => [$d['id'], $d['origine']], $parTitre['Ressource R1']['destinataires']));
        self::assertSame([[$prof2->getId(), 'intervenant']], array_map(fn ($d) => [$d['id'], $d['origine']], $parTitre['Ressource R2']['destinataires']));
        self::assertSame('Ressource R1', $parTitre['Ressource R1']['enseignement']['libelle']);
        self::assertStatut(403, $this->requete('GET', $uri.'/diffusion', $prof1), 'Réservé à l\'équipe.');

        // Validation (avec la section générale confiée à prof2), puis publication : refusée avant la clôture.
        $etat = $this->json($this->requete('PUT', $uri.'/diffusion', $gestionnaire, ['sections' => [$parTitre['Section 1']['id'] => [$prof2->getId()]], 'valider' => true]));
        self::assertNotNull($etat['validee']);
        self::assertStatut(409, $this->requete('POST', $uri.'/resultats/publier', $gestionnaire));
        self::assertStatut(403, $this->requete('GET', $uri.'/analytics', $prof1), 'Rien avant la publication.');

        self::assertStatut(200, $this->requete('POST', $uri.'/cloture', $gestionnaire));
        $publication = $this->json($this->requete('POST', $uri.'/resultats/publier', $gestionnaire));
        self::assertSame(2, $publication['notifies'], 'Une notification par personne.');
        self::assertSame(2, (int) $this->em->getConnection()->fetchOne("SELECT COUNT(*) FROM notification WHERE type = 'questionnaire.resultats'"));

        // Ajout après publication : le nouveau destinataire est notifié aussitôt.
        $autre = $this->personnel($this->em->getRepository(StructureDepartement::class)->find($q->getDepartement()->getId()));
        $this->enregistrer();
        $this->requete('PUT', $uri.'/diffusion', $gestionnaire, ['sections' => [$parTitre['Ressource R1']['id'] => [$prof1->getId(), $autre->getId()]]]);
        self::assertSame(3, (int) $this->em->getConnection()->fetchOne("SELECT COUNT(*) FROM notification WHERE type = 'questionnaire.resultats'"));
    }

    public function testRestitutionFiltreeEtSeuilParSection(): void
    {
        [$q, $gestionnaire, $prof1, , $invitations] = $this->campagne(true, 2);
        $uri = '/api/questionnaires/'.$q->getUuidString();
        $sections = array_column($this->json($this->requete('GET', $uri.'/diffusion', $gestionnaire))['sections'], 'id', 'titre');

        // Deux répondants à la section générale, un seul à R1.
        foreach ($invitations as $i => $invitation) {
            $reponses = [[$sections['Section 1'], $this->premiereQuestion($q)->getId(), 'Bien']];
            if ($i === 0) {
                $reponses[] = [$sections['Ressource R1'], $q->getSections()->last()->getQuestions()->first()->getId(), 'Très bien'];
            }
            foreach ($reponses as [$section, $question, $valeur]) {
                $this->requete('PATCH', '/api/invitations/'.$invitation->getToken().'/answers', null, ['publishedSectionInstanceId' => $section, 'answers' => [['questionId' => $question, 'value' => $valeur]]]);
            }
            self::assertStatut(201, $this->requete('POST', '/api/invitations/'.$invitation->getToken().'/submit', null, []));
        }
        $this->requete('PUT', $uri.'/diffusion', $gestionnaire, ['sections' => [], 'valider' => true]);
        $this->requete('POST', $uri.'/cloture', $gestionnaire);
        self::assertStatut(200, $this->requete('POST', $uri.'/resultats/publier', $gestionnaire));

        $vue = $this->json($this->requete('GET', $uri.'/analytics', $prof1));
        self::assertFalse($vue['gestion']);
        self::assertSame(['Ressource R1'], array_column($vue['sections'], 'sectionTitle'), 'Le destinataire ne voit que sa section.');
        self::assertTrue($vue['sections'][0]['masquee'], 'Un seul répondant sous le seuil de 2 : masqué.');
        self::assertSame([], $vue['sections'][0]['questions']);

        $equipe = array_column($this->json($this->requete('GET', $uri.'/analytics', $gestionnaire))['sections'], null, 'sectionTitle');
        self::assertCount(3, $equipe);
        self::assertFalse($equipe['Section 1']['masquee']);
        self::assertSame(2, $equipe['Section 1']['reponses']);

        $mes = $this->json($this->requete('GET', '/api/questionnaire/mes-resultats', $prof1));
        self::assertSame([$q->getUuidString()], array_column($mes, 'uuid'));
        self::assertSame(['Ressource R1'], array_column($mes[0]['sections'], 'titre'));
    }

    public function testRetoursDesEnseignants(): void
    {
        [$q, $gestionnaire, $prof1, $prof2] = $this->campagne();
        $uri = '/api/questionnaires/'.$q->getUuidString();
        $sections = array_column($this->json($this->requete('GET', $uri.'/diffusion', $gestionnaire))['sections'], 'id', 'titre');
        $this->requete('PUT', $uri.'/diffusion', $gestionnaire, ['sections' => [], 'valider' => true]);
        self::assertStatut(403, $this->requete('PUT', $uri.'/retours', $prof1, ['section' => $sections['Ressource R1'], 'commentaire' => 'Trop tôt']));
        $this->requete('POST', $uri.'/cloture', $gestionnaire);
        $this->requete('POST', $uri.'/resultats/publier', $gestionnaire);

        self::assertStatut(200, $this->requete('PUT', $uri.'/retours', $prof1, ['section' => $sections['Ressource R1'], 'commentaire' => 'Merci', 'planAction' => 'Plus d\'exercices']));
        self::assertStatut(403, $this->requete('PUT', $uri.'/retours', $prof1, ['section' => $sections['Ressource R2'], 'commentaire' => 'Pas la mienne']));

        $retours = $this->json($this->requete('GET', $uri.'/retours', $gestionnaire));
        self::assertSame(['Plus d\'exercices'], array_column($retours, 'planAction'));
        self::assertSame([], $this->json($this->requete('GET', $uri.'/retours', $prof2)), 'Chacun ne lit que ses retours.');
        self::assertSame(1, (int) $this->em->getConnection()->fetchOne("SELECT COUNT(*) FROM notification WHERE type = 'questionnaire.retour'"), 'L\'auteur est prévenu.');
    }

    public function testPublicationAutomatiqueSeulementSiValidee(): void
    {
        [$q, $gestionnaire] = $this->campagne();
        $q->getDepartement()->setOpt(['questionnaire' => ['delai_relecture_jours' => 0]]);
        $q->setClosingDate(new \DateTime('-1 hour'));
        $this->enregistrer();

        $this->commande('app:questionnaire:check-completed');
        $q = $this->em->getRepository(Questionnaire::class)->find($q->getId());
        self::assertSame('relecture', $q->getEtapeResultats(), 'Clôturé, diffusion pré-remplie mais non validée : pas de publication.');
        self::assertTrue($q->isDiffusionInitialisee());

        $this->requete('PUT', '/api/questionnaires/'.$q->getUuidString().'/diffusion', $this->recharger($gestionnaire), ['sections' => [], 'valider' => true]);
        $this->commande('app:questionnaire:check-completed');
        self::assertSame('publies', $this->em->getRepository(Questionnaire::class)->find($q->getId())->getEtapeResultats());
    }

    private function recharger(Personnel $p): Personnel
    {
        return $this->em->getRepository(Personnel::class)->find($p->getId());
    }
}
