<?php

namespace StageBundle\Tests\Api;

use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructurePn;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureTypeDiplome;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Entity\Stages\StagePeriode;
use StageBundle\Enum\EtatStageEnum;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Tests HTTP de l'API du bundle stage, sans dépendance supplémentaire : le noyau Symfony traite directement les requêtes
 * (environnement « test », base « <base>_test »), l'utilisateur est authentifié par un vrai jeton JWT, et chaque test
 * tourne dans une transaction annulée à la fin (la base de test reste vide).
 *
 * Mise en place (une fois) : voir packages/stage-bundle/docs/tests.md. Sans base de test, ces tests sont ignorés.
 */
abstract class StageApiTestCase extends TestCase
{
    private static ?Kernel $noyau = null;
    private static ?string $indisponible = null;
    private static string $dossierRapports = '';

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
        // Rapports déposés pendant les tests : dossier temporaire, jamais var/stage/rapports.
        self::$dossierRapports = sys_get_temp_dir().'/stage_rapports_tests_'.getmypid();
        $_SERVER['STAGE_RAPPORT_DIR'] = $_ENV['STAGE_RAPPORT_DIR'] = self::$dossierRapports;

        try {
            $noyau = new Kernel('test', true);
            $noyau->boot();
            $noyau->getContainer()->get('doctrine')->getConnection()->executeQuery('SELECT 1 FROM stage_etudiant LIMIT 1');
            self::$noyau = $noyau;
        } catch (\Throwable $e) {
            self::$indisponible = $e->getMessage();
        }
    }

    protected function setUp(): void
    {
        if (self::$noyau === null) {
            self::markTestSkipped('Base de test indisponible (voir packages/stage-bundle/docs/tests.md) : '.self::$indisponible);
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
        if (self::$dossierRapports !== '') {
            (new \Symfony\Component\Filesystem\Filesystem())->remove(self::$dossierRapports);
        }
    }

    protected function service(string $id): mixed
    {
        return self::$noyau->getContainer()->get('test.service_container')->get($id);
    }

    // ---------- Requêtes ----------

    /** @param array<string, mixed>|null $json */
    protected function requete(string $methode, string $uri, ?UserInterface $user = null, ?array $json = null, array $fichiers = [], ?string $contentType = null): Response
    {
        $serveur = ['HTTP_ACCEPT' => 'application/json'];
        if ($json !== null) {
            $serveur['CONTENT_TYPE'] = $contentType ?? ($methode === 'PATCH' ? 'application/merge-patch+json' : 'application/json');
        }
        if ($user !== null) {
            $serveur['HTTP_AUTHORIZATION'] = 'Bearer '.$this->service('lexik_jwt_authentication.jwt_manager')->create($user);
        }
        $requete = Request::create($uri, $methode, [], [], $fichiers, $serveur, $json !== null ? json_encode($json, \JSON_THROW_ON_ERROR) : null);
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

    protected function pdf(string $nom = 'rapport.pdf'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tmp, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");

        return new UploadedFile($tmp, $nom, null, null, true);
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

    /** Période de stage d'un département (semestre → année → PN → diplôme → département). */
    protected function periode(StructureDepartement $departement, ?Personnel $responsable = null): StagePeriode
    {
        $type = (new StructureTypeDiplome())->setLibelle('Bachelor Universitaire de Technologie')->setSigle('BUT')->setApc(true);
        $diplome = (new StructureDiplome())->setLibelle($this->unique('BUT'))->setVolumeHoraire(1800)->setDepartement($departement)->setTypeDiplome($type);
        $pn = (new StructurePn($diplome))->setLibelle('PN')->setAnneePublication(2022);
        $annee = (new StructureAnnee())->setLibelle('BUT2')->setOrdre(2)->setActif(true)->setPn($pn);
        $semestre = (new StructureSemestre())->setLibelle('S4')->setOrdreAnnee(2)->setOrdreLmd(4)->setActif(true)
            ->setNbGroupesCm(1)->setNbGroupesTd(1)->setNbGroupesTp(1)->setCloture(false)->setAnnee($annee);
        $au = (new StructureAnneeUniversitaire())->setLibelle('2026/2027')->setAnnee(2026)->setActif(true);
        $periode = (new StagePeriode())->setLibelle($this->unique('Stage BUT2'))->setSemestreProgramme($semestre)->setAnneeUniversitaire($au)
            ->setNbSemaines(10)->setNbJours(50)
            ->setDateDebut(new \DateTime('2027-04-05'))->setDateFin(new \DateTime('2027-06-25'))
            ->setResponsablePrincipal($responsable);
        foreach ([$type, $diplome, $pn, $annee, $semestre, $au, $periode] as $e) {
            $this->em->persist($e);
        }

        return $periode;
    }

    /**
     * @param list<string> $permissions ex. ['ROLE_STAGE_MANAGER'], ['SUPER_ADMIN']
     */
    protected function personnel(?StructureDepartement $departement = null, array $permissions = []): Personnel
    {
        $login = $this->unique('pers');
        $p = (new Personnel())->setUsername($login)->setMailUniv($login.'@exemple.test')->setPrenom('Prénom')->setNom(strtoupper($login));
        $this->em->persist($p);
        if ($departement !== null) {
            $dp = (new StructureDepartementPersonnel())->setPersonnel($p)->setDepartement($departement)
                ->setPermissions($permissions)->setPackages(['intranet', 'stage'])->setDefaut(true)->setAffectation(true);
            $p->addDepartementPersonnel($dp);
            $this->em->persist($dp);
        }

        return $p;
    }

    protected function etudiant(): Etudiant
    {
        $login = $this->unique('etu');
        $e = (new Etudiant())->setUsername($login)->setMailUniv($login.'@exemple.test')->setPrenom('Alice')->setNom(strtoupper($login))
            ->setRoles(['ROLE_ETUDIANT'])->setBoursier(false)->setAnneeSortie(0)->setApplications(['stage']);
        $this->em->persist($e);

        return $e;
    }

    protected function dossier(StagePeriode $periode, Etudiant $etudiant, EtatStageEnum $etat = EtatStageEnum::AUTORISE, ?Personnel $tuteur = null): StageEtudiant
    {
        $s = (new StageEtudiant())->setStagePeriode($periode)->setEtudiant($etudiant)->setEtatStage($etat)->setTuteurUniversitaire($tuteur)
            ->setDateDebutStage(new \DateTime('2027-04-05'))->setDateFinStage(new \DateTime('2027-05-28'))->setDureeHebdomadaire(35.0);
        $this->em->persist($s);

        return $s;
    }

    /** Enregistre les données préparées (dans la transaction du test). */
    protected function enregistrer(): void
    {
        $this->em->flush();
    }
}
