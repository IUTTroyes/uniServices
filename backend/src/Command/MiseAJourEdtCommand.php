<?php
/*
 * Copyright (c) 2022. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Command/MiseAJourEdtCommand.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 10/08/2022 17:46
 */

namespace App\Command;

use App\Repository\AnneeUniversitaireRepository;
use App\Repository\EdtPlanningRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:mise-a-jour-edt',
    description: 'Commande epour la mise à jour de la structure de l\'edt',
)]
class MiseAJourEdtCommand extends Command
{
    public function __construct(
        protected AnneeUniversitaireRepository $anneeUniversitaireRepository,
        protected EdtPlanningRepository $edtPlanningRepository,
        protected EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $annee = $this->anneeUniversitaireRepository->find(4);

        $edts = $this->edtPlanningRepository->findAll();
        foreach ($edts as $edt) {
            if (null !== $edt->getSemestre()) {
                $edt->setOrdreSemestre($edt->getSemestre()->getOrdreLmd());
            }
            $edt->setAnneeUniversitaire($annee);
            $edt->setCreated(Carbon::now());
            $edt->setUpdated(Carbon::now());
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
