<?php

namespace UnifolioBundle\Tests\Entity;

use App\Entity\Apc\ApcApprentissageCritique;
use App\Entity\Apc\ApcCompetence;
use App\Entity\Apc\ApcNiveau;
use App\Entity\Users\Etudiant;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Entity\Trace;
use UnifolioBundle\Entity\TraceMedia;
use UnifolioBundle\Entity\TraceRattachement;
use UnifolioBundle\Enum\ConceptionIaEnum;
use UnifolioBundle\Enum\GranulariteEnum;
use UnifolioBundle\Enum\RealisationIaEnum;
use UnifolioBundle\Enum\StatutTraceEnum;
use UnifolioBundle\Enum\TypeMediaEnum;
use UnifolioBundle\Service\Media\StoredMedia;

class TraceTest extends TestCase
{
    public function testNouvelleTraceEnBrouillonAvecUuid(): void
    {
        $trace = new Trace(new Etudiant(), '  Maquette SAÉ 1.01  ');

        $this->assertSame('Maquette SAÉ 1.01', $trace->getTitre());
        $this->assertSame(StatutTraceEnum::BROUILLON, $trace->getStatut());
        $this->assertNotNull($trace->getUuid());
        $this->assertTrue($trace->isModifiable());
    }

    public function testTitreObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Trace(new Etudiant(), '   ');
    }

    public function testDepotExigePreuveRattachementEtDeclarationIa(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $this->assertCount(3, $trace->getErreursDepot());

        $trace->addMedia(TraceMedia::depuisTexte($trace, TypeMediaEnum::TEXTE, 'Compte rendu'));
        $trace->addRattachement(TraceRattachement::versCompetence($trace, $this->competence(1)));
        $this->assertSame(['Déclarez l\'usage de l\'IA (conception et réalisation).'], $trace->getErreursDepot());

        $trace->setConceptionIa(ConceptionIaEnum::AUCUN)->setRealisationIa(RealisationIaEnum::PEU);
        $trace->deposer(new \DateTimeImmutable('2026-11-06'));

        $this->assertSame(StatutTraceEnum::DEPOSEE, $trace->getStatut());
        $this->assertSame('2026-11-06', $trace->getDeposeeLe()?->format('Y-m-d'));

        $trace->retirerEnBrouillon();
        $this->assertSame(StatutTraceEnum::BROUILLON, $trace->getStatut());
        $this->assertNull($trace->getDeposeeLe());
    }

    public function testDepotRefuseSansDeclaration(): void
    {
        $this->expectException(\DomainException::class);
        (new Trace(new Etudiant(), 'Projet'))->deposer();
    }

    public function testTransitionInterdite(): void
    {
        $this->expectException(\DomainException::class);
        (new Trace(new Etudiant(), 'Projet'))->changerStatut(StatutTraceEnum::EVALUEE);
    }

    public function testTagsNormalises(): void
    {
        $trace = (new Trace(new Etudiant(), 'Projet'))->setTags([' Web ', 'web', '', 'UX']);

        $this->assertSame(['web', 'ux'], $trace->getTags());
    }

    public function testMediasOrdonnes(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $a = TraceMedia::depuisTexte($trace, TypeMediaEnum::TEXTE, 'A');
        $b = TraceMedia::depuisUrl($trace, TypeMediaEnum::LIEN, 'https://example.org');
        $trace->addMedia($a)->addMedia($b);

        $this->assertSame([0, 1], [$a->getOrdre(), $b->getOrdre()]);
    }

    public function testMediaUrlEtEmbedValides(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');

        $this->assertSame('https://www.youtube.com/watch?v=x', TraceMedia::depuisUrl($trace, TypeMediaEnum::EMBED, 'https://www.youtube.com/watch?v=x')->getUrl());

        foreach (['javascript:alert(1)', 'ftp://example.org', 'pas une url'] as $url) {
            try {
                TraceMedia::depuisUrl($trace, TypeMediaEnum::LIEN, $url);
                $this->fail("URL acceptée à tort : $url");
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->expectException(\InvalidArgumentException::class);
        TraceMedia::depuisUrl($trace, TypeMediaEnum::EMBED, 'https://site-inconnu.example/video');
    }

    public function testCodeConserveLeLangage(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');

        $this->assertSame(['langage' => 'php'], TraceMedia::depuisTexte($trace, TypeMediaEnum::CODE, '<?php echo 1;', 'PHP')->getMetadata());
        $this->assertSame([], TraceMedia::depuisTexte($trace, TypeMediaEnum::CODE, 'x', '<script>')->getMetadata());
    }

    public function testRattachementGranulariteEtCompetenceParente(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $competence = $this->competence(7);
        $niveau = $this->createMock(ApcNiveau::class);
        $niveau->method('getId')->willReturn(70);
        $niveau->method('getCompetence')->willReturn($competence);
        $ac = $this->createMock(ApcApprentissageCritique::class);
        $ac->method('getId')->willReturn(700);
        $ac->method('getNiveau')->willReturn($niveau);

        $rAc = TraceRattachement::versApprentissageCritique($trace, $ac, 'Parce que…');
        $this->assertSame(GranulariteEnum::AC, $rAc->getGranularite());
        $this->assertSame('ac:700', $rAc->getCleCible());
        $this->assertSame($competence, $rAc->getCompetenceParente());

        $this->assertSame(GranulariteEnum::NIVEAU, TraceRattachement::versNiveau($trace, $niveau)->getGranularite());
        $this->assertSame($competence, TraceRattachement::versNiveau($trace, $niveau)->getCompetenceParente());
    }

    public function testRemplacerRattachementsConserveLesExistants(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $c1 = $this->competence(1);
        $c2 = $this->competence(2);
        $c3 = $this->competence(3);

        $trace->addRattachement(TraceRattachement::versCompetence($trace, $c1, 'ancien'));
        $trace->addRattachement(TraceRattachement::versCompetence($trace, $c2));
        $existant = $trace->getRattachements()->first();

        $trace->remplacerRattachements([
            TraceRattachement::versCompetence($trace, $c3),
            TraceRattachement::versCompetence($trace, $c1, 'nouveau'),
        ]);

        $cles = array_map(static fn (TraceRattachement $r) => $r->getCleCible(), $trace->getRattachements()->toArray());
        sort($cles);
        $this->assertSame(['competence:1', 'competence:3'], $cles);
        $this->assertTrue($trace->getRattachements()->contains($existant), 'Le rattachement existant est conservé (pas recréé)');
        $this->assertSame('nouveau', $existant->getArgumentaire());
        $this->assertSame(1, $existant->getOrdre());
    }

    public function testAddRattachementSansDoublon(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $c = $this->competence(1);
        $trace->addRattachement(TraceRattachement::versCompetence($trace, $c));
        $trace->addRattachement(TraceRattachement::versCompetence($trace, $c, 'maj'));

        $this->assertCount(1, $trace->getRattachements());
        $this->assertSame('maj', $trace->getRattachements()->first()->getArgumentaire());
    }

    private function competence(int $id): ApcCompetence
    {
        $c = $this->createMock(ApcCompetence::class);
        $c->method('getId')->willReturn($id);

        return $c;
    }

    public function testChangerTypeDUnFichier(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $media = TraceMedia::depuisFichier($trace, TypeMediaEnum::IMAGE, new StoredMedia('2026/11/a.png', 'a.png', 'image/png', 10, TypeMediaEnum::IMAGE));

        $media->changerType(TypeMediaEnum::FICHIER);
        $this->assertSame(TypeMediaEnum::FICHIER, $media->getType());
        $media->changerType(TypeMediaEnum::IMAGE);
        $this->assertSame(TypeMediaEnum::IMAGE, $media->getType());

        $this->expectException(\InvalidArgumentException::class);
        $media->changerType(TypeMediaEnum::PDF);
    }

    public function testChangerLienEnContenuIntegre(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $lien = TraceMedia::depuisUrl($trace, TypeMediaEnum::LIEN, 'https://youtu.be/abc');
        $lien->changerType(TypeMediaEnum::EMBED);
        $this->assertSame(TypeMediaEnum::EMBED, $lien->getType());

        $autre = TraceMedia::depuisUrl($trace, TypeMediaEnum::LIEN, 'https://example.org');
        $this->expectException(\InvalidArgumentException::class);
        $autre->changerType(TypeMediaEnum::EMBED);
    }

    public function testTexteEnCodeEtRetour(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $media = TraceMedia::depuisTexte($trace, TypeMediaEnum::TEXTE, 'echo 1;');
        $media->changerType(TypeMediaEnum::CODE)->setLangage('php');
        $this->assertSame(['langage' => 'php'], $media->getMetadata());

        $media->changerType(TypeMediaEnum::TEXTE);
        $this->assertSame([], $media->getMetadata());
        $this->assertFalse(\in_array(TypeMediaEnum::LIEN, $media->getTypesCompatibles(), true));
    }

    public function testChangerUrlVerifieLAdresse(): void
    {
        $trace = new Trace(new Etudiant(), 'Projet');
        $media = TraceMedia::depuisUrl($trace, TypeMediaEnum::LIEN, 'https://example.org');
        $media->changerUrl('https://example.org/v2');
        $this->assertSame('https://example.org/v2', $media->getUrl());

        $this->expectException(\InvalidArgumentException::class);
        $media->changerUrl('javascript:alert(1)');
    }
}
