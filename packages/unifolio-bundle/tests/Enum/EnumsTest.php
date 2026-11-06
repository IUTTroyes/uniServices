<?php

namespace UnifolioBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use UnifolioBundle\Enum\ConceptionIaEnum;
use UnifolioBundle\Enum\RealisationIaEnum;
use UnifolioBundle\Enum\StatutTraceEnum;
use UnifolioBundle\Enum\TypeMediaEnum;
use UnifolioBundle\Enum\TypePortfolioEnum;
use UnifolioBundle\Enum\VisibilitePortfolioEnum;

class EnumsTest extends TestCase
{
    public function testValeursIaCompatiblesV1(): void
    {
        // Valeurs stockées par UniFolio v1 : la migration en dépend.
        $this->assertSame(3, ConceptionIaEnum::NON_RENSEIGNE->value);
        $this->assertSame(2, ConceptionIaEnum::AVANCE->value);
        $this->assertSame(4, RealisationIaEnum::NON_RENSEIGNE->value);
        $this->assertSame(1, RealisationIaEnum::PEU->value);
        $this->assertFalse(ConceptionIaEnum::NON_RENSEIGNE->isRenseigne());
        $this->assertTrue(RealisationIaEnum::AUCUN->isRenseigne());
    }

    public function testPortfolioAcademiqueNePeutPasEtrePublic(): void
    {
        $this->assertTrue(TypePortfolioEnum::ACADEMIQUE->isEvaluable());
        $this->assertFalse(TypePortfolioEnum::VITRINE->isEvaluable());
        $this->assertFalse(TypePortfolioEnum::ACADEMIQUE->autoriseVisibilite(VisibilitePortfolioEnum::PUBLIC));
        $this->assertFalse(TypePortfolioEnum::ACADEMIQUE->autoriseVisibilite(VisibilitePortfolioEnum::LIEN_SECRET));
        $this->assertTrue(TypePortfolioEnum::ACADEMIQUE->autoriseVisibilite(VisibilitePortfolioEnum::ETABLISSEMENT));
        foreach (VisibilitePortfolioEnum::cases() as $visibilite) {
            $this->assertTrue(TypePortfolioEnum::VITRINE->autoriseVisibilite($visibilite));
        }
    }

    public function testSeulLePublicEstServiDirectement(): void
    {
        foreach (VisibilitePortfolioEnum::cases() as $visibilite) {
            $this->assertSame($visibilite === VisibilitePortfolioEnum::PUBLIC, $visibilite->isServableDirectement());
        }
    }

    public function testTransitionsDeStatutTrace(): void
    {
        $this->assertTrue(StatutTraceEnum::BROUILLON->peutPasserA(StatutTraceEnum::DEPOSEE));
        $this->assertFalse(StatutTraceEnum::BROUILLON->peutPasserA(StatutTraceEnum::EVALUEE));
        $this->assertTrue(StatutTraceEnum::EN_EVALUATION->peutPasserA(StatutTraceEnum::A_REVISER));
        $this->assertFalse(StatutTraceEnum::EN_EVALUATION->isModifiableParEtudiant());
        $this->assertTrue(StatutTraceEnum::A_REVISER->isModifiableParEtudiant());

        foreach (StatutTraceEnum::cases() as $statut) {
            $this->assertNotSame('', $statut->getLibelle());
            $this->assertNotContains($statut, $statut->transitionsPossibles(), 'Pas de transition vers soi-même');
        }
    }

    public function testMediasAFichierOntDesTypesMime(): void
    {
        foreach (TypeMediaEnum::cases() as $type) {
            $this->assertSame($type->requiertFichier(), $type->mimeTypesAcceptes() !== [], $type->value);
        }
    }
}
