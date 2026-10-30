<?php

namespace MessageBundle\Tests\Entity;

use App\Entity\Users\Personnel;
use MessageBundle\Entity\Message;
use MessageBundle\Entity\MessageDestinataire;
use MessageBundle\Entity\MessagePieceJointe;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testInitialState(): void
    {
        $message = new Message();

        $this->assertNull($message->getId());
        $this->assertNull($message->getSujet());
        $this->assertNull($message->getContenu());
        $this->assertNull($message->getExpediteur());
        $this->assertSame('INDIVIDUEL', $message->getTypeCible());
        $this->assertNull($message->getCibleLibelle());
        $this->assertFalse($message->isPrioritaire());
        $this->assertFalse($message->isCopieEmail());
        $this->assertCount(0, $message->getDestinataires());
        $this->assertCount(0, $message->getPiecesJointes());
    }

    public function testSettersAndGetters(): void
    {
        $message = new Message();
        $personnel = new Personnel();

        $message->setSujet('Information importante');
        $message->setContenu('Ceci est une annonce officielle.');
        $message->setExpediteur($personnel);
        $message->setTypeCible('SEMESTRES');
        $message->setCibleLibelle('Semestre 1, Semestre 2');
        $message->setPrioritaire(true);
        $message->setCopieEmail(true);

        $this->assertSame('Information importante', $message->getSujet());
        $this->assertSame('Ceci est une annonce officielle.', $message->getContenu());
        $this->assertSame($personnel, $message->getExpediteur());
        $this->assertSame('SEMESTRES', $message->getTypeCible());
        $this->assertSame('Semestre 1, Semestre 2', $message->getCibleLibelle());
        $this->assertTrue($message->isPrioritaire());
        $this->assertTrue($message->isCopieEmail());
    }

    public function testDestinatairesRelation(): void
    {
        $message = new Message();
        $dest1 = new MessageDestinataire();
        $dest2 = new MessageDestinataire();

        $message->addDestinataire($dest1);
        $message->addDestinataire($dest2);

        $this->assertCount(2, $message->getDestinataires());
        $this->assertSame($message, $dest1->getMessage());
        $this->assertSame($message, $dest2->getMessage());

        // Éviter les doublons
        $message->addDestinataire($dest1);
        $this->assertCount(2, $message->getDestinataires());

        $message->removeDestinataire($dest1);
        $this->assertCount(1, $message->getDestinataires());
        $this->assertNull($dest1->getMessage());
    }

    public function testPiecesJointesRelation(): void
    {
        $message = new Message();
        $pj1 = new MessagePieceJointe();
        $pj2 = new MessagePieceJointe();

        $message->addPieceJointe($pj1);
        $message->addPieceJointe($pj2);

        $this->assertCount(2, $message->getPiecesJointes());
        $this->assertSame($message, $pj1->getMessage());
        $this->assertSame($message, $pj2->getMessage());

        // Éviter les doublons
        $message->addPieceJointe($pj1);
        $this->assertCount(2, $message->getPiecesJointes());

        $message->removePieceJointe($pj1);
        $this->assertCount(1, $message->getPiecesJointes());
        $this->assertNull($pj1->getMessage());
    }
}

