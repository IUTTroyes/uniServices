<?php

namespace MessageBundle\Tests\Entity;

use MessageBundle\Entity\Message;
use MessageBundle\Entity\MessagePieceJointe;
use PHPUnit\Framework\TestCase;

class MessagePieceJointeTest extends TestCase
{
    public function testInitialState(): void
    {
        $pj = new MessagePieceJointe();

        $this->assertNull($pj->getId());
        $this->assertNull($pj->getMessage());
        $this->assertNull($pj->getNomOriginal());
        $this->assertNull($pj->getFichier());
        $this->assertSame(0, $pj->getTaille());
        $this->assertNull($pj->getMimeType());
        $this->assertInstanceOf(\DateTimeImmutable::class, $pj->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $pj = new MessagePieceJointe();
        $message = new Message();
        $createdAt = new \DateTimeImmutable('2026-10-30 14:00:00');

        $pj->setMessage($message);
        $pj->setNomOriginal('Consignes_TP1.pdf');
        $pj->setFichier('pj_663a8bc9_a1b2c3d4.pdf');
        $pj->setTaille(2048576);
        $pj->setMimeType('application/pdf');
        $pj->setCreatedAt($createdAt);

        $this->assertSame($message, $pj->getMessage());
        $this->assertSame('Consignes_TP1.pdf', $pj->getNomOriginal());
        $this->assertSame('pj_663a8bc9_a1b2c3d4.pdf', $pj->getFichier());
        $this->assertSame(2048576, $pj->getTaille());
        $this->assertSame('application/pdf', $pj->getMimeType());
        $this->assertSame($createdAt, $pj->getCreatedAt());
    }
}

