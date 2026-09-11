<?php

declare(strict_types=1);

namespace Allgorithm\FilamentActionGuard\Contracts;

use Allgorithm\FilamentActionGuard\Bridge\BusinessCoreContractMap;
use Illuminate\Database\Eloquent\Model;

interface OperationContextFactoryContract
{
    public function make(
        Model $record,
        BusinessCoreContractMap $contracts,
        string $correlationId,
    ): object;
}
