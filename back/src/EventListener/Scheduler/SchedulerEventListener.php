<?php

namespace App\EventListener\Scheduler;

use App\Entity\Scheduler\SchedulerTask;
use App\Repository\Scheduler\SchedulerTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Scheduler\Event\FailureEvent;
use Symfony\Component\Scheduler\Event\PostRunEvent;

class SchedulerEventListener
{
    public function __construct(
        private readonly EntityManagerInterface   $em,
        private readonly SchedulerTaskRepository $taskRepository
    ) {}

    #[AsEventListener(event: PostRunEvent::class)]
    public function onPostRun(PostRunEvent $event): void
    {
        $this->updateTaskExecution($event->getMessage(), $event->getMessageContext());
    }

    #[AsEventListener(event: FailureEvent::class)]
    public function onFailure(FailureEvent $event): void
    {
        $this->updateTaskExecution($event->getMessage(), $event->getMessageContext());
    }

    private function updateTaskExecution(object $message, $context): void
    {
        if (!$message instanceof RunCommandMessage) {
            return;
        }

        $input = $message->input;
        $parts = explode(' ', $input, 2);
        $commandName = $parts[0];

        // Trouver la tache correspondante en BDD
        $task = $this->taskRepository->findOneBy([
            'command' => $commandName,
            'active' => true,
        ]);

        if (null !== $task) {
            $task->setLastRun($context->triggeredAt);
            $task->setNextRun($context->nextTriggerAt);
            $this->em->flush();
        }
    }
}
