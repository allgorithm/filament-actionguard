<?php

declare(strict_types=1);

namespace Allgorithm\FilamentActionGuard\Bridge;

use Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract;
use InvalidArgumentException;
use LogicException;

/**
 * Resolves BusinessCore Operation descriptors and provides ActionGuard checks.
 */
class EnterpriseBridgeResolver
{
    protected BusinessCoreContractMap $contracts;

    public function __construct(?BusinessCoreContractMap $contracts = null)
    {
        $this->contracts = $contracts ?? new BusinessCoreContractMap;
    }

    /**
     * Resolves guards from an enterprise Operation class.
     *
     * @return array<ActionGuardCheckContract>
     *
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function resolveGuards(string $operationClass): array
    {
        if (! $this->contracts->isAvailable()) {
            throw new LogicException(
                "Enterprise Bridge requires a compatible licensed installation of 'allgorithm/business-core' (^1.2). ".
                'Community checks remain available through ->checks([...]) and ->forState(...).'
            );
        }

        if (! class_exists($operationClass)) {
            throw new InvalidArgumentException("Operation class '{$operationClass}' does not exist.");
        }

        if (! is_a($operationClass, $this->contracts->operationContract, true)) {
            throw new LogicException(
                "The operation '{$operationClass}' must implement '{$this->contracts->operationContract}'."
            );
        }

        $descriptor = $this->extractDescriptor($operationClass);

        if (! $descriptor instanceof $this->contracts->descriptor) {
            throw new LogicException(
                "The operation '{$operationClass}' does not provide a compatible '{$this->contracts->descriptor}'."
            );
        }

        $descriptorData = get_object_vars($descriptor);
        $guards = $descriptorData['guards'] ?? null;
        if (! is_array($guards)) {
            throw new LogicException(
                "The descriptor returned by '{$operationClass}' must expose a guards array."
            );
        }

        $checks = [];
        foreach ($guards as $guard) {
            if ($guard instanceof ActionGuardCheckContract) {
                $checks[] = $guard;

                continue;
            }

            $implementsCoreContract = is_object($guard)
                ? $guard instanceof $this->contracts->guardContract
                : is_string($guard) && is_a($guard, $this->contracts->guardContract, true);

            if (! $implementsCoreContract) {
                $guardType = is_object($guard) ? $guard::class : get_debug_type($guard);

                throw new LogicException(
                    "Guard '{$guardType}' declared by '{$operationClass}' must implement '{$this->contracts->guardContract}'."
                );
            }

            $checks[] = new BusinessCoreGuardAdapter($guard, $this->contracts);
        }

        return $checks;
    }

    /**
     * Attempts to extract the OperationDescriptor from the operation class or container.
     */
    protected function extractDescriptor(string $operationClass): ?object
    {
        try {
            $instance = app($operationClass);
            if (is_object($instance) && method_exists($instance, 'descriptor')) {
                $result = $instance->descriptor();
                if (is_object($result)) {
                    return $result;
                }
            }
        } catch (\Throwable $exception) {
            report($exception);

            throw new LogicException(
                "The operation '{$operationClass}' descriptor could not be resolved. See the application log for details.",
                previous: $exception,
            );
        }

        return null;
    }
}
