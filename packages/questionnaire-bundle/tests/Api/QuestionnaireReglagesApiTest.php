<?php

namespace QuestionnaireBundle\Tests\Api;

use App\Entity\Structure\StructureDepartement;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Enum\QuestStatutEnum;

/**
 * Réglages des questionnaires d'un département (options du département, clé « questionnaire ») et leurs effets :
 * création, publication, modification des réponses, seuil d'anonymat, clôture automatique ou manuelle, référent qualité.
 */
final class QuestionnaireReglagesApiTest extends QuestionnaireApiTestCase
{
    public function testLectureEtEnregistrementReservesAuxGestionnairesDuDepartement(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUALITE']);
        $autre = $this->personnel($this->departement('B'), ['ROLE_QUESTIONNAIRE_MANAGER']);
        $collegue = $this->personnel($a);
        $exterieur = $this->personnel($this->departement('C'));
        $this->enregistrer();
        $uri = '/api/questionnaire/reglages/'.$a->getId();

        self::assertStatut(403, $this->requete('GET', $uri, $autre));
        self::assertSame(5, $this->json($this->requete('GET', $uri, $gestionnaire))['seuil_min_reponses'], 'Valeurs par défaut.');

        self::assertStatut(422, $this->requete('PUT', $uri, $gestionnaire, ['duree_jours' => 0]));
        self::assertStatut(422, $this->requete('PUT', $uri, $gestionnaire, ['inconnu' => true]));
        self::assertStatut(422, $this->requete('PUT', $uri, $gestionnaire, ['responsable_qualite' => $exterieur->getId()]));
        self::assertStatut(403, $this->requete('PUT', $uri, $autre, ['anonymat' => false]));

        $reponse = $this->requete('PUT', $uri, $gestionnaire, ['anonymat' => false, 'duree_jours' => 7, 'responsable_qualite' => $collegue->getId()]);
        self::assertStatut(200, $reponse);
        self::assertSame(7, $this->json($reponse)['duree_jours']);

        $enregistre = $this->em->getRepository(StructureDepartement::class)->find($a->getId())->getOpt()['questionnaire'];
        self::assertFalse($enregistre['anonymat']);
        self::assertSame(7, $enregistre['duree_jours']);
        self::assertSame($collegue->getId(), $enregistre['responsable_qualite']);
        self::assertTrue($enregistre['cloture_auto'], 'Les autres réglages gardent leur valeur.');
    }

    public function testCreationEtPublicationAppliquentLesReglages(): void
    {
        $a = $this->departement('A');
        $this->regler($a, ['anonymat' => false, 'modification_reponses' => false, 'seuil_min_reponses' => 3, 'duree_jours' => 10]);
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $this->enregistrer();

        self::assertStatut(201, $this->requete('POST', '/api/questionnaires', $gestionnaire, ['title' => 'Par défaut', 'departement' => $this->iri($a)]));
        self::assertStatut(201, $this->requete('POST', '/api/questionnaires', $gestionnaire, ['title' => 'Anonyme', 'departement' => $this->iri($a), 'opt' => ['anonymous' => true]]));

        $defaut = $this->em->getRepository(Questionnaire::class)->findOneBy(['title' => 'Par défaut']);
        self::assertFalse($defaut->estAnonyme());
        self::assertFalse($defaut->autoriseModificationReponses());
        self::assertSame(3, $defaut->getOpt()['seuilMinReponses']);
        self::assertTrue($this->em->getRepository(Questionnaire::class)->findOneBy(['title' => 'Anonyme'])->estAnonyme(), 'La valeur de l\'éditeur prime.');

        // Publication sans date de clôture : ouverture + 10 jours.
        $q = $this->questionnaire($this->em->getRepository(StructureDepartement::class)->find($a->getId()));
        $this->publier($q, $gestionnaire);
        $q = $this->em->getRepository(Questionnaire::class)->find($q->getId());
        self::assertSame((new \DateTime('+10 days'))->format('Y-m-d'), $q->getClosingDate()?->format('Y-m-d'));
    }

    public function testModificationDesReponsesEnvoyees(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a);
        $inv = $this->publier($q, $gestionnaire)[0];
        $base = '/api/invitations/'.$inv->getToken();
        $corps = fn (string $v) => ['publishedSectionInstanceId' => $this->instance($q), 'answers' => [['questionId' => $this->premiereQuestion($q)->getId(), 'value' => $v]]];

        $this->requete('PATCH', $base.'/answers', null, $corps('Bien'));
        self::assertStatut(201, $this->requete('POST', $base.'/submit', null, []));
        self::assertTrue($this->json($this->requete('GET', $base))['modifiable']);
        self::assertStatut(200, $this->requete('PATCH', $base.'/answers', null, $corps('Très bien')), 'Modification autorisée tant que c\'est ouvert.');

