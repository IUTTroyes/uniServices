<?php

namespace App\Tests\Security;

use App\Entity\Etablissement;
use App\Entity\ResetToken;
use App\Entity\Users\Personnel;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

/**
 * Parcours « mot de passe oublié » sur la base « <base>_test », dans une transaction annulée. Aucun e-mail ne part.
 * Sans base de test, les tests qui en ont besoin sont ignorés.
 */
class MotDePasseOublieTest extends TestCase
{
    private ?Kernel $noyau = null;
    private ?EntityManagerInterface $em = null;
    /** @var list<Email> */
    private array $envoyes = [];

    protected function tearDown(): void
    {
        if ($this->noyau !== null) {
            if ($this->em?->getConnection()->isTransactionActive()) {
                $this->em->getConnection()->rollBack();
            }
            $this->noyau->shutdown();
            restore_exception_handler();
        }
    }

    public function testUnCompteAvecMotDePasseRecoitUnLienVersLApplication(): void
    {
        $this->preparer('https://compte.example.org');
        $this->compte('camille@example.org', 'empreinte');

        $this->demander('camille@example.org');

        self::assertCount(1, $this->envoyes);
        self::assertSame('camille@example.org', $this->envoyes[0]->getTo()[0]->getAddress());
        $html = (string) $this->envoyes[0]->getHtmlBody();
        self::assertMatchesRegularExpression('~href="[^"]+/app/auth/reset-password/confirm\?token=[0-9a-f]{64}"~', $html);
        self::assertStringNotContainsString('compte.example.org', $html);
        self::assertSame(1, $this->em->getRepository(ResetToken::class)->count([]));
    }

    public function testUnCompteSansMotDePasseRecoitLAdresseUniversitaireEtAucunJeton(): void
    {
        $this->preparer('https://compte.example.org/mdp');
        $this->compte('dominique@example.org', null);

        $this->demander('dominique@example.org');

        self::assertCount(1, $this->envoyes);
        self::assertStringContainsString('https://compte.example.org/mdp', (string) $this->envoyes[0]->getHtmlBody());
        self::assertSame(0, $this->em->getRepository(ResetToken::class)->count([]));
    }

    public function testLaReponseEstIdentiqueEtRienNePartSansCompteNiAdresseConfiguree(): void
    {
        $this->preparer(null);
        $this->compte('camille@example.org', 'empreinte');
        $this->compte('dominique@example.org', null);

        $inconnu = $this->demander('inconnu@example.org');
        $sansUrl = $this->demander('dominique@example.org');
        self::assertSame([], $this->envoyes);
        $connu = $this->demander('camille@example.org');

        self::assertSame($inconnu, $sansUrl);
        self::assertSame($inconnu, $connu);
    }

    public function testLaPageDeConnexionPubliqueExposeLAdresse(): void
    {
        $this->preparer('https://compte.example.org');

        $reponse = $this->requete('GET', '/api/public/login-branding');

        self::assertSame('https://compte.example.org', json_decode((string) $reponse->getContent(), true)['url_mot_de_passe_cas']);
    }

    #[DataProvider('adresses')]
    public function testSeuleUneAdresseWebEstAcceptee(?string $saisie, ?string $attendue): void
    {
        $etablissement = (new Etablissement())->setSettings(['integrations' => ['cas' => ['urlMotDePasse' => $saisie]]]);

        self::assertSame($attendue, $etablissement->getUrlMotDePasseCas());
    }

    public static function adresses(): iterable
    {
        yield [' https://compte.example.org/mdp ', 'https://compte.example.org/mdp'];
        yield ['javascript:alert(1)', null];
        yield ['compte.example.org', null];
        yield [null, null];
    }

    private function preparer(?string $urlCas): void
    {
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
        (new Dotenv())->bootEnv(__DIR__.'/../../back/.env');
        $_SERVER['MAILER_DSN'] = $_ENV['MAILER_DSN'] = 'null://null';
        try {
            $this->noyau = new Kernel('test', true);
            $this->noyau->boot();
            $this->em = $this->noyau->getContainer()->get('doctrine')->getManager();
            $this->em->getConnection()->executeQuery('SELECT 1 FROM reset_token LIMIT 1');
        } catch (\Throwable $e) {
            self::markTestSkipped('Base de test indisponible : '.$e->getMessage());
        }
        $this->em->getConnection()->beginTransaction();
        $this->noyau->getContainer()->get('event_dispatcher')->addListener(MessageEvent::class, function (MessageEvent $e): void {
            if (!$e->isQueued() && $e->getMessage() instanceof Email) {
                $this->envoyes[] = $e->getMessage();
            }
        });

        // La base de test est vide : cet établissement est le seul.
        $etablissement = (new Etablissement())->setLibelle('IUT de Test');
        $etablissement->setIsMain(true);
        $etablissement->getUniversite()->setLibelle('Université de Test');
        $etablissement->setSettings(['integrations' => ['cas' => ['urlMotDePasse' => $urlCas]]]);
        $this->em->persist($etablissement);
        $this->em->flush();
    }

    private function compte(string $mail, ?string $motDePasse): void
    {
        $personnel = (new Personnel())->setUsername(strstr($mail, '@', true).bin2hex(random_bytes(3)))
            ->setMailUniv($mail)->setPrenom('Prénom')->setNom('Nom')->setPassword($motDePasse);
        $this->em->persist($personnel);
        $this->em->flush();
    }

    private function demander(string $mail): string
    {
        $reponse = $this->requete('POST', '/api/change_password', $mail);
        self::assertSame(200, $reponse->getStatusCode(), (string) $reponse->getContent());

        return (string) $reponse->getContent();
    }

    private function requete(string $methode, string $chemin, ?string $corps = null): Response
    {
        // Une adresse différente à chaque appel : la limite de trois demandes par heure n'est pas le sujet ici.
        $requete = Request::create($chemin, $methode, [], [], [], ['REMOTE_ADDR' => '10.'.random_int(0, 255).'.'.random_int(0, 255).'.'.random_int(1, 254)], $corps);

        return $this->noyau->handle($requete, catch: true);
    }
}
