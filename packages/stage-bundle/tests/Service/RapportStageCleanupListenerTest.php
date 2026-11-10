<?php

namespace StageBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use StageBundle\Entity\Stages\StageEtudiant;
use StageBundle\EventListener\RapportStageCleanupListener;
use StageBundle\Service\StageRapportStorage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RapportStageCleanupListenerTest extends TestCase
{
    private string $racine;
    private StageRapportStorage $storage;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->racine = sys_get_temp_dir().'/stage_cleanup_'.uniqid();
        $this->storage = new StageRapportStorage($this->racine);
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->racine);
    }

    private function dossierAvecRapport(): StageEtudiant
    {
        $tmp = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, "%PDF-1.4\n%%EOF");
        $chemin = $this->storage->enregistrer(new UploadedFile($tmp, 'r.pdf', null, null, true));

        return (new StageEtudiant())->deposerRapport($chemin, 'r.pdf');
    }

    public function testLeRapportEstEffaceApresLaSuppressionDuDossier(): void
    {
        $stage = $this->dossierAvecRapport();
        $chemin = (string) $stage->getReportPath();
        $listener = new RapportStageCleanupListener($this->storage, new NullLogger());

        $listener->preFlush(new PreFlushEventArgs($this->em));
        $listener->postRemove(new PostRemoveEventArgs($stage, $this->em));
        self::assertTrue($this->storage->existe($chemin), 'rien n\'est effacé avant la fin du flush');

        $listener->postFlush(new PostFlushEventArgs($this->em));
        self::assertFalse($this->storage->existe($chemin));
    }

    public function testUnFlushEchoueNEffaceRien(): void
    {
        $stage = $this->dossierAvecRapport();
        $chemin = (string) $stage->getReportPath();
        $listener = new RapportStageCleanupListener($this->storage, new NullLogger());

        $listener->preFlush(new PreFlushEventArgs($this->em));
        $listener->postRemove(new PostRemoveEventArgs($stage, $this->em)); // puis le flush échoue : pas de postFlush
        $listener->preFlush(new PreFlushEventArgs($this->em));              // flush suivant, sans suppression
        $listener->postFlush(new PostFlushEventArgs($this->em));

        self::assertTrue($this->storage->existe($chemin));
    }
}
