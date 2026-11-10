<?php

namespace UnifolioBundle\Tests\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnifolioBundle\Service\Migration\V1\MigrationV1;
use UnifolioBundle\Service\Migration\V1\RapportMigration;

class MigrationV1Test extends TestCase
{
    #[DataProvider('textes')]
    public function testLeTexteV1EstNettoye(mixed $entree, ?string $attendu): void
    {
        self::assertSame($attendu, MigrationV1::texte($entree));
    }

    /** @return iterable<string, array{mixed, ?string}> */
    public static function textes(): iterable
    {
        yield 'html retiré' => ['<p>Une <strong>maquette</strong> &amp; son contexte</p>', 'Une maquette & son contexte'];
        yield 'script retiré' => ['Avant<script>alert(1)</script>après', 'Avantalert(1)après'];
        yield 'sauts de ligne' => ["<p>Un</p><p>Deux</p>\n\n\n\nTrois", "Un\nDeux\n\nTrois"];
        yield 'espaces' => ["  a \t  b  ", 'a b'];
        yield 'vide' => ['   ', null];
        yield 'null' => [null, null];
        yield 'pas une chaîne' => [['x'], null];
        yield 'que des balises' => ['<p> </p>', null];
    }

    public function testLeRapportCompteEtClasseLesEcarts(): void
    {
        $r = new RapportMigration();
        $r->compter('trace', RapportMigration::CREE);
        $r->compter('trace', RapportMigration::CREE);
        $r->compter('trace', RapportMigration::DEJA);
        $r->ecart('erreur', 'trace', 3, 'Contenu illisible.');
        $r->ecart('avertissement', 'etudiant', 9, 'Introuvable.');
        $r->ecart('avertissement', 'etudiant', 10, 'Introuvable.');

        self::assertSame(['trace' => [RapportMigration::CREE => 2, RapportMigration::DEJA => 1]], $r->compteurs());
        self::assertSame(1, $r->nbEcarts('erreur'));
        self::assertSame(2, $r->nbEcarts('avertissement'));
        self::assertSame(0, $r->nbEcarts('info'));
    }

    public function testLeCsvDuRapportNeutraliseLesFormules(): void
    {
        $r = new RapportMigration();
        $r->ecart('avertissement', 'trace', 1, '=HYPERLINK("http://x")');
        $r->ecart('info', 'cv', null, 'Normal');
        $lignes = array_map(static fn (string $l) => str_getcsv($l, ';', '"', '\\'), explode("\n", trim(substr($r->csv(), 3))));

        self::assertSame(['gravité', 'type', 'identifiant v1', 'message'], $lignes[0]);
        self::assertSame("'=HYPERLINK(\"http://x\")", $lignes[1][3]);
        self::assertSame('Normal', $lignes[2][3]);
    }
}
