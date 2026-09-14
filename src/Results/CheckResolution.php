<?php

declare(strict_types=1);

namespace Allgorithm\FilamentActionGuard\Results;

use Allgorithm\FilamentActionGuard\Support\ResolutionUrlSanitizer;

final readonly class CheckResolution
{
    public string $label;

    public ?string $url;

    public function __construct(
        string $label,
        ?string $url = null,
    ) {
        $this->label = $label;
        $this->url = ResolutionUrlSanitizer::sanitize($url);
    }
}
