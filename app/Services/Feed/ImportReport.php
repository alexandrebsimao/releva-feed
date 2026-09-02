<?php

namespace App\Services\Feed;

final readonly class ImportReport
{
    public function __construct(
        public int $imported = 0,
        public int $skipped = 0,
        public bool $failed = false,
        public ?string $error = null,
    ) {}
}
