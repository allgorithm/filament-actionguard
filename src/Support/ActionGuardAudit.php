<?php

declare(strict_types=1);

namespace Allgorithm\FilamentActionGuard\Support;

use Illuminate\Support\Facades\Log;

final class ActionGuardAudit
{
    /**
     * Writes a deliberately data-minimised audit event. Applications that require
     * durable audit storage can route the configured channel to their SIEM.
     *
     * @param  array<string, scalar|null>  $context
     */
    public static function record(string $event, array $context = []): void
    {
        if (! config('filament-actionguard.audit.enabled', false)) {
            return;
        }

        Log::channel(config('filament-actionguard.audit.channel'))
            ->notice('filament-actionguard.'.$event, $context);
    }
}
