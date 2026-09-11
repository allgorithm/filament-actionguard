<?php

declare(strict_types=1);

namespace Allgorithm\FilamentActionGuard\Traits;

use Allgorithm\FilamentActionGuard\Bridge\EnterpriseBridgeResolver;
use Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract;
use Allgorithm\FilamentActionGuard\Exceptions\StateInvariantViolationException;
use Allgorithm\FilamentActionGuard\Results\ActionGuardResult;
use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait HasActionGuards
 *
 * Automatically protects Eloquent models against post-save invalidation.
 * Enforces state invariants whenever the model is saved via Filament, APIs, or CLI.
 */
trait HasActionGuards
{
    /**
     * Set to true to temporarily bypass action guard enforcement.
     */
    public bool $skipActionGuards = false;

    /**
     * Boot the trait and register the Eloquent saving event.
     */
    public static function bootHasActionGuards(): void
    {
        static::saving(function (Model $model) {
            /** @var Model&HasActionGuards $model */
            $model->enforceActionGuards();
        });
    }

    /**
     * Enforces all action guards configured for the model's current or target state.
     *
     * @throws StateInvariantViolationException
     */
    public function enforceActionGuards(): void
    {
        if ($this->skipActionGuards) {
            return;
        }

        $stateColumn = $this->getActionGuardStateColumn();
        $state = (string) $this->getAttribute($stateColumn);

        $guards = $this->getGuardsForState($state);

        if (empty($guards)) {
            return;
        }

        $results = [];
        foreach ($guards as $check) {
            try {
                $results[] = $check->evaluate($this);
            } catch (\Throwable $e) {
                $reference = (string) Str::uuid();
                report($e);

                $results[] = CheckResult::error(
                    key: 'state_guard_error',
                    label: __('filament-actionguard::ui.errors.invariant_label'),
                    message: __('filament-actionguard::ui.errors.invariant_failed', ['reference' => $reference]),
                );
            }
        }

        $result = ActionGuardResult::fromChecks($results);

        if (! $result->passed) {
            throw StateInvariantViolationException::fromResult($this, $state, $result);
        }
    }

    /**
     * Resolves all ActionGuard checks associated with a given state.
     *
     * @return array<ActionGuardCheckContract>
     */
    public function getGuardsForState(string $state): array
    {
        $map = method_exists($this, 'actionGuards') ? $this->actionGuards() : [];

        if (! isset($map[$state])) {
            return [];
        }

        $configured = $map[$state];

        // 1. If configured as an Enterprise Operation class string
        if (is_string($configured) && class_exists($configured)) {
            return app(EnterpriseBridgeResolver::class)->resolveGuards($configured);
        }

        // 2. If configured as an array of checks / class-strings
        if (is_array($configured)) {
            return array_map(function ($check) {
                if (is_string($check) && class_exists($check)) {
                    return app($check);
                }

                return $check;
            }, $configured);
        }

        return [];
    }

    /**
     * Name of the Eloquent column holding the state (default: 'status').
     */
    public function getActionGuardStateColumn(): string
    {
        return property_exists($this, 'actionGuardStateColumn')
            ? (string) $this->actionGuardStateColumn
            : 'status';
    }

    /**
     * Executes a callback without triggering ActionGuard checks.
     */
    public function withoutActionGuards(callable $callback): mixed
    {
        $previous = $this->skipActionGuards;
        $this->skipActionGuards = true;

        try {
            return $callback($this);
        } finally {
            $this->skipActionGuards = $previous;
        }
    }
}
