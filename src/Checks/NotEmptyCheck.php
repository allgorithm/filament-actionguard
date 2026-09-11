<?php

namespace Allgorithm\FilamentActionGuard\Checks;

use Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract;
use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Illuminate\Database\Eloquent\Model;

class NotEmptyCheck implements ActionGuardCheckContract
{
    public function __construct(
        protected string $field,
        protected ?string $label = null,
        protected bool $required = true
    ) {}

    public static function make(string $field): self
    {
        return new self($field);
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

    public function evaluate(Model $record): CheckResult
    {
        $label = $this->label ?? (string) str($this->field)->headline();
        $value = $record->getAttribute($this->field);
        $isEmpty = false;
        if ($value === null) {
            $isEmpty = true;
        } elseif (is_array($value)) {
            $isEmpty = empty($value);
        } elseif ($value instanceof \Countable) {
            $isEmpty = count($value) === 0;
        } else {
            $isEmpty = trim((string) $value) === '';
        }

        if ($isEmpty) {
            return CheckResult::fail(
                key: $this->field,
                label: $label,
                message: __('filament-actionguard::checks.not_empty.message', ['field' => $label]),
                required: $this->required
            );
        }

        return CheckResult::pass(
            key: $this->field,
            label: $label
        );
    }
}
