<?php

namespace UnifolioBundle\Tests\Entity;

use App\Entity\Users\Etudiant;
use App\Entity\Users\Personnel;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Entity\Commentaire;
use UnifolioBundle\Entity\Trace;

class CommentaireTest extends TestCase
{
    public function testAuteurEtContenu(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $c = Commentaire::parEtudiant($trace, new Etudiant(), "  Merci pour le retour  ");

        $this->assertTrue($c->isDeEtudiant());
        $this->assertSame('Merci pour le retour', $c->getContenu());
        $this->assertNull($c->getAuteurPersonnel());
        $this->assertFalse(Commentaire::parPersonnel($trace, $this->createMock(Personnel::class), 'Bonjour')->isDeEtudiant());
    }

    public function testCommentaireVideOuTropLong(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        foreach (['   ', str_repeat('a', Commentaire::LONGUEUR_MAX + 1)] as $contenu) {
            try {
                Commentaire::parEtudiant($trace, new Etudiant(), $contenu);
                $this->fail('Contenu invalide accepté');
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testFilAUnSeulNiveau(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $racine = Commentaire::parPersonnel($trace, $this->createMock(Personnel::class), 'Question');
        $reponse = Commentaire::parEtudiant($trace, new Etudiant(), 'Réponse', null, $racine);
        $suite = Commentaire::parPersonnel($trace, $this->createMock(Personnel::class), 'Suite', null, $reponse);

        $this->assertSame($racine, $reponse->getParent());
        $this->assertSame($racine, $suite->getParent(), 'Une réponse à une réponse reste sous le commentaire racine');
    }

    public function testParentDUneAutreTraceRefuse(): void
    {
        $racine = Commentaire::parEtudiant(new Trace(new Etudiant(), 'A'), new Etudiant(), 'Hello');

        $this->expectException(\InvalidArgumentException::class);
        Commentaire::parEtudiant(new Trace(new Etudiant(), 'B'), new Etudiant(), 'Réponse', null, $racine);
    }
}
