<?php

namespace UnifolioBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UnifolioBundle\Enum\TypeMediaEnum;
use UnifolioBundle\Service\Media\LocalMediaStorage;
use UnifolioBundle\Service\Media\MediaValidationException;

class LocalMediaStorageTest extends TestCase
{
    private string $racine;
    private string $sources;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir() . '/unifolio-test-' . bin2hex(random_bytes(4));
        $this->racine = $base . '/media';
        $this->sources = $base . '/sources';
        (new Filesystem())->mkdir($this->sources);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(\dirname($this->racine));
    }

    public function testEnregistreUneImageHorsWebroot(): void
    {
        $storage = new LocalMediaStorage($this->racine, 1024 * 1024);
        $stored = $storage->store($this->png('../../etc/évil.png'), TypeMediaEnum::IMAGE);

        $this->assertMatchesRegularExpression('#^\d{4}/\d{2}/[0-9a-f-]{36}\.png$#', $stored->chemin);
        $this->assertSame('image/png', $stored->mimeType);
        $this->assertSame('évil.png', $stored->nomOriginal);
        $this->assertTrue($storage->exists($stored->chemin));
        $this->assertStringStartsWith($this->racine . '/', $storage->cheminAbsolu($stored->chemin));

        $storage->delete($stored->chemin);
        $this->assertFalse($storage->exists($stored->chemin));
    }

    public function testDeduitLeTypeDuFormatReel(): void
    {
        $storage = new LocalMediaStorage($this->racine, 1024 * 1024);
        // Le nom et le type annoncés par le navigateur ne comptent pas.
        $stored = $storage->store($this->png('document.pdf'));

        $this->assertSame(TypeMediaEnum::IMAGE, $stored->type);
        $this->assertSame('image/png', $stored->mimeType);
    }

    public function testRefuseUnFormatNonPrisEnCharge(): void
    {
        $chemin = $this->sources . '/exe';
        file_put_contents($chemin, "MZ\x90\x00" . str_repeat("\x00", 64));
        $this->expectException(MediaValidationException::class);
        (new LocalMediaStorage($this->racine, 1024 * 1024))->store(new UploadedFile($chemin, 'outil.exe', null, null, true));
    }

    public function testRefuseUnTypeNonConforme(): void
    {
        $this->expectException(MediaValidationException::class);
        (new LocalMediaStorage($this->racine, 1024 * 1024))->store($this->png('photo.png'), TypeMediaEnum::PDF);
    }

    public function testRefuseUnFichierTropVolumineux(): void
    {
        $this->expectException(MediaValidationException::class);
        (new LocalMediaStorage($this->racine, 10))->store($this->png('photo.png'), TypeMediaEnum::IMAGE);
    }

    public function testRefuseUnTypeSansFichier(): void
    {
        $this->expectException(MediaValidationException::class);
        (new LocalMediaStorage($this->racine, 1024 * 1024))->store($this->png('photo.png'), TypeMediaEnum::LIEN);
    }

    public function testRefuseLesChemins(): void
    {
        $storage = new LocalMediaStorage($this->racine, 1024);
        $this->expectException(\InvalidArgumentException::class);
        $storage->cheminAbsolu('2026/../../secret');
    }

    private function png(string $nomClient): UploadedFile
    {
        $chemin = $this->sources . '/' . bin2hex(random_bytes(4));
        // PNG 1x1 transparent.
        file_put_contents($chemin, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

        return new UploadedFile($chemin, $nomClient, 'image/png', null, true);
    }
}
