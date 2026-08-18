<?php

namespace App\Controller\Scheduler;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AvailableCommandsController extends AbstractController
{
    #[Route('/api/scheduler/available-commands', name: 'api_scheduler_available_commands', methods: ['GET'])]
    #[IsGranted('SUPER_ADMIN')]
    public function getAvailableCommands(KernelInterface $kernel): JsonResponse
    {
        $application = new Application($kernel);
        // Desactive auto-exit pour eviter d'arreter l'execution PHP
        $application->setAutoExit(false);

        $commands = $application->all();
        $list = [];

        foreach ($commands as $command) {
            $name = $command->getName();
            if ($name && (str_starts_with($name, 'app:') || str_starts_with($name, 'database:'))) {
                // Eviter de lister des commandes de dev internes si on veut restreindre, mais app:* est parfait
                $list[] = [
                    'name' => $name,
                    'description' => $command->getDescription() ?? '',
                ];
            }
        }

        // Tri alphabetique par nom de commande
        usort($list, static fn($a, $b) => strcmp($a['name'], $b['name']));

        return new JsonResponse($list);
    }
}
