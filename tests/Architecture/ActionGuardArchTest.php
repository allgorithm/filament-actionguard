<?php

arch('it enforces application operation boundary')
    ->expect('Allgorithm\FilamentActionGuard\Actions')
    ->not->toUse('App\Models');

arch('it isolates plugins from foreign eloquent imports')
    ->expect('Allgorithm\FilamentActionGuard')
    ->not->toUse('App\Models');

arch('results and statuses are readonly')
    ->expect('Allgorithm\FilamentActionGuard\Results')
    ->classes()
    ->toBeReadonly();

arch('it does not use Model::all()')
    ->expect('Allgorithm\FilamentActionGuard')
    ->not->toUse('Illuminate\Database\Eloquent\Model::all');

arch('checks implement the ActionGuardCheckContract')
    ->expect('Allgorithm\FilamentActionGuard\Checks')
    ->toImplement('Allgorithm\FilamentActionGuard\Contracts\ActionGuardCheckContract');
