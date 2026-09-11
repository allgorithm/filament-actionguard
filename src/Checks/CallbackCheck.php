<?php

namespace Allgorithm\FilamentActionGuard\Checks;

use Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract;
use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CallbackCheck implements ActionGuardCheckContract
{
    public function __construct(
        protected string $key,
        protected ?Closure $callback = null,
    ) {}

    /**
     * @param  (Closure(Model): CheckResult)|null  $callback
     */
    public static function make(string $key, ?Closure $callback = null): self
    {
        return new self($key, $callback);
    }

    /**
     * @param  Closure(Model): CheckResult  $callback
     */
    public function check(Closure $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function evaluate(Model $record): CheckResult
    {
        if (! $this->callback) {
            return CheckResult::error(
                key: $this->key,
                label: (string) str($this->key)->headline(),
                message: "No callback defined for CallbackCheck '{$this->key}'."
            );
        }

        $label = (string) str($this->key)->headline();

        try {
            $result = call_user_func($this->callback, $record);

            if (! $result instanceof CheckResult) {
                return CheckResult::error(
                    key: $this->key,
                    label: $label,
                    message: "Callback for CallbackCheck '{$this->key}' must return an instance of CheckResult."
                );
            }

            return $result;
        } catch (\Throwable $e) {
            $reference = (string) Str::uuid();
            report($e);

            return CheckResult::error(
                key: $this->key,
                label: $label,
                message: __('filament-actionguard::ui.errors.check_failed', ['reference' => $reference])
            );
        }
    }
}
