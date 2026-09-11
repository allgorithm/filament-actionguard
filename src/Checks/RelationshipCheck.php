<?php

namespace Allgorithm\FilamentActionGuard\Checks;

use Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract;
use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RelationshipCheck implements ActionGuardCheckContract
{
    public function __construct(
        protected string $relationship,
        protected ?string $label = null,
        protected bool $required = true
    ) {}

    public static function make(string $relationship): self
    {
        return new self($relationship);
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
        $label = $this->label ?? (string) str($this->relationship)->headline();

        if (! method_exists($record, $this->relationship)) {
            return CheckResult::error(
                key: $this->relationship,
                label: $label,
                message: "Relationship '{$this->relationship}' does not exist on model."
            );
        }

        try {
            $relationValue = $record->getRelationValue($this->relationship);
        } catch (\Throwable $e) {
            $reference = (string) Str::uuid();
            report($e);

            return CheckResult::error(
                key: $this->relationship,
                label: $label,
                message: __('filament-actionguard::ui.errors.check_failed', ['reference' => $reference])
            );
        }

        $isEmpty = false;
        if ($relationValue === null) {
            $isEmpty = true;
        } elseif ($relationValue instanceof Collection && $relationValue->isEmpty()) {
            $isEmpty = true;
        }

        if ($isEmpty) {
            return CheckResult::fail(
                key: $this->relationship,
                label: $label,
                message: __('filament-actionguard::checks.relationship.message', ['field' => $label]),
                required: $this->required
            );
        }

        return CheckResult::pass(
            key: $this->relationship,
            label: $label
        );
    }
}
