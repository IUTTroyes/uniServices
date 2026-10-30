<?php

namespace MessageBundle\Tests\Entity;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use MessageBundle\Entity\Message;
use MessageBundle\Entity\MessageDestinataire;
use PHPUnit\Framework\TestCase;

class MessageDestinataireTest extends TestCase
{
    public function testInitialState(): void
    {
        $dest = new MessageDestinataire();

        $this->assertNull($dest->getId());
        $this->assertNull($dest->getMessage());
        $this->assertNull($dest->getEtudiant());
        $this->assertNull($dest->getPersonnel());
        $this->assertFalse($dest->isLu());
        $this->assertNull($dest->getDateLecture());
        $this->assertFalse($dest->isCopie());
        $this->assertFalse($dest->isArchive());
    }

    public function testSettersAndGetters(): void
    {
        $dest = new MessageDestinataire();
        $message = new Message();
        $etudiant = new Etudiant();
        $personnel = new Personnel();
        $readDate = new \DateTimeImmutable('2026-10-30 15:30:00');

        $dest->setMessage($message);
        $dest->setEtudiant($etudiant);
        $dest->setPersonnel($personnel);
        $dest->setIsCopie(true);
        $dest->setIsArchive(true);
        $dest->setIsLu(true);
        $dest->setDateLecture($readDate);

        $this->assertSame($message, $dest->getMessage());
        $this->assertSame($etudiant, $dest->getEtudiant());
        $this->assertSame($personnel, $dest->getPersonnel());
        $this->assertTrue($dest->isCopie());
        $this->assertTrue($dest->isArchive());
        $this->assertTrue($dest->isLu());
        $this->assertSame($readDate, $dest->getDateLecture());
    }

    public function testSetIsLuSetsDateLectureAutomatically(): void
    {
        $dest = new MessageDestinataire();
        $this->assertNull($dest->getDateLecture());

        $dest->setIsLu(true);
        $this->assertTrue($dest->isLu());
        $this->assertInstanceOf(\DateTimeImmutable::class, $dest->getDateLecture());

        $dest->setIsLu(false);
        $this->assertFalse($dest->isLu());
        $this->assertNull($dest->getDateLecture());
    }
}

