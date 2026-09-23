<?php

namespace App\Entity\Contracts;

interface TimestampableInterface
{
    public function getCreatedAt(): ?\DateTimeImmutable;

    public function getUpdatedAt(): ?\DateTimeImmutable;

    public function setCreatedAt(\DateTimeImmutable $createdAt): void;

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void;
}
