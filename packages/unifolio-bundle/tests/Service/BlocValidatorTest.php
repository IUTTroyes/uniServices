<?php

namespace UnifolioBundle\Tests\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Service\Portfolio\BlocRegistry;
use UnifolioBundle\Service\Portfolio\BlocValidator;
use UnifolioBundle\Service\Portfolio\ContexteBlocs;
use UnifolioBundle\Service\Portfolio\ContexteModele;
use UnifolioBundle\Service\Trace\UnifolioValidationException;

class BlocValidatorTest extends TestCase
{
    private const MA_TRACE = '0193a000-0000-7000-8000-000000000001';
    private const TRACE_D_UN_AUTRE = '0193a000-0000-7000-8000-000000000002';

    private BlocValidator $validateur;
    private ContexteBlocs $moi;

    protected function setUp(): void
    {
        $this->validateur = new BlocValidator(new BlocRegistry());
        $this->moi = new class implements ContexteBlocs {
            public function traceAppartient(string $uuid): bool
            {
                return $uuid === '0193a000-0000-7000-8000-000000000001';
            }

            public function mediaAppartient(string $traceUuid, int $mediaId): bool
            {
                return $traceUuid === '0193a000-0000-7000-8000-000000000001' && $mediaId === 7;
            }
        };
    }

    public function testLeSchemaDeclareTousLesTypesAttendus(): void
    {
        $types = array_keys((new BlocRegistry())->types());

        foreach (['heading', 'paragraph', 'quote', 'list', 'callout', 'divider', 'button', 'image', 'gallery', 'embed', 'file', 'columns', 'column', 'group', 'accordion', 'panel', 'trace', 'traces', 'progression', 'cv', 'parcours', 'reflexion'] as $type) {
            $this->assertContains($type, $types);
        }
        $this->assertNotContains('column', (new BlocRegistry())->typesRacine(), 'Une colonne n\'existe que dans des colonnes');
    }

