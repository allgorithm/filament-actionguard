<?php

namespace Allgorithm\FilamentActionGuard\Results;

final readonly class CheckResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $key,
        public string $label,
        public CheckStatus $status,
        public bool $required = true,
        public string $message = '',
        public string $severity = 'error',
        public array $metadata = [],
        public string|CheckResolution|null $resolution = null,
    ) {}

    public static function pass(string $key, string $label, string $message = ''): self
    {
        return new self(
            key: $key,
            label: $label,
            status: CheckStatus::PASS,
            message: $message,
            severity: 'success'
        );
    }

    public static function fail(
        string $key,
        string $label,
        string $message,
        bool $required = true,
        string|CheckResolution|null $resolution = null,
    ): self {
        return new self(
            key: $key,
            label: $label,
            status: CheckStatus::FAIL,
            required: $required,
            message: $message,
            severity: $required ? 'error' : 'warning',
            resolution: $resolution
        );
    }

    public static function error(string $key, string $label, string $message): self
    {
        return new self(
            key: $key,
            label: $label,
            status: CheckStatus::ERROR,
            message: $message,
            severity: 'error'
        );
    }

    public function isPassed(): bool
    {
        return $this->status === CheckStatus::PASS;
    }

    public function isFailed(): bool
    {
        return $this->status === CheckStatus::FAIL || $this->status === CheckStatus::ERROR;
    }
}
