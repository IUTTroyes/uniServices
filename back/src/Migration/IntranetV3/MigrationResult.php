<?php

namespace App\Migration\IntranetV3;

final readonly class MigrationResult
{
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public int $failed = 0,
        public array $messages = [],
    ) {
    }

    public function total(): int
    {
        return $this->created + $this->updated + $this->skipped + $this->failed;
    }
}
