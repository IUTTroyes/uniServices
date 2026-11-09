<?php

namespace StageBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use StageBundle\Entity\Stages\StageContact;
use StageBundle\Entity\Stages\StageEntreprise;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\Service\StageEntrepriseResolver;

class StageEntrepriseResolverTest extends TestCase
{
    private function avecId(object $entite, int $id): object
    {
        (new \ReflectionProperty($entite, 'id'))->setValue($entite, $id);

        return $entite;
    }

    private function resolver(?StageEntreprise $parSiret): StageEntrepriseResolver
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn($parSiret);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        return new StageEntrepriseResolver($em);
    }

    public function testLeSiretEstNormalise(): void
    {
        self::assertSame('12345678900012', StageEntreprise::normaliserSiret(' 123 456 789 00012 '));
        self::assertNull(StageEntreprise::normaliserSiret(' - '));
    }

    public function testUneEntrepriseConnueParSonSiretEstReutilisee(): void
    {
        $signataire = $this->avecId((new StageContact())->setNom('Ancien'), 7);
        $existante = $this->avecId((new StageEntreprise())->setRaisonSociale('ACME')->setSiret('12345678900012')->setResponsable($signataire), 3);

        $saisie = (new StageEntreprise())->setRaisonSociale('ACME SAS')->setSiret('123 456 789 00012')
            ->setResponsable((new StageContact())->setNom('Nouveau'));
        $stage = (new StageEtudiant())->setEntreprise($saisie);

        $this->resolver($existante)->resoudre($stage, null, null);

        self::assertSame($existante, $stage->getEntreprise(), 'pas de nouvelle entreprise');
        self::assertSame('ACME SAS', $existante->getRaisonSociale());
        self::assertSame($signataire, $existante->getResponsable(), 'le signataire est mis à jour en place');
        self::assertSame('Nouveau', $signataire->getNom());
    }

    public function testLeTuteurEntrepriseEstMisAJourEnPlace(): void
    {
        $ancien = $this->avecId((new StageContact())->setNom('Martin'), 9);
        $stage = (new StageEtudiant())->setTuteur((new StageContact())->setNom('Durand')->setEmail('d@acme.fr'));

        $this->resolver(null)->resoudre($stage, null, $ancien);

        self::assertSame($ancien, $stage->getTuteur());
        self::assertSame('Durand', $ancien->getNom());
        self::assertSame('d@acme.fr', $ancien->getEmail());
    }

    public function testUneEntrepriseInconnueResteNouvelle(): void
    {
        $saisie = (new StageEntreprise())->setRaisonSociale('Nouvelle')->setSiret('999');
        $stage = (new StageEtudiant())->setEntreprise($saisie);

        $this->resolver(null)->resoudre($stage, null, null);

        self::assertSame($saisie, $stage->getEntreprise());
    }

    public function testUnContactDupliqueEstIndependant(): void
    {
        $original = $this->avecId((new StageContact())->setNom('Martin'), 4);
        $copie = $original->dupliquer();

        self::assertNotSame($original, $copie);
        self::assertNull($copie->getId());
        self::assertSame('Martin', $copie->getNom());
    }
}
