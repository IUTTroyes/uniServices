<?php

namespace UnifolioBundle\Tests\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Service\Suivi\SuiviCampagne;

class SuiviCampagneTest extends TestCase
{
    private function suivi(string $nom): array
    {
        return [
            'competences' => [['id' => 1, 'nomCourt' => 'Comprendre']],
            'lignes' => [[
                'etudiant' => ['id' => 5, 'nom' => $nom, 'prenom' => 'Lauren'],
                'cellules' => [1 => ['total' => 4, 'couverts' => 2, 'nbTraces' => 3, 'evalScore' => 0.755, 'autoScore' => null]],
                'resume' => ['nbTraces' => 3, 'aEvaluer' => 1],
                'alertes' => ['a_evaluer', 'ecart_notable'],
            ]],
        ];
    }

    private function csv(array $suivi): string
    {
        return (new \ReflectionClass(SuiviCampagne::class))->newInstanceWithoutConstructor()->csv($suivi);
    }

    public function testLeCsvEstExploitableDansExcel(): void
    {
        $csv = $this->csv($this->suivi('LAMOUREUX'));
        self::assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lignes = array_map(static fn (string $l) => str_getcsv($l, ';', '"', '\\'), explode("\n", trim(substr($csv, 3))));
        self::assertSame(['Nom', 'Prénom', 'Comprendre — couverts', 'Comprendre — évaluation (%)', 'Comprendre — auto-évaluation (%)', 'Traces déposées', 'À évaluer', 'Alertes'], $lignes[0]);
        self::assertSame(['LAMOUREUX', 'Lauren', '2/4', '76', '', '3', '1', 'a_evaluer, ecart_notable'], $lignes[1]);
    }

    #[DataProvider('formules')]
    public function testLesFormulesSontNeutralisees(string $nom): void
    {
        $lignes = array_map(static fn (string $l) => str_getcsv($l, ';', '"', '\\'), explode("\n", trim(substr($this->csv($this->suivi($nom)), 3))));
        self::assertSame("'".$nom, $lignes[1][0]);
    }

    /** @return iterable<string, array{string}> */
    public static function formules(): iterable
    {
        yield 'égal' => ['=HYPERLINK("http://x","y")'];
        yield 'plus' => ['+33612345678'];
        yield 'moins' => ['-2+3'];
        yield 'arobase' => ['@SUM(A1)'];
    }
}
