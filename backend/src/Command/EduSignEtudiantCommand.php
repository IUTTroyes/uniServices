<?php

/*
 * Copyright (c) 2024. | David Annebicque | IUT de Troyes  - All Rights Reserved
 * @file /Users/davidannebicque/Sites/intranetV3/src/Command/EduSignEtudiantCommand.php
 * @author davidannebicque
 * @project intranetV3
 * @lastUpdate 24/02/2024 08:41
 */

namespace App\Command;

use App\Classes\EduSign\UpdateEtudiant;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'edusign:update-etudiant',
    description: 'Mise à jour des etudiants',
)]
class EduSignEtudiantCommand extends Command
{
    public function __construct(
        private readonly UpdateEtudiant $updateEtudiant,
    )
    {
        parent::__construct();
    }


    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->updateEtudiant->update();//boucler sur département pour chaque update (ou diplome)

        $io->success('Etudiants mis à jour sur EduSign.');

        return Command::SUCCESS;
    }
}
