<?php

namespace App\Entity\Scheduler;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\Scheduler\SchedulerTaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SchedulerTaskRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['scheduler_task:read']],
            security: "is_granted('ROLE_SUPER_ADMIN')"
        ),
        new Get(
            normalizationContext: ['groups' => ['scheduler_task:read']],
            security: "is_granted('ROLE_SUPER_ADMIN')"
        ),
        new Post(
            denormalizationContext: ['groups' => ['scheduler_task:write']],
            normalizationContext: ['groups' => ['scheduler_task:read']],
            security: "is_granted('ROLE_SUPER_ADMIN')"
        ),
        new Patch(
            denormalizationContext: ['groups' => ['scheduler_task:write']],
            normalizationContext: ['groups' => ['scheduler_task:read']],
            security: "is_granted('ROLE_SUPER_ADMIN')"
        ),
        new Delete(
            security: "is_granted('ROLE_SUPER_ADMIN')"
        ),
    ],
)]
class SchedulerTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['scheduler_task:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['scheduler_task:read', 'scheduler_task:write'])]
    private string $name;

    #[ORM\Column(length: 255)]
    #[Groups(['scheduler_task:read', 'scheduler_task:write'])]
    private string $command;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['scheduler_task:read', 'scheduler_task:write'])]
    private ?array $arguments = [];

    #[ORM\Column(length: 255)]
    #[Groups(['scheduler_task:read', 'scheduler_task:write'])]
    private string $cronExpression = '0 0 * * *';

    #[ORM\Column]
    #[Groups(['scheduler_task:read', 'scheduler_task:write'])]
    private bool $active = true;

    #[ORM\Column(length: 1000, nullable: true)]
    #[Groups(['scheduler_task:read', 'scheduler_task:write'])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['scheduler_task:read'])]
    private ?\DateTimeImmutable $lastRun = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['scheduler_task:read'])]
    private ?\DateTimeImmutable $nextRun = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): self
    {
        $this->command = $command;
        return $this;
    }

    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    public function setArguments(?array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    public function getCronExpression(): string
    {
        return $this->cronExpression;
    }

    public function setCronExpression(string $cronExpression): self
    {
        $this->cronExpression = $cronExpression;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getLastRun(): ?\DateTimeImmutable
    {
        return $this->lastRun;
    }

    public function setLastRun(?\DateTimeImmutable $lastRun): self
    {
        $this->lastRun = $lastRun;
        return $this;
    }

    public function getNextRun(): ?\DateTimeImmutable
    {
        return $this->nextRun;
    }

    public function setNextRun(?\DateTimeImmutable $nextRun): self
    {
        $this->nextRun = $nextRun;
        return $this;
    }
}
