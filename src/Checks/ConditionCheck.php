<?php

namespace Allgorithm\FilamentActionGuard\Checks;

use Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract;
use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Closure;
use Illuminate\Database\Eloquent\Model;

class ConditionCheck implements ActionGuardCheckContract
{
    public function __construct(
        protected string $key,
        protected ?Closure $condition = null,
        protected ?string $failureMessage = null,
        protected ?string $label = null,
        protected bool $required = true
    ) {}

    public static function make(
        string $key,
        ?Closure $condition = null,
        ?string $failureMessage = null,
        ?string $label = null
    ): self {
        return new self($key, $condition, $failureMessage, $label);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function optional(): self
    {
        $this->required = false;

        return $this;
    }

    public function check(Closure $condition): self
    {
        $this->condition = $condition;

        return $this;
    }

    public function failureMessage(string $message): self
    {
        $this->failureMessage = $message;

        return $this;
    }

    public function evaluate(Model $record): CheckResult
    {
        $label = $this->label ?? (string) str($this->key)->headline();

        if (! $this->condition) {
            return CheckResult::error(
                key: $this->key,
                label: $label,
                message: "No condition defined for ConditionCheck '{$this->key}'."
            );
        }

        try {
            $passed = (bool) call_user_func($this->condition, $record);
        } catch (\Throwable $e) {
            return CheckResult::error(
                key: $this->key,
                label: $label,
                message: "ConditionCheck '{$this->key}' encountered an error: ".$e->getMessage()
            );
        }

        if (! $passed) {
            return CheckResult::fail(
                key: $this->key,
                label: $label,
                message: $this->failureMessage ?? __('filament-actionguard::checks.condition.message', ['condition' => $label]),
                required: $this->required
            );
        }

        return CheckResult::pass(
            key: $this->key,
            label: $label
        );
    }
}
