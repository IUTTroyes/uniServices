<?php

namespace QuestionnaireBundle\Tests\Api;

use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use QuestionnaireBundle\Entity\Questionnaires\Questionnaire;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireInvitation;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireQuestion;
use QuestionnaireBundle\Entity\Questionnaires\QuestionnaireSection;
use QuestionnaireBundle\Enum\QuestStatutEnum;
use QuestionnaireBundle\Enum\QuestTypeQuestionEnum;
use QuestionnaireBundle\Enum\QuestTypeSectionEnum;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Tests HTTP de l'API des questionnaires, sur le modèle de StageApiTestCase : le noyau Symfony traite directement les
 * requêtes (environnement « test », base « <base>_test »), l'utilisateur est authentifié par un vrai jeton JWT, et chaque
 * test tourne dans une transaction annulée à la fin (la base de test reste vide).
 *
 * Mise en place (une fois) : base « <base>_test » accessible (back/.env.test.local), puis `make test-db`.
 * Sans base de test, ces tests sont ignorés.
 */
abstract class QuestionnaireApiTestCase extends TestCase
{
    private static ?Kernel $noyau = null;
    private static ?string $indisponible = null;

    protected EntityManagerInterface $em;
    private int $sequence = 0;
    /** @var list<Email> e-mails partis pendant le test (voir capturerEmails()) */
    protected array $emails = [];
    private ?\Closure $ecouteur = null;

    public static function setUpBeforeClass(): void
    {
        if (self::$noyau !== null || self::$indisponible !== null) {
            return;
        }
        $racine = \dirname(__DIR__, 4).'/back';
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
        // Debug : le conteneur se reconstruit dès que le code change (pas de cache de test périmé).
        $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '1';
        (new Dotenv())->bootEnv($racine.'/.env');
        $_SERVER['MAILER_DSN'] = $_ENV['MAILER_DSN'] = 'null://null';

        try {
            $noyau = new Kernel('test', true);
            $noyau->boot();
            $noyau->getContainer()->get('doctrine')->getConnection()->executeQuery('SELECT 1 FROM questionnaire LIMIT 1');
            self::$noyau = $noyau;
        } catch (\Throwable $e) {
            self::$indisponible = $e->getMessage();
        }
    }

    protected function setUp(): void
    {
        if (self::$noyau === null) {
            self::markTestSkipped('Base de test indisponible (make test-db) : '.self::$indisponible);
        }
        $this->em = $this->service('doctrine')->getManager();
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (self::$noyau === null) {
            return;
        }
        if ($this->ecouteur !== null) {
            $this->service('event_dispatcher')->removeListener(MessageEvent::class, $this->ecouteur);
            $this->ecouteur = null;
        }
        $connexion = $this->service('doctrine')->getConnection();
        while ($connexion->isTransactionActive()) {
            $connexion->rollBack();
        }
        $this->service('doctrine')->getManager()->clear();
    }

    protected function service(string $id): mixed
    {
        return self::$noyau->getContainer()->get('test.service_container')->get($id);
    }

    /** Capture les e-mails envoyés (MAILER_DSN=null://null : rien ne part réellement). */
    protected function capturerEmails(): void
    {
        $this->emails = [];
        $this->ecouteur = function (MessageEvent $e): void {
            if (!$e->isQueued() && $e->getMessage() instanceof Email) {
                $this->emails[] = $e->getMessage();
            }
        };
        $this->service('event_dispatcher')->addListener(MessageEvent::class, $this->ecouteur);
    }

    /** @param array<string, mixed> $options */
    protected function commande(string $nom, array $options = []): CommandTester
    {
        $testeur = new CommandTester((new Application(self::$noyau))->find($nom));
        $testeur->execute($options);
        $this->em = $this->service('doctrine')->getManager();

        return $testeur;
    }

    // ---------- Requêtes ----------

    /** @param array<string, mixed>|null $json */
    protected function requete(string $methode, string $uri, ?UserInterface $user = null, ?array $json = null): Response
    {
        $serveur = ['HTTP_ACCEPT' => 'application/json'];
        if ($json !== null) {
            $serveur['CONTENT_TYPE'] = $methode === 'PATCH' ? 'application/merge-patch+json' : 'application/json';
        }
        if ($user !== null) {
            $serveur['HTTP_AUTHORIZATION'] = 'Bearer '.$this->service('lexik_jwt_authentication.jwt_manager')->create($user);
        }
        $requete = Request::create($uri, $methode, [], [], [], $serveur, $json !== null ? json_encode($json, \JSON_THROW_ON_ERROR) : null);
        $reponse = self::$noyau->handle($requete);
        // Le noyau réinitialise ses services entre deux requêtes : on reprend le gestionnaire d'entités courant.
        $this->em = $this->service('doctrine')->getManager();

        return $reponse;
    }

    /** @return array<mixed> */
    protected function json(Response $reponse): array
    {
        return json_decode((string) $reponse->getContent(), true) ?? [];
    }

    protected static function assertStatut(int $attendu, Response $reponse): void
    {
        self::assertSame($attendu, $reponse->getStatusCode(), (string) $reponse->getContent());
    }

    // ---------- Jeux de données ----------

    protected function unique(string $prefixe): string
    {
        return sprintf('%s_%s_%d', $prefixe, substr(md5((string) microtime(true)), 0, 6), ++$this->sequence);
    }

