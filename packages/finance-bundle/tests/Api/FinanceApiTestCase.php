<?php

namespace FinanceBundle\Tests\Api;

use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Users\Personnel;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use FinanceBundle\Entity\FinanceBonCommande;
use FinanceBundle\Entity\FinanceFournisseur;
use FinanceBundle\Enum\StatutCommandeEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Tests HTTP de l'API du package Finances (même principe que StageApiTestCase) : noyau Symfony en environnement
 * « test » (base « <base>_test », `make test-db`), vrai jeton JWT, chaque test dans une transaction annulée.
 * Sans base de test, ces tests sont ignorés.
 */
abstract class FinanceApiTestCase extends TestCase
{
    private static ?Kernel $noyau = null;
    private static ?string $indisponible = null;
    protected static string $dossierDocuments = '';

    protected EntityManagerInterface $em;
    private int $sequence = 0;

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
        // Documents déposés pendant les tests : dossier temporaire, jamais var/finance.
        self::$dossierDocuments = sys_get_temp_dir().'/finance_documents_tests_'.getmypid();
        $_SERVER['FINANCE_DOCUMENTS_DIR'] = $_ENV['FINANCE_DOCUMENTS_DIR'] = self::$dossierDocuments;

        try {
            $noyau = new Kernel('test', true);
            $noyau->boot();
            $noyau->getContainer()->get('doctrine')->getConnection()->executeQuery('SELECT 1 FROM finance_bon_commande LIMIT 1');
            self::$noyau = $noyau;
        } catch (\Throwable $e) {
            self::$indisponible = $e->getMessage();
        }
    }

    protected function setUp(): void
    {
        if (self::$noyau === null) {
            self::markTestSkipped('Base de test indisponible (make test-db, back/.env.test.local) : '.self::$indisponible);
        }
        $this->em = $this->service('doctrine')->getManager();
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (self::$noyau === null) {
            return;
        }
        $connexion = $this->service('doctrine')->getConnection();
        while ($connexion->isTransactionActive()) {
            $connexion->rollBack();
        }
        $this->service('doctrine')->getManager()->clear();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$dossierDocuments !== '') {
            (new \Symfony\Component\Filesystem\Filesystem())->remove(self::$dossierDocuments);
        }
    }

    protected function service(string $id): mixed
    {
        return self::$noyau->getContainer()->get('test.service_container')->get($id);
    }

    // ---------- Requêtes ----------

    /** @param array<string, mixed>|null $json */
    protected function requete(string $methode, string $uri, ?UserInterface $user = null, ?array $json = null, array $fichiers = [], array $champs = []): Response
    {
        $serveur = ['HTTP_ACCEPT' => 'application/ld+json'];
        if ($json !== null) {
            $serveur['CONTENT_TYPE'] = $methode === 'PATCH' ? 'application/merge-patch+json' : 'application/ld+json';
        }
        if ($user !== null) {
            $serveur['HTTP_AUTHORIZATION'] = 'Bearer '.$this->service('lexik_jwt_authentication.jwt_manager')->create($user);
        }
        $requete = Request::create($uri, $methode, $champs, [], $fichiers, $serveur, $json !== null ? json_encode($json, \JSON_THROW_ON_ERROR) : null);
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

    /** @return list<int> identifiants d'une collection */
    protected function ids(Response $reponse): array
    {
        $data = $this->json($reponse);

        return array_map(static fn (array $e): int => (int) $e['id'], $data['member'] ?? $data['hydra:member'] ?? []);
    }

    protected function fichier(string $contenu, string $nom): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'fin');
        file_put_contents($tmp, $contenu);

        return new UploadedFile($tmp, $nom, null, null, true);
    }

    protected function pdf(): UploadedFile
    {
        return $this->fichier("%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF", 'signe.pdf');
    }

    /**
     * Constatation du service fait (formulaire multipart) avec `$pdf` bons de livraison.
     *
     * @param array<string, scalar> $champs
     */
    protected function constater(int $commande, UserInterface $user, array $champs = [], int $pdf = 1): Response
    {
        $fichiers = $pdf > 0 ? ['bonsLivraison' => array_map(fn (): UploadedFile => $this->fichier("%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF", 'BL.pdf'), range(1, $pdf))] : [];

        return $this->requete('POST', sprintf('/api/finance_bon_commandes/%d/transitions/constater_service_fait', $commande), $user, null, $fichiers, array_map('strval', $champs));
    }

    /** @param list<string>|null $numerosMigo */
    protected function viser(int $reception, UserInterface $user, ?array $numerosMigo = null): Response
    {
        return $this->requete('POST', sprintf('/api/finance_receptions/%d/viser', $reception), $user, $numerosMigo === null ? [] : ['numerosMigo' => $numerosMigo]);
    }

    /** @return list<int> réceptions de la demande, dans l'ordre */
    protected function receptions(int $commande): array
    {
        $this->em->clear();

        return array_values(array_map(static fn ($r): int => (int) $r->getId(), $this->em->find(FinanceBonCommande::class, $commande)->getReceptions()->toArray()));
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
     * @param list<string> $permissions ex. ['ROLE_FINANCE_MANAGER'], ['SUPER_ADMIN']
     */
    protected function personnel(?StructureDepartement $departement = null, array $permissions = []): Personnel
    {
        $login = $this->unique('pers');
        $p = (new Personnel())->setUsername($login)->setMailUniv($login.'@exemple.test')->setPrenom('Prénom')->setNom(strtoupper($login));
        $this->em->persist($p);
        if ($departement !== null) {
            $dp = (new StructureDepartementPersonnel())->setPersonnel($p)->setDepartement($departement)
                ->setPermissions($permissions)->setPackages(['intranet', 'finance'])->setDefaut(true)->setAffectation(true);
            $p->addDepartementPersonnel($dp);
            $this->em->persist($dp);
        }

        return $p;
    }

    protected function commande(StructureDepartement $departement, ?Personnel $demandeur, StatutCommandeEnum $statut = StatutCommandeEnum::BROUILLON): FinanceBonCommande
    {
        $c = (new FinanceBonCommande())->setDepartement($departement)->setDemandeur($demandeur)
            ->setObjetDemande($this->unique('Achat'))->setMontantHt('100.00')->setStatut($statut);
        $this->em->persist($c);

        return $c;
    }

    protected function fournisseur(string $nom = 'FOURNISSEUR'): FinanceFournisseur
    {
        $f = (new FinanceFournisseur())->setNomFournisseur($this->unique($nom))->setNumeroFournisseur('1000');
        $this->em->persist($f);

        return $f;
    }

    protected function iri(string $ressource, ?int $id): string
    {
        return sprintf('/api/%s/%d', $ressource, $id);
    }
}
