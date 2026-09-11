<?php

declare(strict_types=1);

namespace Allgorithm\FilamentActionGuard\Bridge;

use Allgorithm\FilamentActionGuard\Contracts\OperationContextFactoryContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Community fallback for BusinessCore context construction.
 *
 * A licensed BusinessCore installation may replace this container binding with
 * its own factory without requiring changes in the consuming application.
 */
final class DefaultOperationContextFactory implements OperationContextFactoryContract
{
    public function make(
        Model $record,
        BusinessCoreContractMap $contracts,
        string $correlationId,
    ): object {
        $user = auth()->user();
        $roles = is_object($user) && method_exists($user, 'getRoleNames')
            ? $this->normalizeStringList($user->getRoleNames())
            : [];
        $permissions = is_object($user) && method_exists($user, 'getAllPermissions')
            ? $this->normalizeStringList($user->getAllPermissions(), 'name')
            : [];

        $actorClass = $contracts->actorContext;
        $contextClass = $contracts->operationContext;
        $actor = new $actorClass(
            actorId: (string) (auth()->id() ?? 'system'),
            roles: $roles,
            permissions: $permissions,
            tenantId: $this->stringAttribute($user, 'tenant_id'),
            organizationId: $this->stringAttribute($user, 'organization_id'),
            metadata: ['channel' => 'filament'],
        );

        return new $contextClass(
            actor: $actor,
            correlationId: $correlationId,
            metadata: [
                'bridge' => 'filament-actionguard',
                'record_type' => $record::class,
            ],
        );
    }

    /** @return list<string> */
    private function normalizeStringList(mixed $values, ?string $field = null): array
    {
        if ($values instanceof Collection) {
            $values = $field ? $values->pluck($field) : $values;
            $values = $values->all();
        }

        if (! is_array($values)) {
            return [];
        }

        return array_values(array_map('strval', $values));
    }

    private function stringAttribute(mixed $subject, string $key): ?string
    {
        if (! is_object($subject)) {
            return null;
        }

        $value = data_get($subject, $key);

        return $value === null ? null : (string) $value;
    }
}
