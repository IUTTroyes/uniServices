<?php

namespace StageBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use StageBundle\Service\StageRapportStorage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StageRapportStorageTest extends TestCase
{
    private string $racine;
    private StageRapportStorage $storage;

    protected function setUp(): void
    {
        $this->racine = sys_get_temp_dir().'/stage_rapports_'.uniqid();
        $this->storage = new StageRapportStorage($this->racine);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->racine);
    }

    private function fichier(string $contenu, string $nom): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, $contenu);

        return new UploadedFile($tmp, $nom, null, null, true);
    }

    public function testUnPdfEstEnregistreHorsDuNomClient(): void
    {
        $chemin = $this->storage->enregistrer($this->fichier("%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF", '../../evil.pdf'));

        self::assertMatchesRegularExpression('#^\d{4}/[0-9a-f-]{36}\.pdf$#', $chemin);
        self::assertTrue($this->storage->existe($chemin));

        $this->storage->supprimer($chemin);
        self::assertFalse($this->storage->existe($chemin));
    }

    public function testUnFichierQuiNEstPasUnPdfEstRefuse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->storage->enregistrer($this->fichier('<html><script>alert(1)</script></html>', 'rapport.pdf'));
    }

    public function testLesCheminsSortantDuDossierSontRefuses(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->storage->cheminAbsolu('../../etc/passwd');
    }

    public function testLeNomAfficheEstNettoyeEtFiniParPdf(): void
    {
        self::assertSame('evil.pdf', StageRapportStorage::nettoyerNom('../../evil.pdf'));
        self::assertSame('rapport.pdf', StageRapportStorage::nettoyerNom(''));
        self::assertSame('virus.pdf', StageRapportStorage::nettoyerNom('virus.exe'));
        self::assertSame('Mon rapport.pdf', StageRapportStorage::nettoyerNom("Mon rapport\x07"));
    }

    public function testLeNomDeTelechargementEstImposeEtAscii(): void
    {
        self::assertSame(
            'Rapport-stage_DUPONT-LEFEVRE-Elodie_Stage-BUT2-2026-2027.pdf',
            StageRapportStorage::nomTelechargement('Dupont-Lefèvre', 'Élodie', 'Stage BUT2 – 2026/2027')
        );
        self::assertSame('Rapport-stage.pdf', StageRapportStorage::nomTelechargement(null, null, null));
    }

    public function testLeFichierNEstLisibleQueParLeServeur(): void
    {
        $chemin = $this->storage->enregistrer($this->fichier("%PDF-1.4\n%%EOF", 'r.pdf'));
        self::assertSame('0640', substr(sprintf('%o', fileperms($this->storage->cheminAbsolu($chemin))), -4));
    }
}
