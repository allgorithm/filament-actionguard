<?php

declare(strict_types=1);

namespace Allgorithm\FilamentActionGuard\Results;

final readonly class CheckResolution
{
    public function __construct(
        public string $label,
        public ?string $url = null,
    ) {}
}