        $q = $this->em->getRepository(Questionnaire::class)->find($q->getId());
        $q->setOpt(['modificationReponses' => false]);
        $this->enregistrer();
        self::assertFalse($this->json($this->requete('GET', $base))['modifiable']);
        self::assertStatut(409, $this->requete('PATCH', $base.'/answers', null, $corps('Changé')));
    }

    public function testSeuilDAnonymatMasqueLesResultats(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a, null, true);
        $q->setOpt(['seuilMinReponses' => 2]);
        [$un, $deux] = $this->publier($q, $gestionnaire, ['un@exemple.test', 'deux@exemple.test']);
        $analytics = '/api/questionnaires/'.$q->getUuidString().'/analytics';

        $this->repondre($q, $un, 'A');
        $stats = $this->json($this->requete('GET', $analytics, $gestionnaire));
        self::assertFalse($stats['seuilAtteint']);
        self::assertSame([], $stats['sections']);
        self::assertStatut(409, $this->requete('GET', '/api/questionnaires/'.$q->getUuidString().'/export-excel', $gestionnaire));
        self::assertStatut(409, $this->requete('PATCH', '/api/questionnaires/'.$q->getUuidString(), $gestionnaire, ['opt' => ['seuilMinReponses' => 0]]), 'Seuil figé après publication.');

        $this->repondre($q, $deux, 'B');
        $stats = $this->json($this->requete('GET', $analytics, $gestionnaire));
        self::assertTrue($stats['seuilAtteint']);
        self::assertNotSame([], $stats['sections']);
    }

    public function testClotureManuelleQuandLaClotureAutomatiqueEstDesactivee(): void
    {
        $a = $this->departement('A');
        $auteur = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $referent = $this->personnel($a);
        $this->enregistrer();
        $this->regler($a, ['cloture_auto' => false, 'responsable_qualite' => $referent->getId()]);
        $q = $this->questionnaire($a, $auteur);
        $this->publier($q, $auteur);
        $q = $this->em->getRepository(Questionnaire::class)->find($q->getId());
        $q->setClosingDate(new \DateTime('-1 hour'));
        $this->enregistrer();
        $this->capturerEmails();

        $this->commande('app:questionnaire:check-completed');
        self::assertSame(QuestStatutEnum::PUBLISHED, $this->em->getRepository(Questionnaire::class)->find($q->getId())->getStatus());
        self::assertSame([], $this->emails);

        self::assertStatut(403, $this->requete('POST', '/api/questionnaires/'.$q->getUuidString().'/cloture', $referent), 'Le référent consulte, il ne gère pas.');
        self::assertStatut(200, $this->requete('POST', '/api/questionnaires/'.$q->getUuidString().'/cloture', $auteur));
        self::assertStatut(409, $this->requete('POST', '/api/questionnaires/'.$q->getUuidString().'/cloture', $auteur));

        $this->commande('app:questionnaire:check-completed');
        $destinataires = array_map(fn ($m) => $m->getTo()[0]->getAddress(), $this->emails);
        sort($destinataires);
        $attendus = [$auteur->getMailUniv(), $referent->getMailUniv()];
        sort($attendus);
        self::assertSame($attendus, $destinataires, 'Bilan à l\'auteur et au référent qualité.');
    }

    public function testLeReferentQualiteConsulteSansGerer(): void
    {
        $a = $this->departement('A');
        $referent = $this->personnel($a);
        $this->enregistrer();
        $this->regler($a, ['responsable_qualite' => $referent->getId()]);
        $q = $this->questionnaire($a);
        $this->enregistrer();

        self::assertContains($q->getTitle(), array_column($this->json($this->requete('GET', '/api/questionnaires?pagination=false', $referent)), 'title'));
        self::assertStatut(200, $this->requete('GET', '/api/questionnaires/'.$q->getUuidString().'/analytics', $referent));
        self::assertStatut(403, $this->requete('PATCH', '/api/questionnaires/'.$q->getUuidString(), $referent, ['title' => 'Non']));
        self::assertStatut(403, $this->requete('GET', '/api/questionnaire/reglages/'.$a->getId(), $referent));
    }

    /** @param array<string, mixed> $reglages */
    private function regler(StructureDepartement $departement, array $reglages): void
    {
        $departement->setOpt(['questionnaire' => $reglages]);
        $this->enregistrer();
    }

    private function instance(Questionnaire $q): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT id FROM questionnaire_section_instance WHERE questionnaire_id = ? LIMIT 1', [$q->getId()]);
    }

    private function repondre(Questionnaire $q, \QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation $invitation, string $valeur): void
    {
        $base = '/api/invitations/'.$invitation->getToken();
        $this->requete('PATCH', $base.'/answers', null, ['publishedSectionInstanceId' => $this->instance($q), 'answers' => [['questionId' => $this->premiereQuestion($q)->getId(), 'value' => $valeur]]]);
        self::assertStatut(201, $this->requete('POST', $base.'/submit', null, []));
    }
}
