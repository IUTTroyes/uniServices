<?php

namespace App\Command\CopyBdd;

use App\Entity\Edt\EdtEvent;
use App\Repository\PersonnelRepository;
use App\Repository\ScolEnseignementRepository;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use App\Repository\Structure\StructureGroupeRepository;
use App\Repository\Structure\StructureSemestreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Uid\UuidV4;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'copy:transfert-bdd:edt',
    description: 'Add a short description for your command',
)]
class CopyTransfertBddEdtCommand extends Command
{
    protected object $em;

    protected array $tMatieres = [];
    protected array $tPersonnels = [];
    protected array $tSemestres = [];
    protected array $tAnneesUniversitaires = [];
    protected array $tGroupes = [];
    protected string $base_url;


    protected SymfonyStyle $io;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        ManagerRegistry                  $managerRegistry,
        PersonnelRepository              $personnelRepository,
        StructureSemestreRepository      $structureSemestreRepository,
        StructureAnneeUniversitaireRepository $structureAnneeUniversitaireRepository,
        StructureGroupeRepository $structureGroupeRepository,
        ScolEnseignementRepository       $scolEnseignementRepository,
        protected HttpClientInterface    $httpClient,
        ParameterBagInterface            $params
    )
    {
        parent::__construct();
        $this->em = $managerRegistry->getConnection('copy');
        $this->tPersonnels = $personnelRepository->findAllByOldIdArray();
        $this->tSemestres = $structureSemestreRepository->findAllByOldIdArray();
        $this->tAnneesUniversitaires = $structureAnneeUniversitaireRepository->findAllByOldIdArray();
        $this->tGroupes = $structureGroupeRepository->findAllByOldIdArray();
        $matieres = $scolEnseignementRepository->findAll();

        foreach ($matieres as $matiere) {
            $this->tMatieres[$matiere->getType()->value . '_' . $matiere->getOldId()] = $matiere;
        }

        $this->base_url = $params->get('URL_INTRANET_V3');
        $this->httpClient = HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
        ]);

    }

    protected function configure(): void
    {
    }

    private function effacerTables(): void
    {
        // vider les tables de destination et les réinitialiser
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=0');
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE edt_event');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=1');
    }

    protected
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->effacerTables();
        $this->addEdtEventIntranet();
        // $this->addEdtEventCelcat();

        $this->io->success('Processus de recopie terminé.');

        return Command::SUCCESS;
    }

    private function addEdtEventIntranet(): void
    {
        $reponses = $this->httpClient->request('GET', $this->base_url . '/edt-intranet');
        $edts = $reponses->toArray();
        foreach ($edts as $ed) {
//            dd($ed);
            if (array_key_exists($ed['prof'], $this->tPersonnels) && array_key_exists($ed['matiere'], $this->tMatieres)) {
                $edt = new EdtEvent();
                $edt->setUuid(UuidV4::v4());
                $edt->setDebut(new \DateTime($ed['debut']));
                $edt->setFin(new \DateTime($ed['fin']));
                $edt->setSalle($ed['salle']);
                $edt->setPersonnel($this->tPersonnels[$ed['prof']]);
                $edt->setLibPersonnel($ed['libprof']);
                $edt->setCodePersonnel($ed['codeRh']);
                $edt->setGroupe($this->tGroupes[$ed['groupe']] ?? null);
                $edt->setType($ed['type']);
                $edt->setCouleur($ed['couleur']);
                $edt->setEvaluation($ed['evaluation']);
                //$edt->setCodeGroupe($this->tGroupes[$ed['groupe']]->getCodeApogee());
                $edt->setCodeModule($this->tMatieres[$ed['matiere']]->getCodeApogee());
                $edt->setEnseignement($this->tMatieres[$ed['matiere']]);
                $edt->setJour($ed['jour']);
                // $edt->setLibGroupe($this->tGroupes[$ed['groupe']]->getLibelle());
                $edt->setLibModule($this->tMatieres[$ed['matiere']]->getLibelle());
                $edt->setSemestre($this->tSemestres[$ed['semestre']]);
                $edt->setSemaineFormation($ed['semaine']);
                $edt->setAnneeUniversitaire($this->tAnneesUniversitaires[$ed['anneeUniversitaire']]);

                $this->entityManager->persist($edt);
            }
        }
        $this->entityManager->flush();
    }
}
