<?php

namespace QuestionnaireBundle\Tests\Api;

use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Enum\QuestStatutEnum;

/**
 * Droits sur les questionnaires : profil, département, auteur, champs tenus par le serveur, structure figée.
 */
final class QuestionnaireSecuriteApiTest extends QuestionnaireApiTestCase
{
    public function testAnonymeEtEtudiantSontRefuses(): void
    {
        $etudiant = $this->etudiant();
        $this->enregistrer();

        self::assertStatut(401, $this->requete('GET', '/api/questionnaires'));
        self::assertStatut(403, $this->requete('GET', '/api/questionnaires', $etudiant));
    }

    public function testListeRestreinteAuDepartementGereEtAuxQuestionnairesDeLAuteur(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $gestionnaireA = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $qualiteB = $this->personnel($b, ['ROLE_QUALITE']);
        $simple = $this->personnel($a);
        $qa = $this->questionnaire($a);
        $qb = $this->questionnaire($b);
        $qAuteur = $this->questionnaire($b, $simple);
        $etablissement = $this->questionnaire(null);
        $this->enregistrer();

        $titres = fn ($user) => array_column($this->json($this->requete('GET', '/api/questionnaires?pagination=false', $user)), 'title');

        $vusA = $titres($gestionnaireA);
        self::assertContains($qa->getTitle(), $vusA);
        self::assertNotContains($qb->getTitle(), $vusA);
        self::assertNotContains($etablissement->getTitle(), $vusA, 'Un questionnaire d\'établissement est réservé au super administrateur.');

        $vusB = $titres($qualiteB);
        self::assertContains($qb->getTitle(), $vusB);
        self::assertContains($qAuteur->getTitle(), $vusB);
        self::assertNotContains($qa->getTitle(), $vusB);

        self::assertSame([$qAuteur->getTitle()], $titres($simple), 'Sans rôle, on ne voit que ses propres questionnaires.');

        self::assertStatut(403, $this->requete('GET', '/api/questionnaires/'.$qb->getUuidString(), $gestionnaireA));
        self::assertStatut(200, $this->requete('GET', '/api/questionnaires/'.$qa->getUuidString(), $gestionnaireA));
        self::assertStatut(200, $this->requete('GET', '/api/questionnaires/'.$qAuteur->getUuidString(), $simple));
    }

    public function testSuperAdminVoitTout(): void
    {
        $a = $this->departement('A');
        $admin = $this->personnel($this->departement('Admin'), ['SUPER_ADMIN']);
        $qa = $this->questionnaire($a);
        $etablissement = $this->questionnaire(null);
        $this->enregistrer();

        $titres = array_column($this->json($this->requete('GET', '/api/questionnaires?pagination=false', $admin)), 'title');
        self::assertContains($qa->getTitle(), $titres);
        self::assertContains($etablissement->getTitle(), $titres);
    }

    public function testCreationReserveeAuxGestionnairesDuDepartementEtChampsServeurRefuses(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $gestionnaireA = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $simple = $this->personnel($a);
        $this->enregistrer();

        self::assertStatut(403, $this->requete('POST', '/api/questionnaires', $gestionnaireA, ['title' => 'X', 'departement' => $this->iri($b)]));
        self::assertStatut(403, $this->requete('POST', '/api/questionnaires', $simple, ['title' => 'X', 'departement' => $this->iri($a)]));
        self::assertStatut(403, $this->requete('POST', '/api/questionnaires', $gestionnaireA, ['title' => 'X']), 'Établissement : super administrateur seulement.');

        $refus = $this->requete('POST', '/api/questionnaires', $gestionnaireA, ['title' => 'X', 'departement' => $this->iri($a), 'status' => 'published']);
        self::assertStatut(422, $refus);
        self::assertStringContainsString('status', (string) $refus->getContent());

        $cree = $this->requete('POST', '/api/questionnaires', $gestionnaireA, ['title' => 'Nouvelle enquête', 'departement' => $this->iri($a), 'opt' => ['anonymous' => false]]);
        self::assertStatut(201, $cree);
        self::assertSame('draft', $this->json($cree)['status']);
        $q = $this->em->getRepository(Questionnaire::class)->findOneBy(['title' => 'Nouvelle enquête']);
        self::assertSame($gestionnaireA->getId(), $q?->getAuthor()?->getId(), 'L\'auteur est la personne connectée.');
    }

