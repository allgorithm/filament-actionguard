<?php

namespace Allgorithm\FilamentActionGuard\Checks;

use Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract;
use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Illuminate\Database\Eloquent\Model;

class MediaCheck implements ActionGuardCheckContract
{
    public function __construct(
        protected string $collection,
        protected ?string $label = null,
        protected bool $required = true
    ) {}

    public static function make(string $collection): self
    {
        return new self($collection);
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
        $label = $this->label ?? (string) str($this->collection)->headline();

        $hasMedia = false;

        try {
            // Check if Spatie MediaLibrary is used
            if (method_exists($record, 'getMedia')) {
                $hasMedia = $record->getMedia($this->collection)->isNotEmpty();
            } else {
                // Fallback for simple file arrays or attributes
                $value = $record->getAttribute($this->collection);
                if (is_array($value) && count($value) > 0) {
                    $hasMedia = true;
                } elseif (is_string($value) && trim($value) !== '') {
                    $hasMedia = true;
                }
            }
        } catch (\Throwable $e) {
            return CheckResult::error(
                key: "media.{$this->collection}",
                label: $label,
                message: "MediaCheck for '{$label}' encountered an error: ".$e->getMessage()
            );
        }

        if (! $hasMedia) {
            return CheckResult::fail(
                key: "media.{$this->collection}",
                label: $label,
                message: __('filament-actionguard::checks.media.message', ['collection' => $label]),
                required: $this->required
            );
        }

        return CheckResult::pass(
            key: "media.{$this->collection}",
            label: $label
        );
    }
}