    public function testNormaliseEtAjouteLesValeursParDefaut(): void
    {
        $blocs = $this->valider([
            ['id' => 'abcdefgh', 'type' => 'heading', 'attrs' => ['texte' => "  Mon   portfolio \n", 'inconnu' => 'x']],
            ['type' => 'divider'],
        ]);

        $this->assertSame('Mon portfolio', $blocs[0]['attrs']['texte']);
        $this->assertSame(2, $blocs[0]['attrs']['niveau'], 'Valeur par défaut');
        $this->assertArrayNotHasKey('inconnu', $blocs[0]['attrs'], 'Attribut inconnu écarté');
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $blocs[1]['id'], 'Identifiant généré');
    }

    public function testImbricationColonnesEtVolets(): void
    {
        $blocs = $this->valider([
            ['id' => 'colonnes1', 'type' => 'columns', 'attrs' => ['ratio' => '1-2'], 'children' => [
                ['id' => 'colonneA', 'type' => 'column', 'children' => [['id' => 'texteAAAA', 'type' => 'paragraph', 'attrs' => ['texte' => 'Bonjour']]]],
                ['id' => 'colonneB', 'type' => 'column', 'children' => []],
            ]],
            ['id' => 'accord01', 'type' => 'accordion', 'children' => [['id' => 'volet001', 'type' => 'panel', 'attrs' => ['titre' => 'Détails'], 'children' => []]]],
        ]);

        $this->assertSame('paragraph', $blocs[0]['children'][0]['children'][0]['type']);
        $this->assertSame('1-2', $blocs[0]['attrs']['ratio']);
    }

    #[DataProvider('documentsRefuses')]
    public function testRefuseLesDocumentsDangereuxOuIncoherents(array $blocs, string $motif): void
    {
        try {
            $this->valider($blocs);
            $this->fail('Document accepté à tort');
        } catch (UnifolioValidationException $e) {
            $this->assertStringContainsString($motif, implode(' ', $e->getErreurs()));
        }
    }

    /** @return iterable<string, array{0: array<int, array<string, mixed>>, 1: string}> */
    public static function documentsRefuses(): iterable
    {
        yield 'type inconnu' => [[['type' => 'script']], 'type de bloc inconnu'];
        yield 'colonne hors colonnes' => [[['type' => 'column']], 'ne peut figurer que dans'];
        yield 'enfant interdit dans des colonnes' => [[['type' => 'columns', 'children' => [['type' => 'paragraph']]]], 'pas autorisé ici'];
        yield 'enfants sur un bloc feuille' => [[['type' => 'divider', 'children' => [['type' => 'divider']]]], 'ne peut pas contenir'];
        yield 'url javascript' => [[['type' => 'button', 'attrs' => ['libelle' => 'x', 'url' => 'javascript:alert(1)']]], 'adresse http(s) valide'];
        yield 'url data' => [[['type' => 'button', 'attrs' => ['libelle' => 'x', 'url' => 'data:text/html,<script>']]], 'adresse http(s) valide'];
        yield 'site non intégrable' => [[['type' => 'embed', 'attrs' => ['url' => 'https://site-inconnu.example/v']]], 'intégrables autorisés'];
        yield 'embed en http' => [[['type' => 'embed', 'attrs' => ['url' => 'http://www.youtube.com/watch?v=x']]], 'intégrables autorisés'];
        yield 'enum hors liste' => [[['type' => 'callout', 'attrs' => ['ton' => 'rouge-vif']]], 'non autorisée'];
        yield 'niveau hors bornes' => [[['type' => 'heading', 'attrs' => ['niveau' => 9, 'texte' => 'x']]], 'entre 1 et 4'];
        yield 'texte trop long' => [[['type' => 'heading', 'attrs' => ['texte' => str_repeat('a', 201)]]], '200 caractères'];
        yield 'trace d\'un autre' => [[['type' => 'trace', 'attrs' => ['trace' => self::TRACE_D_UN_AUTRE]]], 'ne vous appartient pas'];
        yield 'trace non uuid' => [[['type' => 'trace', 'attrs' => ['trace' => '1 OR 1=1']]], 'ne vous appartient pas'];
        yield 'media d\'un autre' => [[['type' => 'image', 'attrs' => ['source' => ['trace' => self::TRACE_D_UN_AUTRE, 'media' => 7]]]], 'ne vous appartient pas'];
        yield 'media inexistant' => [[['type' => 'image', 'attrs' => ['source' => ['trace' => self::MA_TRACE, 'media' => 999]]]], 'ne vous appartient pas'];
        yield 'identifiant en double' => [[['id' => 'identique1', 'type' => 'divider'], ['id' => 'identique1', 'type' => 'divider']], 'en double'];
        yield 'identifiant douteux' => [[['id' => '"><img onerror=1>', 'type' => 'divider']], 'identifiant de bloc invalide'];
        yield 'liste trop longue' => [[['type' => 'list', 'attrs' => ['elements' => array_fill(0, 51, 'a')]]], '50 éléments maximum'];
        yield 'pas une liste' => [['type' => 'divider'], 'liste de blocs'];
    }

    public function testRefuseUneImbricationTropProfonde(): void
    {
        $bloc = ['id' => 'prof-0000', 'type' => 'divider'];
        for ($i = 1; $i <= 6; ++$i) {
            $bloc = ['id' => "groupe-$i-xx", 'type' => 'group', 'children' => [$bloc]];
        }

        $this->expectException(UnifolioValidationException::class);
        $this->expectExceptionMessage('imbrication trop profonde');
        $this->valider([$bloc]);
    }

    public function testRefuseUnePageTropVolumineuse(): void
    {
        $this->expectException(UnifolioValidationException::class);
        $this->expectExceptionMessage('trop volumineuse');
        $this->valider(array_fill(0, 120, ['type' => 'paragraph', 'attrs' => ['texte' => str_repeat('a', 4000)]]));
    }

    public function testAccepteMesTracesMesMediasEtLesImagesExternes(): void
    {
        $blocs = $this->valider([
            ['type' => 'trace', 'attrs' => ['trace' => strtoupper(self::MA_TRACE), 'affichage' => 'detail']],
            ['type' => 'image', 'attrs' => ['source' => ['trace' => self::MA_TRACE, 'media' => '7']]],
            ['type' => 'image', 'attrs' => ['source' => ['url' => 'https://exemple.fr/photo.jpg']]],
            ['type' => 'embed', 'attrs' => ['url' => 'https://www.youtube.com/watch?v=abc']],
        ]);

        $this->assertSame(self::MA_TRACE, $blocs[0]['attrs']['trace'], 'Uuid normalisé en minuscules');
        $this->assertSame(['trace' => self::MA_TRACE, 'media' => 7], $blocs[1]['attrs']['source']);
        $this->assertSame(['url' => 'https://exemple.fr/photo.jpg'], $blocs[2]['attrs']['source']);
    }

    public function testUnModeleNePeutReferencerAucuneTraceNiMedia(): void
    {
        $this->expectException(UnifolioValidationException::class);
        $this->validateur->valider([['type' => 'trace', 'attrs' => ['trace' => self::MA_TRACE]]], new ContexteModele());
    }

    public function testUnBlocIncompletEstEnregistrableMaisSignale(): void
    {
        $blocs = $this->valider([
            ['id' => 'bouton001', 'type' => 'button', 'attrs' => ['libelle' => 'Voir']],
            ['id' => 'groupe001', 'type' => 'group', 'children' => [['id' => 'image001', 'type' => 'image']]],
        ]);

        $manques = implode(' | ', $this->validateur->incomplets($blocs));
        $this->assertStringContainsString('Adresse', $manques);
        $this->assertStringContainsString('Image', $manques);
        $this->assertSame([], $this->validateur->incomplets($this->valider([['type' => 'divider']])));
    }

    /** @param list<array<string, mixed>>|array<string, mixed> $blocs @return list<array<string, mixed>> */
    private function valider(array $blocs): array
    {
        return $this->validateur->valider($blocs, $this->moi);
    }
}
