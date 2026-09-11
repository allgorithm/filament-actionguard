<?php

declare(strict_types=1);

namespace Allgorithm\FilamentActionGuard\Bridge;

use Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract;
use Allgorithm\FilamentActionGuard\Contracts\OperationContextFactoryContract;
use Allgorithm\FilamentActionGuard\Results\CheckResolution;
use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Allgorithm\FilamentActionGuard\Results\CheckStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Enterprise Adapter: Adapts a BusinessCore OperationGuardContract to Filament ActionGuardCheckContract.
 */
class BusinessCoreGuardAdapter implements ActionGuardCheckContract
{
    protected BusinessCoreContractMap $contracts;

    /**
     * @param  object|class-string  $guard
     */
    public function __construct(
        protected object|string $guard,
        ?BusinessCoreContractMap $contracts = null,
    ) {
        $this->contracts = $contracts ?? new BusinessCoreContractMap;
    }

    public function evaluate(Model $record): CheckResult
    {
        $correlationId = (string) Str::uuid();
        /** @var object|null $guardInstance */
        $guardInstance = is_string($this->guard) ? app($this->guard) : $this->guard;

        if (! is_object($guardInstance) || ! $guardInstance instanceof $this->contracts->guardContract) {
            return CheckResult::error(
                key: is_string($this->guard) ? $this->guard : 'enterprise_guard',
                label: __('filament-actionguard::ui.enterprise.label'),
                message: __('filament-actionguard::ui.enterprise.unresolvable'),
            );
        }

        try {
            $invoker = [$guardInstance, 'check'];
            if (! is_callable($invoker)) {
                return CheckResult::error(
                    key: is_string($this->guard) ? $this->guard : $guardInstance::class,
                    label: __('filament-actionguard::ui.enterprise.label'),
                    message: __('filament-actionguard::ui.enterprise.not_callable'),
                );
            }

            $coreResult = $invoker($record, $this->makeOperationContext($record, $correlationId));

            return $this->mapCoreResult($coreResult);
        } catch (\Throwable $e) {
            report($e);

            return CheckResult::error(
                key: is_string($this->guard) ? $this->guard : get_class($this->guard),
                label: __('filament-actionguard::ui.enterprise.error_label'),
                message: __('filament-actionguard::ui.enterprise.failed', ['reference' => $correlationId]),
            );
        }
    }

    /**
     * Maps BusinessCore CheckResult to ActionGuard CheckResult.
     */
    protected function mapCoreResult(mixed $coreResult): CheckResult
    {
        if ($coreResult instanceof CheckResult) {
            return $coreResult;
        }

        if (! $coreResult instanceof $this->contracts->checkResult) {
            return CheckResult::error(
                key: 'invalid_result',
                label: __('filament-actionguard::ui.enterprise.label'),
                message: __('filament-actionguard::ui.enterprise.invalid_result'),
            );
        }

        // Map status (CheckStatus enum from Core or string)
        $rawStatus = property_exists($coreResult, 'status') ? $coreResult->status : null;
        $statusValue = $rawStatus instanceof \BackedEnum ? $rawStatus->value : (string) $rawStatus;

        $status = match (strtolower((string) $statusValue)) {
            'pass' => CheckStatus::PASS,
            'fail' => CheckStatus::FAIL,
            default => CheckStatus::ERROR,
        };

        $key = property_exists($coreResult, 'key') ? (string) $coreResult->key : 'core_guard';
        $label = property_exists($coreResult, 'label') ? (string) $coreResult->label : (string) str($key)->headline();
        $message = property_exists($coreResult, 'message') ? (string) $coreResult->message : '';
        $required = property_exists($coreResult, 'required') ? (bool) $coreResult->required : true;

        // Map resolution if present
        $resolution = null;
        if (property_exists($coreResult, 'resolution') && $coreResult->resolution) {
            if (is_string($coreResult->resolution)) {
                $resolution = $coreResult->resolution;
            } elseif (is_object($coreResult->resolution) && property_exists($coreResult->resolution, 'label')) {
                $action = property_exists($coreResult->resolution, 'action')
                    ? $this->sanitizeResolutionUrl($coreResult->resolution->action)
                    : null;
                $resolution = new CheckResolution(
                    label: (string) $coreResult->resolution->label,
                    url: $action,
                );
            }
        }

        $metadata = property_exists($coreResult, 'metadata') && is_array($coreResult->metadata) ? $coreResult->metadata : [];

        return new CheckResult(
            key: $key,
            label: $label,
            status: $status,
            required: $required,
            message: $message,
            severity: $status === CheckStatus::PASS ? 'success' : ($required ? 'error' : 'warning'),
            metadata: $metadata,
            resolution: $resolution
        );
    }

    protected function makeOperationContext(Model $record, string $correlationId): object
    {
        return app(OperationContextFactoryContract::class)->make(
            $record,
            $this->contracts,
            $correlationId,
        );
    }

    protected function sanitizeResolutionUrl(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);
        if ($url === '' || preg_match('/[\x00-\x20\x7F\\\\]/', $url) === 1 || str_starts_with($url, '//')) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['https', 'http'], true) ? $url : null;
    }
}