    public function testModificationChampsServeurEtChangementDeDepartement(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $gestionnaireA = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a);
        $this->enregistrer();
        $uri = '/api/questionnaires/'.$q->getUuidString();

        self::assertStatut(422, $this->requete('PATCH', $uri, $gestionnaireA, ['status' => 'published']));
        self::assertStatut(422, $this->requete('PATCH', $uri, $gestionnaireA, ['completionNotified' => true]));
        self::assertStatut(403, $this->requete('PATCH', $uri, $gestionnaireA, ['departement' => $this->iri($b)]));
        self::assertStatut(422, $this->requete('PATCH', $uri, $gestionnaireA, ['openingDate' => '2027-01-10T00:00:00+00:00', 'closingDate' => '2027-01-01T00:00:00+00:00']));
        self::assertStatut(200, $this->requete('PATCH', $uri, $gestionnaireA, ['title' => 'Renommé']));
    }

    public function testStructureProtegeeParDepartementEtFigeeApresPublication(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $gestionnaireA = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $gestionnaireB = $this->personnel($b, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a);
        $this->enregistrer();
        $sections = '/api/questionnaires/'.$q->getUuidString().'/questionnaire_sections';
        $section = $q->getSections()->first();
        $question = $this->premiereQuestion($q);

        self::assertStatut(403, $this->requete('POST', $sections, $gestionnaireB, ['title' => 'Intrus', 'sortOrder' => 2, 'typeSection' => 'normal']));
        self::assertStatut(403, $this->requete('PATCH', '/api/questionnaire_questions/'.$question->getUuid(), $gestionnaireB, ['label' => 'Piraté']));
        self::assertSame([], $this->json($this->requete('GET', $sections, $gestionnaireB)), 'Les sections d\'un autre département ne sont pas listées.');
        // Questions imbriquées : refusées (API Platform n'accepte que des IRI ; le processeur refuse aussi la clé).
        self::assertGreaterThanOrEqual(400, $this->requete('PATCH', $sections.'/'.$section->getUuid(), $gestionnaireA, ['questions' => [['label' => 'imbriquée']]])->getStatusCode());
        self::assertStatut(422, $this->requete('PATCH', $sections.'/'.$section->getUuid(), $gestionnaireA, ['questions' => []]));

        self::assertStatut(201, $this->requete('POST', $sections, $gestionnaireA, ['title' => 'Section 2', 'sortOrder' => 2, 'typeSection' => 'normal']));

        $this->publier($q, $gestionnaireA);
        self::assertStatut(409, $this->requete('POST', $sections, $gestionnaireA, ['title' => 'Trop tard', 'sortOrder' => 3, 'typeSection' => 'normal']));
        self::assertStatut(409, $this->requete('PATCH', '/api/questionnaire_questions/'.$question->getUuid(), $gestionnaireA, ['label' => 'Modifiée']));
        self::assertStatut(409, $this->requete('DELETE', $sections.'/'.$section->getUuid(), $gestionnaireA));
        self::assertStatut(409, $this->requete('POST', '/api/questionnaires/'.$q->getUuidString().'/publish', $gestionnaireA, ['recipients' => []]));
    }

    public function testAnonymatNePeutPlusChangerApresPublication(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a, null, true);
        $this->publier($q, $gestionnaire);

        self::assertStatut(409, $this->requete('PATCH', '/api/questionnaires/'.$q->getUuidString(), $gestionnaire, ['opt' => ['anonymous' => false]]));
    }

    public function testRessourcesDeGestionProtegees(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $gestionnaireA = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $gestionnaireB = $this->personnel($b, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a);
        $invitations = $this->publier($q, $gestionnaireA);
        $base = '/api/questionnaires/'.$q->getUuidString();

        foreach (['/preview', '/analytics', '/export-excel'] as $suffixe) {
            self::assertStatut(403, $this->requete('GET', $base.$suffixe, $gestionnaireB), $suffixe);
        }
        self::assertStatut(403, $this->requete('POST', $base.'/duplicate', $gestionnaireB, ['newTitle' => 'Copie']));
        self::assertStatut(403, $this->requete('POST', $base.'/reminders', $gestionnaireB, ['invitations' => [$invitations[0]->getId()]]));
        self::assertStatut(403, $this->requete('DELETE', $base, $gestionnaireB));
        self::assertStatut(404, $this->requete('GET', '/api/questionnaires/pas-un-uuid/analytics', $gestionnaireA));
        self::assertStatut(200, $this->requete('GET', $base.'/analytics', $gestionnaireA));

        $copie = $this->requete('POST', $base.'/duplicate', $gestionnaireA, ['newTitle' => 'Copie A']);
        self::assertStatut(201, $copie);
        $duplique = $this->em->getRepository(Questionnaire::class)->findOneBy(['title' => 'Copie A']);
        self::assertSame($gestionnaireA->getId(), $duplique?->getAuthor()?->getId(), 'La copie appartient à qui la crée.');
    }

    public function testInvitationsSansJetonEtReponsesMasqueesSiAnonyme(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $autre = $this->personnel($this->departement('B'), ['ROLE_QUESTIONNAIRE_MANAGER']);
        $anonyme = $this->questionnaire($a, null, true);
        $nominatif = $this->questionnaire($a, null, false);
        $invAnonyme = $this->publier($anonyme, $gestionnaire)[0];
        $invNominatif = $this->publier($nominatif, $gestionnaire)[0];

        foreach ([[$anonyme, $invAnonyme], [$nominatif, $invNominatif]] as [$q, $inv]) {
            $this->requete('PATCH', '/api/invitations/'.$inv->getToken().'/answers', null, [
                'publishedSectionInstanceId' => $this->instance($q),
                'answers' => [['questionId' => $this->premiereQuestion($q)->getId(), 'value' => 'Très bien']],
            ]);
        }

        $liste = fn (Questionnaire $q, $user) => $this->json($this->requete('GET', '/api/questionnaire_invitations?pagination=false&questionnaire=/api/questionnaires/'.$q->getUuidString(), $user));

        $lignes = $liste($anonyme, $gestionnaire);
        self::assertCount(1, $lignes);
        self::assertArrayNotHasKey('token', $lignes[0], 'Le jeton (clé d\'accès du répondant) n\'est jamais exposé.');
        self::assertSame([], $lignes[0]['questionnaireReponses'], 'Questionnaire anonyme : pas de réponses individuelles.');

        $lignes = $liste($nominatif, $gestionnaire);
        self::assertCount(1, $lignes[0]['questionnaireReponses']);

        self::assertSame([], $liste($nominatif, $autre), 'Les invitations d\'un autre département ne sont pas listées.');
    }

    public function testReponsesParJetonControlees(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a);
        $q->setOpt(['modificationReponses' => false]); // réponses envoyées définitives
        $autre = $this->questionnaire($a);
        $inv = $this->publier($q, $gestionnaire)[0];
        $this->publier($autre, $gestionnaire);
        $base = '/api/invitations/'.$inv->getToken();
        $section = $this->instance($q);

        self::assertStatut(404, $this->requete('GET', '/api/invitations/inconnu'));
        self::assertStatut(200, $this->requete('GET', $base));
        self::assertStatut(404, $this->requete('GET', $base.'/sections/'.$this->instance($autre)));

        // Question d'un autre questionnaire : refusée.
        self::assertStatut(422, $this->requete('PATCH', $base.'/answers', null, [
            'publishedSectionInstanceId' => $section,
            'answers' => [['questionId' => $this->premiereQuestion($autre)->getId(), 'value' => 'x']],
        ]));
        // Réponse obligatoire manquante : soumission refusée.
        self::assertStatut(422, $this->requete('POST', $base.'/submit', null, []));

        self::assertStatut(200, $this->requete('PATCH', $base.'/answers', null, [
            'publishedSectionInstanceId' => $section,
            'answers' => [['questionId' => $this->premiereQuestion($q)->getId(), 'value' => 'Bien']],
        ]));
        self::assertStatut(201, $this->requete('POST', $base.'/submit', null, []));
        self::assertStatut(409, $this->requete('PATCH', $base.'/answers', null, [
            'publishedSectionInstanceId' => $section,
            'answers' => [['questionId' => $this->premiereQuestion($q)->getId(), 'value' => 'Changé']],
        ]), 'Réponses déjà envoyées.');
    }

    public function testQuestionnaireFermeRefuseLesReponses(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a);
        $inv = $this->publier($q, $gestionnaire)[0];
        $q = $this->em->getRepository(Questionnaire::class)->find($q->getId());
        $q->setStatus(QuestStatutEnum::CLOSED);
        $this->enregistrer();

        self::assertStatut(409, $this->requete('PATCH', '/api/invitations/'.$inv->getToken().'/answers', null, [
            'publishedSectionInstanceId' => $this->instance($q),
            'answers' => [['questionId' => $this->premiereQuestion($q)->getId(), 'value' => 'x']],
        ]));
        self::assertStatut(409, $this->requete('POST', '/api/questionnaires/'.$q->getUuidString().'/reminders', $gestionnaire, ['invitations' => [$inv->getId()]]));
    }

    public function testSuppressionDUnQuestionnaireAvecReponses(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $q = $this->questionnaire($a);
        $inv = $this->publier($q, $gestionnaire)[0];
        $this->requete('PATCH', '/api/invitations/'.$inv->getToken().'/answers', null, [
            'publishedSectionInstanceId' => $this->instance($q),
            'answers' => [['questionId' => $this->premiereQuestion($q)->getId(), 'value' => 'x']],
        ]);

        self::assertStatut(204, $this->requete('DELETE', '/api/questionnaires/'.$q->getUuidString(), $gestionnaire));
        self::assertNull($this->em->getRepository(Questionnaire::class)->find($q->getId()));
    }

    public function testUnEtudiantNAccedeAAucuneRouteDeGestion(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $etudiant = $this->etudiant();
        $q = $this->questionnaire($a);
        [$invitation] = $this->publier($q, $gestionnaire, [$etudiant->getMailUniv()]);
        $base = '/api/questionnaires/'.$q->getUuidString();

        foreach (['GET' => [$base, $base.'/preview', $base.'/analytics', $base.'/export-excel', $base.'/questionnaire_sections', '/api/questionnaire/reglages/'.$a->getId(), '/api/questionnaire/mes-resultats']] as $methode => $uris) {
            foreach ($uris as $uri) {
                self::assertContains($this->requete($methode, $uri, $etudiant)->getStatusCode(), [403], $uri);
            }
        }
        foreach ([$base.'/publish' => ['recipients' => []], $base.'/duplicate' => [], $base.'/reminders' => ['invitations' => [$invitation->getId()]], $base.'/invitations' => ['emails' => ['x@exemple.test']], $base.'/cloture' => []] as $uri => $corps) {
            self::assertStatut(403, $this->requete('POST', $uri, $etudiant, $corps));
        }
        self::assertStatut(403, $this->requete('GET', '/api/questionnaire_invitations', $etudiant));
        self::assertStatut(403, $this->requete('DELETE', $base, $etudiant));

        // Ce qui le concerne reste accessible.
        self::assertCount(1, $this->json($this->requete('GET', '/api/questionnaire/mes-invitations', $etudiant)));
        self::assertStatut(200, $this->requete('GET', '/api/invitations/'.$invitation->getToken()));
    }

    private function instance(Questionnaire $q): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT id FROM questionnaire_section_instance WHERE questionnaire_id = ? ORDER BY sort_order LIMIT 1', [$q->getId()]);
    }
}
