<?php

namespace App\Scheduler;

use App\Repository\Scheduler\SchedulerTaskRepository;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;
use Symfony\Component\Console\Messenger\RunCommandMessage;

#[AsSchedule('default')]
class AppScheduleProvider implements ScheduleProviderInterface
{
    public function __construct(
        private readonly SchedulerTaskRepository $taskRepository
    ) {}

    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();

        try {
            $activeTasks = $this->taskRepository->findBy(['active' => true]);
            foreach ($activeTasks as $task) {
                $trigger = CronExpressionTrigger::fromSpec($task->getCronExpression());
                
                $commandLine = $task->getCommand();
                $args = $task->getArguments() ?? [];
                if (!empty($args)) {
                    $commandLine .= ' ' . implode(' ', $args);
                }
                
                $message = new RunCommandMessage($commandLine);

                $schedule = $schedule->with(
                    RecurringMessage::trigger($trigger, $message)
                );
            }
        } catch (\Exception $e) {
            // Evite de bloquer la console / cache-clear si la table n'est pas encore migree
        }

        return $schedule;
    }
}
