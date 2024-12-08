<?php

namespace App\Command\CopyBdd;

use App\Entity\Etudiant\EtudiantScolarite;
use App\Entity\Scolarite\ScolEdtEvent;
use App\Entity\Scolarite\ScolEnseignement;
use App\Entity\Scolarite\ScolEnseignementUe;
use App\Entity\Structure\StructureAnnee;
use App\Entity\Structure\StructureAnneeUniversitaire;
use App\Entity\Structure\StructureDepartement;
use App\Entity\Structure\StructureDepartementPersonnel;
use App\Entity\Structure\StructureDiplome;
use App\Entity\Structure\StructureSemestre;
use App\Entity\Structure\StructureTypeDiplome;
use App\Entity\Structure\StructureUe;
use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use App\Enum\TypeEnseignementEnum;
use App\Repository\EtudiantRepository;
use App\Repository\PersonnelRepository;
use App\Repository\ScolEnseignementRepository;
use App\Repository\Structure\StructureAnneeUniversitaireRepository;
use App\Repository\Structure\StructureDepartementRepository;
use App\Repository\Structure\StructureSemestreRepository;
use App\ValueObject\Adresse;
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
    protected string $base_url;


    protected SymfonyStyle $io;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        ManagerRegistry                  $managerRegistry,
        PersonnelRepository              $personnelRepository,
        StructureSemestreRepository              $structureSemestreRepository,
        ScolEnseignementRepository       $scolEnseignementRepository,
        protected HttpClientInterface    $httpClient,
        ParameterBagInterface            $params
    )
    {
        parent::__construct();
        $this->em = $managerRegistry->getConnection('copy');
        $this->tPersonnels = $personnelRepository->findAllByOldIdArray();
        $this->tSemestres = $structureSemestreRepository->findAllByOldIdArray();
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
        $this->entityManager->getConnection()->executeQuery('TRUNCATE TABLE scol_edt_event');
        $this->entityManager->getConnection()->executeQuery('SET
FOREIGN_KEY_CHECKS=1');
    }

    protected
    function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->effacerTables();
        $this->addEdtEventIntranet();
        $this->addEdtEventCelcat();

        $this->io->success('Processus de recopie terminé.');

        return Command::SUCCESS;
    }

    private function addEdtEventIntranet(): void
    {
        $reponses = $this->httpClient->request('GET', $this->base_url . '/edt-intranet');
        $edts = $reponses->toArray();

        foreach ($edts as $ed) {
            $edt = new ScolEdtEvent();
            $edt->setUuid(UuidV4::v4());
            $edt->setDebut(new \DateTime($ed['debut']));
            $edt->setFin(new \DateTime($ed['fin']));
            $edt->setSalle($ed['salle']);
            $edt->setPersonnel($this->tPersonnels[$ed['prof']]);
            $edt->setLibPersonnel($ed['libprof']);
            $edt->setCodePersonnel($ed['codeRh']);
           // $edt->setGroupe($this->tGroupes[$ed['groupe']]);
            $edt->setType($ed['type']);
            $edt->setCouleur($ed['couleur']);
            $edt->setEvaluation($ed['evaluation']);
            //$edt->setCodeGroupe($this->tGroupes[$ed['groupe']]->getCodeApogee());
            $edt->setCodeModule($this->tMatieres[$ed['matiere']]->getCodeApogee());
            $edt->setJour($ed['jour']);
           // $edt->setLibGroupe($this->tGroupes[$ed['groupe']]->getLibelle());
            $edt->setLibModule($this->tMatieres[$ed['matiere']]->getLibelle());
            $edt->setSemestre($this->tSemestres[$ed['semestre']]);




        }
    }
}
