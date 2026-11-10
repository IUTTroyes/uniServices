<?php

namespace UnifolioBundle\Service\Portfolio;

use App\Entity\Users\Etudiant;
use UnifolioBundle\Repository\TraceRepository;

/** Les traces et médias que l'étudiant peut référencer dans ses blocs : les siens. */
final class ContexteEtudiant implements ContexteBlocs
{
    /** @var array<string, \UnifolioBundle\Entity\Trace|null> */
    private array $cache = [];

    public function __construct(
        private readonly Etudiant $etudiant,
        private readonly TraceRepository $traces,
    ) {
    }

    public function traceAppartient(string $uuid): bool
    {
        return $this->trace($uuid) !== null;
    }

    public function mediaAppartient(string $traceUuid, int $mediaId): bool
    {
        return $this->trace($traceUuid)?->getMediaParId($mediaId) !== null;
    }

    private function trace(string $uuid): ?\UnifolioBundle\Entity\Trace
    {
        $uuid = strtolower($uuid);
        if (!\array_key_exists($uuid, $this->cache)) {
            $trace = $this->traces->findOneByUuid($uuid);
            $this->cache[$uuid] = $trace !== null && $trace->appartientA($this->etudiant) ? $trace : null;
        }

        return $this->cache[$uuid];
    }
}
