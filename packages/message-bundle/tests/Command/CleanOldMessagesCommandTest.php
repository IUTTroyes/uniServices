<?php

namespace MessageBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use MessageBundle\Command\CleanOldMessagesCommand;
use MessageBundle\Entity\Message;
use MessageBundle\Entity\MessageDestinataire;
use MessageBundle\Entity\MessagePieceJointe;
use MessageBundle\Repository\MessageRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class CleanOldMessagesCommandTest extends TestCase
{
    private $entityManager;
    private $messageRepository;
    private $kernel;
    private CleanOldMessagesCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageRepository = $this->createMock(MessageRepository::class);
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('getProjectDir')->willReturn(sys_get_temp_dir());

        $this->command = new CleanOldMessagesCommand(
            $this->entityManager,
            $this->messageRepository,
            $this->kernel
        );

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWhenNoMessagesToClean(): void
    {
        $this->messageRepository
            ->expects($this->once())
            ->method('countMessagesOlderThan')
            ->willReturn(0);

        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Aucun message antérieur à la date limite', $output);
    }

    public function testExecuteDryRunDoesNotDelete(): void
    {
        $msg = new Message();
        $msg->setSujet('Test vieux message');

        $dest = new MessageDestinataire();
        $msg->addDestinataire($dest);

        $pj = new MessagePieceJointe();
        $pj->setFichier('test.pdf');
        $pj->setTaille(2048);
        $msg->addPieceJointe($pj);

        $this->messageRepository
            ->expects($this->once())
            ->method('countMessagesOlderThan')
            ->willReturn(1);

        $this->messageRepository
            ->expects($this->once())
            ->method('findMessagesOlderThan')
            ->willReturn([$msg]);

        $this->entityManager->expects($this->never())->method('remove');
        $this->entityManager->expects($this->never())->method('flush');

        $exitCode = $this->commandTester->execute(['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('[SIMULATION]', $output);
        $this->assertStringContainsString('Mode [DRY-RUN] actif', $output);
        $this->assertStringContainsString('2.00 Ko', $output);
    }

    public function testExecuteWithDaysOptionAndDeletesSuccessfully(): void
    {
        $msg = new Message();
        $msg->setSujet('Message à supprimer');

        $this->messageRepository
            ->expects($this->once())
            ->method('countMessagesOlderThan')
            ->willReturn(1);

        // First loop returns message, second returns empty array to terminate loop
        $this->messageRepository
            ->expects($this->exactly(2))
            ->method('findMessagesOlderThan')
            ->willReturnOnConsecutiveCalls([$msg], []);

        $this->entityManager->expects($this->once())->method('remove')->with($msg);
        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('clear');

        $exitCode = $this->commandTester->execute(['--days' => '30']);

        $this->assertSame(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Nettoyage terminé avec succès', $output);
        $this->assertStringContainsString('1 message(s) supprimé(s)', $output);
    }
}

