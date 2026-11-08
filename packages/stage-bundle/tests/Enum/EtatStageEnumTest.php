<?php

namespace StageBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use StageBundle\Enum\EtatStageEnum as E;

class EtatStageEnumTest extends TestCase
{
    public function testLeParcoursNormalEstPermis(): void
    {
        $parcours = [E::INCOMPLET, E::AUTORISE, E::DEPOSE, E::VALIDE, E::CONVENTION_IMPRIMEE, E::CONVENTION_ENVOYEE, E::CONVENTION_RECUE];
        for ($i = 1; $i < \count($parcours); ++$i) {
            self::assertTrue($parcours[$i - 1]->peutPasserA($parcours[$i]), $parcours[$i - 1]->name.' → '.$parcours[$i]->name);
        }
        self::assertTrue(E::REFUS->peutPasserA(E::DEPOSE), 'l\'étudiant corrige et redépose');
    }

    public function testLesSautsSontInterdits(): void
    {
        self::assertFalse(E::AUTORISE->peutPasserA(E::VALIDE));
        self::assertFalse(E::DEPOSE->peutPasserA(E::CONVENTION_RECUE));
        self::assertFalse(E::REFUS->peutPasserA(E::VALIDE));
        self::assertFalse(E::CONVENTION_RECUE->peutPasserA(E::DEPOSE));
        self::assertFalse(E::CONVENTION_RECUE->peutPasserA(E::AUTORISE));
    }

    public function testChaqueEtatAUnLibelleEtMemeEtatToujoursPermis(): void
    {
        foreach (E::cases() as $etat) {
            self::assertNotSame('', $etat->getLibelle());
            self::assertTrue($etat->peutPasserA($etat));
            self::assertNotContains($etat, $etat->transitionsPossibles());
        }
    }
}
