<?php

namespace App\Tests\Referentiel;

use App\Entity\Apc\ApcReferentiel;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureTypeDiplome;
use App\Kernel;
use App\Repository\EtablissementRepository;
use App\Service\Orebut\ImportReferentiel;
use App\Service\Orebut\OrebutClient;
use App\Service\Orebut\OrebutException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Import d'un référentiel ORéBUT (réponses fictives) sur la base « <base>_test », dans une transaction annulée.
 * Sans base de test, ces tests sont ignorés.
 */
class ImportReferentielTest extends TestCase
{
    private ?Kernel $noyau = null;
    private EntityManagerInterface $em;
    private int $annee = 2021;
    private string $libelleAc = 'Identifier le besoin';
    private bool $homonymes = false;

    protected function setUp(): void
    {
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
        (new Dotenv())->bootEnv(__DIR__.'/../../back/.env');
        try {
            $this->noyau = new Kernel('test', true);
            $this->noyau->boot();
            $this->em = $this->noyau->getContainer()->get('doctrine')->getManager();
            $this->em->getConnection()->executeQuery('SELECT 1 FROM apc_referentiel LIMIT 1');
        } catch (\Throwable $e) {
            self::markTestSkipped('Base de test indisponible : '.$e->getMessage());
        }
        $this->em->getConnection()->setNestTransactionsWithSavepoints(true);
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->noyau !== null) {
            if (isset($this->em) && $this->em->getConnection()->isTransactionActive()) {
                $this->em->getConnection()->rollBack();
            }
            $this->noyau->shutdown();
            restore_exception_handler();
        }
    }

    public function testImporteCompetencesNiveauxEtParcoursPuisNeChangePlusRien(): void
    {
        $id = $this->diplome()->getId();

        $premier = $this->import()->synchroniser($this->trouver($id), 'EX', 'PA');
        self::assertTrue($premier['referentiel']['nouveau']);
        self::assertSame(2021, $premier['referentiel']['annee']);
        self::assertSame(1, $premier['compteurs']['competences']['ajouts']);
        self::assertSame(2, $premier['compteurs']['niveaux']['ajouts']);
        self::assertSame(2, $premier['compteurs']['parcours']['ajouts']);
        self::assertSame([], $premier['alertes']);

        $this->em->clear();
        $diplome = $this->trouver($id);
        self::assertSame('PA', $diplome->getParcours()?->getSigle());
        $niveaux = $diplome->getReferentiel()->getCompetences()->first()->getNiveaux()->toArray();
        usort($niveaux, static fn ($a, $b) => $a->getOrdre() <=> $b->getOrdre());
        self::assertSame([1, 2], array_map(static fn ($n) => $n->getOrdreAnnee(), $niveaux));
        // Premier niveau commun aux deux parcours, second propre à PA.
        self::assertCount(2, $niveaux[0]->getParcours());
        self::assertSame(['PA'], array_values(array_map(static fn ($p) => $p->getSigle(), $niveaux[1]->getParcours()->toArray())));

        $this->em->clear();
        $second = $this->import()->synchroniser($this->trouver($id), 'EX', 'PA');
        self::assertFalse($second['referentiel']['nouveau']);
        foreach ($second['compteurs'] as $famille => $compteur) {
            self::assertSame([0, 0], [$compteur['ajouts'], $compteur['modifications']], $famille);
        }
    }

    public function testLaSimulationNEcritRien(): void
    {
        $id = $this->diplome()->getId();
        $avant = $this->em->getRepository(ApcReferentiel::class)->count([]);

        $rapport = $this->import()->synchroniser($this->trouver($id), 'EX', null, true);

        self::assertTrue($rapport['simulation']);
        self::assertSame(1, $rapport['compteurs']['competences']['ajouts']);
        self::assertSame($avant, $this->em->getRepository(ApcReferentiel::class)->count([]));
        self::assertNull($this->trouver($id)->getReferentiel());
    }

    public function testUneModificationMetAJourSansRecreerEtUnNouveauProgrammeConserveLAncien(): void
    {
        $id = $this->diplome()->getId();
        $this->import()->synchroniser($this->trouver($id), 'EX');
        $this->em->clear();
        $ancien = $this->trouver($id)->getReferentiel()->getId();

        $this->libelleAc = 'Identifier précisément le besoin';
        $rapport = $this->import()->synchroniser($this->trouver($id), 'EX');
        self::assertSame(1, $rapport['compteurs']['apprentissagesCritiques']['modifications']);
        self::assertSame(0, $rapport['compteurs']['apprentissagesCritiques']['ajouts']);

        $this->em->clear();
        $this->annee = 2027;
        $nouveau = $this->import()->synchroniser($this->trouver($id), 'EX');
        self::assertTrue($nouveau['referentiel']['nouveau']);
        $this->em->clear();
        self::assertNotSame($ancien, $this->trouver($id)->getReferentiel()->getId());
        self::assertNotNull($this->em->find(ApcReferentiel::class, $ancien));
    }

    /** Cas réels d'ORéBUT : noms courts et codes d'apprentissage critique en double. */
    public function testLesHomonymesRestentDistincts(): void
    {
        $this->homonymes = true;
        $id = $this->diplome()->getId();
        $premier = $this->import()->synchroniser($this->trouver($id), 'EX');
        self::assertSame(2, $premier['compteurs']['competences']['ajouts']);

        $this->em->clear();
        $second = $this->import()->synchroniser($this->trouver($id), 'EX');
        foreach ($second['compteurs'] as $famille => $compteur) {
            self::assertSame([0, 0], [$compteur['ajouts'], $compteur['modifications']], $famille);
        }
        self::assertSame([], $second['absents']);
    }

    public function testUneSpecialiteSansCompetenceEstRefusee(): void
    {
        $this->expectException(OrebutException::class);
        $this->import()->synchroniser($this->diplome(), 'VIDE');
    }

    private function trouver(int $id): StructureDiplome
    {
        return $this->em->find(StructureDiplome::class, $id);
    }

    private function diplome(): StructureDiplome
    {
        $departement = (new StructureDepartement())->setLibelle('Dept test '.bin2hex(random_bytes(4)))->setActif(true);
        $type = (new StructureTypeDiplome())->setLibelle('BUT test')->setSigle('BUT')->setApc(true);
        $diplome = (new StructureDiplome())->setLibelle('BUT EX')->setVolumeHoraire(1800)->setDepartement($departement)->setTypeDiplome($type);
        foreach ([$departement, $type, $diplome] as $entite) {
            $this->em->persist($entite);
        }
        $this->em->flush();

        return $diplome;
    }

    private function import(): ImportReferentiel
    {
        $http = new MockHttpClient(function (string $methode, string $url): MockResponse {
            $membres = match (true) {
                str_ends_with($url, '/api/specialite/EX/competences') => $this->competences(),
                str_ends_with($url, '/api/specialite/VIDE/competences') => [],
                str_ends_with($url, '/api/departements') => [$this->specialite()],
                default => throw new \LogicException('Adresse inattendue : '.$url),
            };

            return new MockResponse(json_encode(['hydra:member' => $membres]));
        });
        $etablissements = $this->createStub(EtablissementRepository::class);

        return new ImportReferentiel(new OrebutClient($http, $etablissements), $this->em);
    }

    private function competences(): array
    {
        if (!$this->homonymes) {
            return [$this->competence()];
        }
        $a = $this->competence();
        // Même code deux fois dans un niveau, avec des libellés différents.
        $a['apcNiveaux'][0]['apcApprentissageCritiques'][] = ['code' => 'AC11.01', 'libelle' => 'Autre apprentissage'];
        $b = $this->competence();
        $b['libelle'] = 'Concevoir un autre dispositif';

        return [$a, $b];
    }

    private function competence(): array
    {
        $niveau = fn (int $ordre, string $code, string $libelle): array => [
            'id' => 100 + $ordre, 'libelle' => 'Niveau '.$ordre, 'ordre' => $ordre, 'annee' => ['libelle' => 'BUT'.$ordre],
            'apcApprentissageCritiques' => [['code' => $code, 'libelle' => $libelle]],
        ];

        return [
            'nom_court' => 'Concevoir', 'libelle' => 'Concevoir une solution', 'couleur' => 'c1',
            'version' => ['annee' => $this->annee],
            'apcComposanteEssentielles' => [['code' => 'CE1.01', 'libelle' => 'En analysant', 'ordre' => 1]],
            'apcSituationProfessionnelles' => [['libelle' => 'Répondre à une commande']],
            'apcNiveaux' => [$niveau(1, 'AC11.01', $this->libelleAc), $niveau(2, 'AC21.01', 'Maquetter')],
        ];
    }

    private function specialite(): array
    {
        $parcours = static fn (string $code, array $niveaux): array => [
            'code' => $code, 'libelle' => 'Parcours '.$code, 'couleur' => 'c2',
            'apcParcoursNiveaux' => array_map(static fn (int $id): array => ['niveau' => ['id' => $id]], $niveaux),
        ];

        return ['sigle' => 'EX', 'libelle' => 'BUT Exemple', 'apcParcours' => [$parcours('PA', [101, 102]), $parcours('PB', [101])]];
    }
}
