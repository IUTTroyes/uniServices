<?php

namespace QuestionnaireBundle\Tests\Api;

use App\Entity\Structure\StructureAnneeUniversitaire;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireQuestion;
use QuestionnaireBundle\Enum\QuestTypeQuestionEnum;

/** Bibliothèque de modèles (département, établissement) et comparaison des campagnes d'un même modèle. */
final class QuestionnaireModeleApiTest extends QuestionnaireApiTestCase
{
    public function testEnregistrerCommeModeleEtDroits(): void
    {
        $a = $this->departement('A');
        $b = $this->departement('B');
        $gestionnaireA = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $gestionnaireB = $this->personnel($b, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $admin = $this->personnel($this->departement('Admin'), ['SUPER_ADMIN']);
        $q = $this->questionnaire($a);
        $this->enregistrer();
        $uri = '/api/questionnaires/'.$q->getUuidString();

        $modele = $this->json($this->requete('POST', $uri.'/modele', $gestionnaireA, ['title' => 'Modèle A']));
        self::assertTrue($modele['estModele']);
        self::assertStatut(409, $this->requete('POST', '/api/questionnaires/'.$modele['uuid'].'/publish', $gestionnaireA, ['recipients' => ['x@exemple.test']]), 'Un modèle ne se publie pas.');
        self::assertStatut(422, $this->requete('PATCH', '/api/questionnaires/'.$modele['uuid'], $gestionnaireA, ['estModele' => false]));

        self::assertStatut(403, $this->requete('POST', $uri.'/modele', $gestionnaireA, ['departement' => null]), 'Modèle d\'établissement : super administrateur.');
        $etablissement = $this->json($this->requete('POST', $uri.'/modele', $admin, ['title' => 'Modèle commun', 'departement' => null]));

        // Le modèle d'établissement est visible et réutilisable par tout gestionnaire, mais pas modifiable.
        $titres = array_column($this->json($this->requete('GET', '/api/questionnaires?pagination=false&estModele=true', $gestionnaireB)), 'title');
        self::assertContains('Modèle commun', $titres);
        self::assertNotContains('Modèle A', $titres);
        self::assertStatut(403, $this->requete('PATCH', '/api/questionnaires/'.$etablissement['uuid'], $gestionnaireB, ['title' => 'Non']));
        self::assertStatut(403, $this->requete('POST', '/api/questionnaires/'.$etablissement['uuid'].'/instancier', $gestionnaireB, ['departement' => $a->getId()]));
        $campagne = $this->json($this->requete('POST', '/api/questionnaires/'.$etablissement['uuid'].'/instancier', $gestionnaireB, ['departement' => $b->getId(), 'title' => 'Campagne B']));
        self::assertFalse($campagne['estModele']);

        $this->em->clear(); // relire depuis la base (collections de la copie)
        $cree = $this->em->getRepository(Questionnaire::class)->findOneBy(['title' => 'Campagne B']);
        $q = $this->em->getRepository(Questionnaire::class)->find($q->getId());
        self::assertSame('Modèle commun', $cree->getModeleOrigine()?->getTitle());
        self::assertSame($b->getId(), $cree->getDepartement()?->getId());
        self::assertSame(
            $this->premiereQuestion($q)->cleComparaison(),
            $this->premiereQuestion($cree)->getOrigine(),
            'La question garde son origine au fil des copies.'
        );
    }

    public function testComparaisonDUneAnneeSurLAutre(): void
    {
        $a = $this->departement('A');
        $gestionnaire = $this->personnel($a, ['ROLE_QUESTIONNAIRE_MANAGER']);
        $modele = $this->questionnaire($a)->setEstModele(true)->setTitle('Satisfaction');
        $note = (new QuestionnaireQuestion())->setLabel('Note globale')->setTypeQuestion(QuestTypeQuestionEnum::Scale)->setRequired(false)->setSortOrder(2);
        $note->setOpt(['min' => 1, 'max' => 5]);
        $modele->getSections()->first()->addQuestionnaireQuestion($note);
        $this->em->persist($note);
        $annees = [];
        foreach ([2025, 2026] as $annee) {
            $annees[$annee] = (new StructureAnneeUniversitaire())->setLibelle($annee.'/'.($annee + 1))->setAnnee($annee)->setActif(false);
            $this->em->persist($annees[$annee]);
        }
        $this->enregistrer();

        foreach ([2025 => 3, 2026 => 5] as $annee => $valeur) {
            $this->em->getConnection()->executeStatement('UPDATE structure_annee_universitaire SET actif = (id = ?)', [$annees[$annee]->getId()]);
            $c = $this->json($this->requete('POST', '/api/questionnaires/'.$modele->getUuidString().'/instancier', $gestionnaire, ['title' => 'Satisfaction '.$annee]));
            $campagne = $this->em->getRepository(Questionnaire::class)->findOneBy(['uuid' => \Symfony\Component\Uid\Uuid::fromString($c['uuid'])]);
            [$invitation] = $this->publier($campagne, $gestionnaire, ['repondant'.$annee.'@exemple.test']);
            $campagne = $this->em->getRepository(Questionnaire::class)->find($campagne->getId());
            $section = (int) $this->em->getConnection()->fetchOne('SELECT id FROM questionnaire_section_instance WHERE questionnaire_id = ?', [$campagne->getId()]);
            $questions = $campagne->getSections()->first()->getQuestions();
            $this->requete('PATCH', '/api/invitations/'.$invitation->getToken().'/answers', null, ['publishedSectionInstanceId' => $section, 'answers' => [
                ['questionId' => $questions[0]->getId(), 'value' => 'ok'],
                ['questionId' => $questions[1]->getId(), 'value' => $valeur],
            ]]);
            self::assertStatut(201, $this->requete('POST', '/api/invitations/'.$invitation->getToken().'/submit', null, []));
        }

        $comparaison = $this->json($this->requete('GET', '/api/questionnaires/'.$modele->getUuidString().'/comparaison', $gestionnaire));
        self::assertSame(['2025/2026', '2026/2027'], array_column($comparaison['campagnes'], 'annee'));
        $parLibelle = array_column($comparaison['questions'], null, 'label');
        self::assertSame([3.0, 5.0], array_map(fn ($v) => (float) $v['moyenne'], $parLibelle['Note globale']['valeurs']));
    }
}
