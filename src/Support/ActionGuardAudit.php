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
            ->notice('filament-actionguard.'.$event, self::filterContext($event, $context));
    }

    /**
     * @param  array<string, scalar|null>  $context
     * @return array<string, scalar|null>
     */
    private static function filterContext(string $event, array $context): array
    {
        $allowedKeys = match ($event) {
            'action_evaluated' => ['passed', 'failed', 'errors'],
            'invariant_blocked' => ['failed', 'errors'],
            default => [],
        };

        if (config('filament-actionguard.audit.include_model_type', false)
            && in_array($event, ['invariant_blocked', 'bypass_used'], true)) {
            $allowedKeys[] = 'model';
        }

        if (config('filament-actionguard.audit.include_state', false) && $event === 'invariant_blocked') {
            $allowedKeys[] = 'state';
        }

        return array_intersect_key($context, array_flip($allowedKeys));
    }
}