    protected function departement(string $libelle = 'Département'): StructureDepartement
    {
        $d = (new StructureDepartement())->setLibelle($this->unique($libelle))->setActif(true);
        $this->em->persist($d);

        return $d;
    }

    /**
     * @param list<string> $permissions ex. ['ROLE_QUESTIONNAIRE_MANAGER'], ['ROLE_QUALITE'], ['SUPER_ADMIN']
     */
    protected function personnel(?StructureDepartement $departement = null, array $permissions = []): Personnel
    {
        $login = $this->unique('pers');
        $p = (new Personnel())->setUsername($login)->setMailUniv($login.'@exemple.test')->setPrenom('Prénom')->setNom(strtoupper($login));
        $this->em->persist($p);
        if ($departement !== null) {
            $dp = (new StructureDepartementPersonnel())->setPersonnel($p)->setDepartement($departement)
                ->setPermissions($permissions)->setPackages(['intranet', 'questionnaire'])->setDefaut(true)->setAffectation(true);
            $p->addDepartementPersonnel($dp);
            $this->em->persist($dp);
        }

        return $p;
    }

    protected function etudiant(): Etudiant
    {
        $login = $this->unique('etu');
        $e = (new Etudiant())->setUsername($login)->setMailUniv($login.'@exemple.test')->setPrenom('Alice')->setNom(strtoupper($login))
            ->setRoles(['ROLE_ETUDIANT'])->setBoursier(false)->setAnneeSortie(0)->setApplications(['questionnaire']);
        $this->em->persist($e);

        return $e;
    }

    /** Brouillon d'une section et d'une question obligatoire (texte court). */
    protected function questionnaire(?StructureDepartement $departement, ?Personnel $auteur = null, bool $anonyme = true): Questionnaire
    {
        $q = (new Questionnaire())->setTitle($this->unique('Enquête'))->setStatus(QuestStatutEnum::DRAFT)
            ->setDepartement($departement)->setAuthor($auteur);
        $q->setOpt(['anonymous' => $anonyme]);
        $this->em->persist($q);

        $section = (new QuestionnaireSection())->setTitle('Section 1')->setSortOrder(1)->setTypeSection(QuestTypeSectionEnum::normal);
        $q->addQuestionnaireSection($section);
        $this->em->persist($section);

        $question = (new QuestionnaireQuestion())->setLabel('Votre avis')->setTypeQuestion(QuestTypeQuestionEnum::TextShort)
            ->setRequired(true)->setSortOrder(1);
        $section->addQuestionnaireQuestion($question);
        $this->em->persist($question);

        return $q;
    }

    protected function anneeUniversitaire(): StructureAnneeUniversitaire
    {
        $a = (new StructureAnneeUniversitaire())->setLibelle($this->unique('2026/2027'))->setAnnee(2026)->setActif(true);
        $this->em->persist($a);

        return $a;
    }

    protected function enseignement(string $libelle): ScolEnseignement
    {
        $e = (new ScolEnseignement())->setLibelle($libelle)->setCodeEnseignement($this->unique('R'));
        $this->em->persist($e);

        return $e;
    }

    /**
     * Ajoute une section répétée (une section par élément) avec une question facultative.
     *
     * @param list<array{name: string, enseignementId?: int, personnelId?: int}> $elements
     */
    protected function sectionRepetee(Questionnaire $q, array $elements, string $type = 'ressource'): QuestionnaireSection
    {
        $section = (new QuestionnaireSection())->setTitle('Par enseignement')->setSortOrder(2)->setTypeSection(QuestTypeSectionEnum::configurable);
        $section->setOpt(['sourceType' => $type, 'titleTemplate' => '{element}', 'elements' => array_map(
            fn (array $e) => ['id' => (string) ($e['enseignementId'] ?? $this->unique('el'))] + $e,
            $elements,
        )]);
        $q->addQuestionnaireSection($section);
        $question = (new QuestionnaireQuestion())->setLabel('Satisfaction')->setTypeQuestion(QuestTypeQuestionEnum::TextShort)->setRequired(false)->setSortOrder(1);
        $section->addQuestionnaireQuestion($question);
        $this->em->persist($section);
        $this->em->persist($question);

        return $section;
    }

    protected function premiereQuestion(Questionnaire $q): QuestionnaireQuestion
    {
        return $q->getSections()->first()->getQuestions()->first();
    }

    /** Publie par l'API (en tant que $gestionnaire) et renvoie les invitations créées. */
    protected function publier(Questionnaire $q, Personnel $gestionnaire, array $emails = ['repondant@exemple.test']): array
    {
        $this->enregistrer();
        $reponse = $this->requete('POST', '/api/questionnaires/'.$q->getUuidString().'/publish', $gestionnaire, ['recipients' => $emails]);
        self::assertStatut(201, $reponse);

        return $this->em->getRepository(QuestionnaireInvitation::class)->findBy(['questionnaire' => $q->getId()]);
    }

    protected function iri(StructureDepartement $departement): string
    {
        return '/api/structure_departements/'.$departement->getId();
    }

    /** Enregistre les données préparées (dans la transaction du test). */
    protected function enregistrer(): void
    {
        $this->em->flush();
    }
}
