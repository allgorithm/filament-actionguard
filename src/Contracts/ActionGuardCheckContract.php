<?php

namespace Allgorithm\FilamentActionGuard\Contracts;

use Allgorithm\FilamentActionGuard\Results\CheckResult;
use Illuminate\Database\Eloquent\Model;

interface ActionGuardCheckContract
{
    public function evaluate(Model $record): CheckResult;
}
